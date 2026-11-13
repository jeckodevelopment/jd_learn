<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition;
use Jlms\Component\Jlms\Administrator\Domain\QuizScorer;

/** All writers lock enrollment, then attempt. No browser-supplied score, timer or answer key is trusted. */
final class QuizAttemptService
{
    public function __construct(private readonly DatabaseInterface $db,private readonly LearningService $learning,
        private readonly QuizSnapshotService $snapshots,private readonly \Closure $clock,private readonly QuizTimeService $time){}
    private function transaction(\Closure $work):mixed
    {
        $this->db->transactionStart();try{$result=$work();$this->db->transactionCommit();return $result;}
        catch(\Throwable $error){$this->db->transactionRollback();throw $error;}
    }
    private static function date(int $time):string{return gmdate('Y-m-d H:i:s',$time);}
    private static function timestamp(?string $date):?int{return $date===null?null:(new \DateTimeImmutable($date,new \DateTimeZone('UTC')))->getTimestamp();}
    private static function key(string $key):void
    {
        if(!preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D',$key))throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400);
    }
    private function context(User $identity,string $language,int $enrollment,int $lesson):array
    {
        // Acquire the serialisation lock before core user/category reads establish an InnoDB read view.
        // A request waiting for another tab must authorise and read answers after that tab commits.
        $owner=(int)$identity->id;
        $lock=$this->db->createQuery()->select('id')->from('#__jlms_enrollments')->where('id = :id')->where('user_id = :owner')
            ->bind(':id',$enrollment,ParameterType::INTEGER)->bind(':owner',$owner,ParameterType::INTEGER);
        $lock->setQuery((string)$lock.' FOR UPDATE');$this->db->setQuery($lock)->loadResult();
        [$actor,$e,$l]=$this->learning->quizContext($identity,$language,$enrollment,$lesson,true,true);
        $q=$this->db->createQuery()->select('*')->from('#__jlms_quizzes')->where('lesson_id = :lesson')->where('course_version_id = :version');$version=(int)$l->course_version_id;
        $q->bind(':lesson',$lesson,ParameterType::INTEGER)->bind(':version',$version,ParameterType::INTEGER);
        $quiz=$this->db->setQuery($q)->loadObject()?:throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND',404);
        $selection=json_decode($quiz->selection_json,true,16,JSON_THROW_ON_ERROR);
        if(!is_string($selection['published_pool_hash']??null))throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);
        return [$actor,$e,$l,$quiz,$selection];
    }
    private function attempt(int $id,int $enrollment,int $quiz):object
    {
        $q=$this->db->createQuery()->select('*')->from('#__jlms_attempts')->where('id = :id')->where('enrollment_id = :enrollment')->where('quiz_id = :quiz')
            ->bind(':id',$id,ParameterType::INTEGER)->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quiz,ParameterType::INTEGER);
        $q->setQuery((string)$q.' FOR UPDATE');return $this->db->setQuery($q)->loadObject()?:throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND',404);
    }
    private function active(int $enrollment,int $quiz):?object
    {
        $q=$this->db->createQuery()->select('attempt_id')->from('#__jlms_active_attempts')->where('enrollment_id = :enrollment')->where('quiz_id = :quiz')
            ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quiz,ParameterType::INTEGER);
        $q->setQuery((string)$q.' FOR UPDATE');$id=(int)$this->db->setQuery($q)->loadResult();return $id?$this->attempt($id,$enrollment,$quiz):null;
    }
    private function history(int $enrollment,int $quiz):array
    {
        $q=$this->db->createQuery()->select("SUM(counted_for_limit) AS used, MAX(attempt_no) AS last_no, MAX(CASE WHEN status IN ('submitted','expired') THEN submitted_at END) AS last_closed, MAX(CASE WHEN status IN ('submitted','expired') THEN passed ELSE 0 END) AS any_passed")
            ->from('#__jlms_attempts')->where('enrollment_id = :enrollment')->where('quiz_id = :quiz')
            ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quiz,ParameterType::INTEGER);
        $q->setQuery((string)$q.' FOR UPDATE');$r=$this->db->setQuery($q)->loadObject();return ['used'=>(int)$r->used,'last_no'=>(int)$r->last_no,'last_closed'=>self::timestamp($r->last_closed),'passed'=>(bool)$r->any_passed];
    }
    private function startLedger(int $actor,string $key,int $enrollment,int $lesson,?int $result=null):?int
    {
        $scope=hash('sha256',$actor.':quiz.start:'.$key);$hash=hash('sha256',$enrollment.':'.$lesson);
        $q=$this->db->createQuery()->select('request_hash,result_id')->from('#__jlms_idempotency')->where('scope_hash = :scope')->bind(':scope',$scope);
        $q->setQuery((string)$q.' FOR UPDATE');$r=$this->db->setQuery($q)->loadObject();
        if($r){if(!hash_equals($r->request_hash,$hash))throw new \RuntimeException('COM_JLMS_QUIZ_CONFLICT',409);return(int)$r->result_id;}
        if($result!==null){
            $now=self::date(($this->clock)());$expires=self::date(($this->clock)()+604800);
            $q=$this->db->createQuery();$q->setQuery("INSERT INTO #__jlms_idempotency (scope_hash,request_hash,state,result_type,result_id,http_status,created_at,expires_at) VALUES (:scope,:hash,'completed','quiz_attempt',:result,200,:now,:expires) ON DUPLICATE KEY UPDATE scope_hash=scope_hash")
                ->bind(':scope',$scope)->bind(':hash',$hash)->bind(':result',$result,ParameterType::INTEGER)->bind(':now',$now)->bind(':expires',$expires);
            $this->db->setQuery($q)->execute();return $this->startLedger($actor,$key,$enrollment,$lesson);
        }return null;
    }
    private static function shuffle(array $rows):array
    {
        for($i=count($rows)-1;$i>0;$i--){$j=random_int(0,$i);[$rows[$i],$rows[$j]]=[$rows[$j],$rows[$i]];}return $rows;
    }
    public function start(User $identity,string $language,int $enrollment,int $lesson,string $key):array
    {
        self::key($key);
        return $this->transaction(function()use($identity,$language,$enrollment,$lesson,$key):array{
            [$actor,$e,$l,$quiz]=$this->context($identity,$language,$enrollment,$lesson);$quizId=(int)$quiz->id;$now=($this->clock)();
            $replay=$this->startLedger((int)$actor->id,$key,$enrollment,$lesson);
            if($replay){$a=$this->attempt($replay,$enrollment,$quizId);$this->expireIfDue($a,$e,$l,$now);return $this->dto($a,$e,$l,$quiz,$identity,$language);}
            $a=$this->active($enrollment,$quizId);
            if($a){$this->expireIfDue($a,$e,$l,$now);$this->startLedger((int)$actor->id,$key,$enrollment,$lesson,(int)$a->id);return $this->dto($a,$e,$l,$quiz,$identity,$language);}
            $this->learning->quizContext($identity,$language,$enrollment,$lesson,false,false);
            [$quiz,$rules,$pool]=$this->snapshots->published($lesson,(int)$l->course_version_id);$history=$this->history($enrollment,$quizId);
            $rules=$this->time->rules($rules,$enrollment,$quizId);
            if(($rules['max_attempts']>0&&$history['used']>=$rules['max_attempts'])||($history['last_closed']!==null&&$history['last_closed']+$rules['retry_seconds']>$now))throw new \RuntimeException('COM_JLMS_QUIZ_LIMIT',409);
            if($rules['mode']==='random')$pool=array_slice(self::shuffle($pool),0,$rules['draw_count']);
            if($rules['shuffle_questions'])$pool=self::shuffle($pool);
            $rules['lesson_required']=(int)$l->required;$rules['source_revision']=(int)$l->source_revision;
            $max=array_sum(array_map(static fn($row):int=>(int)$row->points,$pool));
            $a=(object)['enrollment_id'=>$enrollment,'course_version_id'=>(int)$l->course_version_id,'quiz_id'=>$quizId,'attempt_no'=>$history['last_no']+1,
                'status'=>'open','score_points'=>null,'passed'=>null,'submitted_at'=>null,'rules_snapshot_json'=>QuestionDefinition::canonical($rules),'max_points'=>$max,'started_at'=>self::date($now),
                'deadline_at'=>$rules['time_seconds']?self::date($now+$rules['time_seconds']):null,'counted_for_limit'=>1,'revision'=>1];
            $this->db->insertObject('#__jlms_attempts',$a,'id');
            $filter=\Joomla\CMS\Filter\InputFilter::getInstance([],[],1,1);$order=0;
            foreach($pool as$row){
                $public=$row->public;$public['prompt']=$filter->clean($public['prompt'],'HTML');if($rules['shuffle_options'])$public['options']=self::shuffle($public['options']);
                $grading=$row->grading;$grading['explanation']=$filter->clean($grading['explanation'],'HTML');
                $snapshot=(object)['attempt_id'=>(int)$a->id,'question_revision_id'=>(int)$row->id,'display_order'=>$order++,
                    'public_snapshot_json'=>QuestionDefinition::canonical($public),'grading_snapshot_json'=>QuestionDefinition::canonical($grading),'max_points'=>(int)$row->points];
                $this->db->insertObject('#__jlms_attempt_questions',$snapshot);
            }
            $slot=(object)['enrollment_id'=>$enrollment,'quiz_id'=>$quizId,'attempt_id'=>(int)$a->id];$this->db->insertObject('#__jlms_active_attempts',$slot);
            $touch=(object)['id'=>$enrollment,'started_at'=>$e->started_at?:self::date($now),'last_activity_at'=>self::date($now),'last_lesson_id'=>$lesson,
                'revision'=>(int)$e->revision+1,'modified_at'=>self::date($now),'modified_by'=>(int)$actor->id];$this->db->updateObject('#__jlms_enrollments',$touch,'id');
            $this->startLedger((int)$actor->id,$key,$enrollment,$lesson,(int)$a->id);
            return $this->dto($a,$e,$l,$quiz,$identity,$language);
        });
    }
    private function questions(int $attempt):array
    {
        $q=$this->db->createQuery()->select('q.*, a.answer_json')->from('#__jlms_attempt_questions AS q')
            ->leftJoin('#__jlms_attempt_answers AS a ON a.attempt_question_id=q.id')->where('q.attempt_id = :attempt')->bind(':attempt',$attempt,ParameterType::INTEGER)->order('q.display_order');
        $q->setQuery((string)$q.' LIMIT 201 FOR UPDATE');$rows=$this->db->setQuery($q)->loadObjectList();if(!$rows||count($rows)>200)throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);return $rows;
    }
    /** Autosaves read only option identifiers, never multi-megabyte prompts or private grading documents. */
    private function answerQuestions(int $attempt):array
    {
        $q=$this->db->createQuery()->select("id, JSON_UNQUOTE(JSON_EXTRACT(public_snapshot_json, '$.kind')) AS kind, JSON_EXTRACT(public_snapshot_json, '$.options[*].id') AS option_ids")
            ->from('#__jlms_attempt_questions')->where('attempt_id = :attempt')->bind(':attempt',$attempt,ParameterType::INTEGER)->order('display_order');
        $rows=$this->db->setQuery($q,0,201)->loadObjectList();
        if(!$rows||count($rows)>200)throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);return $rows;
    }
    private function patch(array $questions,string $json):array
    {
        if(strlen($json)>524288)throw new \RuntimeException('COM_JLMS_QUIZ_ANSWERS_INVALID',413);
        try{$answers=json_decode($json,true,16,JSON_THROW_ON_ERROR);}catch(\JsonException $e){throw new \RuntimeException('COM_JLMS_QUIZ_ANSWERS_INVALID',422);}
        if(!is_array($answers)||count($answers)>200)throw new \RuntimeException('COM_JLMS_QUIZ_ANSWERS_INVALID',422);
        $allowed=[];foreach($questions as$q)$allowed[(int)$q->id]=['kind'=>$q->kind,'ids'=>json_decode($q->option_ids,true,16,JSON_THROW_ON_ERROR)];
        $patch=[];
        foreach($answers as$id=>$selected){
            try{$id=CurriculumService::id($id);}catch(\InvalidArgumentException $error){throw new \RuntimeException('COM_JLMS_QUIZ_ANSWERS_INVALID',422);}
            if(!isset($allowed[$id])||!is_array($selected)||!array_is_list($selected)||count($selected)>20
                ||($allowed[$id]['kind']!=='multiple'&&count($selected)>1))throw new \RuntimeException('COM_JLMS_QUIZ_ANSWERS_INVALID',422);
            $ids=$allowed[$id]['ids'];
            foreach($selected as$value)if(!is_string($value)||!in_array($value,$ids,true))throw new \RuntimeException('COM_JLMS_QUIZ_ANSWERS_INVALID',422);
            if(count($selected)!==count(array_unique($selected,SORT_STRING)))throw new \RuntimeException('COM_JLMS_QUIZ_ANSWERS_INVALID',422);
            sort($selected,SORT_STRING);$patch[$id]=$selected;
        }ksort($patch,SORT_NUMERIC);return $patch;
    }
    public function mutate(User $identity,string $language,int $enrollment,int $lesson,int $attempt,int $revision,string $key,string $json,bool $submit):array
    {
        self::key($key);
        return $this->transaction(function()use($identity,$language,$enrollment,$lesson,$attempt,$revision,$key,$json,$submit):array{
            [, $e,$l,$quiz]=$this->context($identity,$language,$enrollment,$lesson);$a=$this->attempt($attempt,$enrollment,(int)$quiz->id);
            if($a->status==='invalidated')throw new \RuntimeException('COM_JLMS_QUIZ_CANCELLED',409);
            $rows=$this->answerQuestions($attempt);$patch=$this->patch($rows,$json);$hash=hash('sha256',QuestionDefinition::canonical(['operation'=>$submit?'submit':'save','revision'=>$revision,'answers'=>$patch]));
            $q=$this->db->createQuery()->select('*')->from('#__jlms_quiz_mutations')->where('attempt_id = :attempt')->where('mutation_id = :mutation')
                ->bind(':attempt',$attempt,ParameterType::INTEGER)->bind(':mutation',$key);$q->setQuery((string)$q.' FOR UPDATE');$mutation=$this->db->setQuery($q)->loadObject();
            $now=($this->clock)();
            if($mutation){if(!hash_equals($mutation->request_hash,$hash))throw new \RuntimeException('COM_JLMS_QUIZ_CONFLICT',409);$this->expireIfDue($a,$e,$l,$now);$dto=$this->dto($a,$e,$l,$quiz,$identity,$language,$submit||$a->status!=='open');$dto['replay']=true;return $dto;}
            $q=$this->db->createQuery()->select('COUNT(*)')->from('#__jlms_quiz_mutations')->where('attempt_id = :attempt')->bind(':attempt',$attempt,ParameterType::INTEGER);
            $q->setQuery((string)$q.' FOR UPDATE');if((int)$this->db->setQuery($q)->loadResult()>=50000)throw new \RuntimeException('COM_JLMS_QUIZ_LIMIT',429);
            $expired=$this->expireIfDue($a,$e,$l,$now);
            if($a->status!=='open'){
                if(!$submit&&!$expired)throw new \RuntimeException('COM_JLMS_QUIZ_CLOSED',409);
            }else{
                if((int)$a->revision!==$revision)throw new \RuntimeException('COM_JLMS_QUIZ_CONFLICT',409);
                foreach($patch as$id=>$answer){
                    $encoded=QuestionDefinition::canonical($answer);$date=self::date($now);
                    $q=$this->db->createQuery();$q->setQuery('INSERT INTO #__jlms_attempt_answers (attempt_question_id,answer_json,saved_at) VALUES (:id,:answer,:now) ON DUPLICATE KEY UPDATE answer_json=VALUES(answer_json),saved_at=VALUES(saved_at)')
                        ->bind(':id',$id,ParameterType::INTEGER)->bind(':answer',$encoded)->bind(':now',$date);$this->db->setQuery($q)->execute();
                }
                if($submit){$this->grade($a,$e,$l,$now,'submitted');}
                else{
                    $a->revision=(int)$a->revision+1;$update=(object)['id'=>$attempt,'revision'=>$a->revision];$this->db->updateObject('#__jlms_attempts',$update,'id');
                    $touch=(object)['id'=>$enrollment,'last_activity_at'=>self::date($now),'last_lesson_id'=>$lesson,'revision'=>(int)$e->revision+1,'modified_at'=>self::date($now),'modified_by'=>$e->user_id];
                    $this->db->updateObject('#__jlms_enrollments',$touch,'id');$e->revision=$touch->revision;
                }
            }
            $mutation=(object)['attempt_id'=>$attempt,'mutation_id'=>$key,'request_hash'=>$hash,'result_revision'=>(int)$a->revision,'created_at'=>self::date($now)];$this->db->insertObject('#__jlms_quiz_mutations',$mutation);
            $dto=$this->dto($a,$e,$l,$quiz,$identity,$language,$submit||$a->status!=='open');$dto['replay']=false;return $dto;
        });
    }
    private function expireIfDue(object $a,object $enrollment,object $lesson,int $now):bool
    {
        $deadline=self::timestamp($a->deadline_at);
        if($a->status==='open'&&$deadline!==null&&$deadline<=$now){$this->grade($a,$enrollment,$lesson,$deadline,'expired');return true;}return false;
    }
    private function grade(object $a,object $e,object $l,int $time,string $status):void
    {
        if($a->status!=='open')return;
        $score=0;$rules=json_decode($a->rules_snapshot_json,true,16,JSON_THROW_ON_ERROR);
        foreach($this->questions((int)$a->id)as$q){
            $grading=json_decode($q->grading_snapshot_json,true,16,JSON_THROW_ON_ERROR);$answer=$q->answer_json===null?[]:json_decode($q->answer_json,true,16,JSON_THROW_ON_ERROR);
            $points=QuizScorer::score($answer,$grading['correct'],(int)$q->max_points);$score+=$points;
            $update=(object)['id'=>(int)$q->id,'awarded_points'=>$points];$this->db->updateObject('#__jlms_attempt_questions',$update,'id');
        }
        $passed=$rules['threshold']===0||QuizScorer::passes($score,(int)$a->max_points,$rules['threshold']);
        $a->status=$status;$a->score_points=$score;$a->passed=(int)$passed;$a->submitted_at=self::date($time);$a->revision=(int)$a->revision+1;
        $update=(object)['id'=>(int)$a->id,'status'=>$status,'score_points'=>$score,'passed'=>(int)$passed,'submitted_at'=>$a->submitted_at,'revision'=>$a->revision];$this->db->updateObject('#__jlms_attempts',$update,'id');
        $attempt=(int)$a->id;$q=$this->db->createQuery()->delete('#__jlms_active_attempts')->where('attempt_id = :attempt')->bind(':attempt',$attempt,ParameterType::INTEGER);$this->db->setQuery($q)->execute();
        $enrollment=(int)$e->id;$lesson=(int)$l->id;
        $q=$this->db->createQuery()->select('*')->from('#__jlms_lesson_progress')->where('enrollment_id = :enrollment')->where('lesson_id = :lesson')
            ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':lesson',$lesson,ParameterType::INTEGER);
        $q->setQuery((string)$q.' FOR UPDATE');$progress=$this->db->setQuery($q)->loadObject();
        $best=$progress?json_decode($progress->coverage_json,true,16,JSON_THROW_ON_ERROR):null;
        if(!$best||$score*$best['max_points']>=$best['score_points']*(int)$a->max_points)$best=['_schema'=>1,'kind'=>'quiz','best_attempt_id'=>(int)$a->id,'score_points'=>$score,'max_points'=>(int)$a->max_points];
        $wasCompleted=$progress&&$progress->state==='completed';$completed=$wasCompleted||$passed||$rules['assessment']==='practice';
        $fields=['coverage_json'=>QuestionDefinition::canonical($best),'state'=>$completed?'completed':'started','last_activity_at'=>self::date($time),
            'completed_at'=>$progress?->completed_at?:($completed?self::date($time):null),'revision'=>$progress?(int)$progress->revision+1:1];
        if($progress){
            $q=$this->db->createQuery()->update('#__jlms_lesson_progress')->set('coverage_json = :coverage')->set('state = :state')->set('last_activity_at = :time')->set('revision = :revision')
                ->set($fields['completed_at']===null?'completed_at = NULL':'completed_at = :completed')->where('enrollment_id = :enrollment')->where('lesson_id = :lesson')
                ->bind(':coverage',$fields['coverage_json'])->bind(':state',$fields['state'])->bind(':time',$fields['last_activity_at'])->bind(':revision',$fields['revision'],ParameterType::INTEGER)
                ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':lesson',$lesson,ParameterType::INTEGER);
            if($fields['completed_at']!==null)$q->bind(':completed',$fields['completed_at']);$this->db->setQuery($q)->execute();
        }else{$record=(object)($fields+['enrollment_id'=>$enrollment,'course_version_id'=>(int)$a->course_version_id,'lesson_id'=>$lesson,'source_revision'=>$rules['source_revision']]);$this->db->insertObject('#__jlms_lesson_progress',$record);}
        $total=(int)$e->completed_total+(!$wasCompleted&&$completed?(int)$l->required:0);
        $finished=$e->completed_at?:($total>=(int)$e->required_total&&(int)$e->required_total>0?self::date($time):null);
        $newCourseCompletion=$e->completed_at===null&&$finished!==null;
        $touch=(object)['id'=>$enrollment,'completed_total'=>$total,'completed_at'=>$finished,'last_activity_at'=>self::date($time),'last_lesson_id'=>$lesson,
            'revision'=>(int)$e->revision+1,'modified_at'=>self::date($time),'modified_by'=>$e->user_id];$this->db->updateObject('#__jlms_enrollments',$touch,'id',true);
        $e->completed_total=$total;$e->completed_at=$finished;$e->revision=$touch->revision;
        if($newCourseCompletion)(new CertificateIssuer($this->db))->issueForCompletedEnrollment($enrollment,(int)$e->course_version_id,$finished);
    }
    public function state(User $identity,string $language,int $enrollment,int $lesson,int $attempt=0,bool $compact=false):array
    {
        return $this->transaction(function()use($identity,$language,$enrollment,$lesson,$attempt,$compact):array{
            [, $e,$l,$quiz]=$this->context($identity,$language,$enrollment,$lesson);$quizId=(int)$quiz->id;
            $active=$this->active($enrollment,$quizId);if($active)$this->expireIfDue($active,$e,$l,($this->clock)());
            if($attempt)$a=$this->attempt($attempt,$enrollment,$quizId);
            elseif($active)$a=$active;
            else{$q=$this->db->createQuery()->select('id')->from('#__jlms_attempts')->where('enrollment_id = :enrollment')->where('quiz_id = :quiz')
                    ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quizId,ParameterType::INTEGER)->order('attempt_no DESC');$q->setQuery((string)$q.' LIMIT 1 FOR UPDATE');$id=(int)$this->db->setQuery($q)->loadResult();$a=$id?$this->attempt($id,$enrollment,$quizId):null;}
            return $this->dto($a,$e,$l,$quiz,$identity,$language,!$compact);
        });
    }
    private function dto(?object $a,object $e,object $l,object $quiz,User $identity,string $language,bool $includeQuestions=true):array
    {
        $enrollment=(int)$e->id;$quizId=(int)$quiz->id;$history=$this->history($enrollment,$quizId);$active=$this->active($enrollment,$quizId);
        $selection=json_decode($quiz->selection_json,true,16,JSON_THROW_ON_ERROR);
        $rules=$a?json_decode($a->rules_snapshot_json,true,16,JSON_THROW_ON_ERROR):['max_attempts'=>(int)$quiz->max_attempts,'retry_seconds'=>$selection['retry_delay_seconds'],'threshold'=>(int)$quiz->pass_basis_points,'time_seconds'=>(int)$quiz->time_limit_seconds,'assessment'=>$selection['assessment'],'feedback'=>$quiz->feedback_policy,'draw_count'=>$selection['draw_count']];
        $nextRules=$this->time->rules(['time_seconds'=>(int)$quiz->time_limit_seconds],$enrollment,$quizId);
        if(!$a)$rules=array_replace($rules,$nextRules);
        $originalTime=(int)$rules['time_seconds'];$effectiveTime=$a&&$a->deadline_at!==null?max(0,self::timestamp($a->deadline_at)-self::timestamp($a->started_at)):$originalTime;
        $now=($this->clock)();$next=$history['last_closed']===null?$now:$history['last_closed']+$rules['retry_seconds'];$eligible=!$active;
        try{if(!$active)$this->learning->quizContext($identity,$language,$enrollment,(int)$l->id,false,false);}catch(\RuntimeException $error){if($error->getMessage()!=='COM_JLMS_LEARNING_WINDOW')throw $error;$eligible=false;}
        $remaining=$rules['max_attempts']===0?null:max(0,$rules['max_attempts']-$history['used']);
        $dto=['enrollment_id'=>$enrollment,'lesson_id'=>(int)$l->id,'title'=>$l->title,'server_now'=>$now,'assessment'=>$rules['assessment'],
            'base_time_seconds'=>$rules['base_time_seconds']??$rules['time_seconds'],'extra_time_seconds'=>$rules['extra_time_seconds']??0,'next_time_seconds'=>$nextRules['time_seconds'],'next_extra_time_seconds'=>$nextRules['extra_time_seconds'],'original_time_seconds'=>$originalTime,'extension_seconds'=>max(0,$effectiveTime-$originalTime),'feedback_policy'=>$rules['feedback'],'question_count'=>$rules['draw_count'],'threshold'=>$rules['threshold'],'time_seconds'=>$effectiveTime,'used_attempts'=>$history['used'],'remaining_attempts'=>$remaining,
            'next_attempt_at'=>$next,'can_start'=>!$active&&$eligible&&($remaining===null||$remaining>0)&&$next<=$now,
            'active_attempt_id'=>$active?(int)$active->id:null,'attempt'=>null,'questions'=>[],'feedback_available'=>false,
            'course_completed'=>$e->completed_at!==null,'course_percent'=>(int)$e->required_total?min($e->completed_at?100:99,(int)floor(100*(int)$e->completed_total/(int)$e->required_total)):0];
        if($a){
            $closed=$a->status!=='open';$graded=in_array($a->status,['submitted','expired'],true);$feedback=$graded&&!$active&&($rules['feedback']==='after_submit'||($rules['feedback']==='pass_or_exhausted'&&($history['passed']||($remaining!==null&&$remaining===0))));
            $dto['feedback_available']=$feedback;$dto['attempt']=['id'=>(int)$a->id,'number'=>(int)$a->attempt_no,'state'=>$a->status,'revision'=>(int)$a->revision,
                'deadline'=>self::timestamp($a->deadline_at),'started_at'=>self::timestamp($a->started_at),'submitted_at'=>self::timestamp($a->submitted_at),
                'valid'=>$a->status!=='invalidated','counted_for_limit'=>(bool)($a->counted_for_limit??1),'score_points'=>$a->score_points===null?null:(int)$a->score_points,'max_points'=>(int)$a->max_points,'passed'=>$a->passed===null?null:(bool)$a->passed];
            foreach(($includeQuestions?$this->questions((int)$a->id):[])as$row){$public=json_decode($row->public_snapshot_json,true,16,JSON_THROW_ON_ERROR);
                $public['id']=(int)$row->id;$public['max_points']=(int)$row->max_points;$public['selected']=$row->answer_json===null?[]:json_decode($row->answer_json,true,16,JSON_THROW_ON_ERROR);
                if($feedback){$grading=json_decode($row->grading_snapshot_json,true,16,JSON_THROW_ON_ERROR);$public['correct']=$grading['correct'];$public['explanation']=$grading['explanation'];$public['awarded_points']=(int)$row->awarded_points;}
                $dto['questions'][]=$public;
            }
        }
        $q=$this->db->createQuery()->select('id,attempt_no,status,score_points,max_points,passed,submitted_at,counted_for_limit')->from('#__jlms_attempts')->where('enrollment_id = :enrollment')->where('quiz_id = :quiz')
            ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quizId,ParameterType::INTEGER)->order('attempt_no DESC');$q->setQuery((string)$q.' LIMIT 20 FOR UPDATE');$dto['history']=$this->db->setQuery($q)->loadAssocList();
        $q=$this->db->createQuery()->select('coverage_json')->from('#__jlms_lesson_progress')->where('enrollment_id = :enrollment')->where('lesson_id = :lesson');$lesson=(int)$l->id;
        $q->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':lesson',$lesson,ParameterType::INTEGER);$q->setQuery((string)$q.' FOR UPDATE');$best=$this->db->setQuery($q)->loadResult();$dto['best']=$best?(json_decode($best,true,16,JSON_THROW_ON_ERROR)?:null):null;
        return $dto;
    }
    public function expireDue(int $limit=100):array
    {
        $limit=max(1,min(100,$limit));$now=self::date(($this->clock)());
        $q=$this->db->createQuery()->select('id,enrollment_id,quiz_id')->from('#__jlms_attempts')->where("status = 'open' AND deadline_at IS NOT NULL AND deadline_at <= :now")
            ->bind(':now',$now)->order('deadline_at,id');$due=$this->db->setQuery($q,0,$limit)->loadObjectList();$closed=0;$failed=0;
        foreach($due as$record){try{$changed=$this->transaction(function()use($record):bool{
                    $enrollment=(int)$record->enrollment_id;$q=$this->db->createQuery()->select('*')->from('#__jlms_enrollments')->where('id = :id')->bind(':id',$enrollment,ParameterType::INTEGER);
                    $q->setQuery((string)$q.' FOR UPDATE');$e=$this->db->setQuery($q)->loadObject();if(!$e)throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND',404);
                    $a=$this->attempt((int)$record->id,$enrollment,(int)$record->quiz_id);$quiz=(int)$record->quiz_id;
                    $q=$this->db->createQuery()->select('l.*')->from('#__jlms_lessons AS l')->innerJoin('#__jlms_quizzes AS q ON q.lesson_id=l.id')->where('q.id = :quiz')->bind(':quiz',$quiz,ParameterType::INTEGER);
                    $l=$this->db->setQuery($q)->loadObject()?:throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);
                    return $this->expireIfDue($a,$e,$l,($this->clock)());
                });if($changed)$closed++;
            }catch(\Throwable $error){$failed++;\Joomla\CMS\Log\Log::add('Quiz expiry failed; correlation='.bin2hex(random_bytes(8)),\Joomla\CMS\Log\Log::ERROR,'com_jlms');}}
        return ['closed'=>$closed,'failed'=>$failed,'has_more'=>count($due)===$limit];
    }
}
