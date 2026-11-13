<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Application;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition;

/** Administrative invalidation preserves raw evidence and atomically replaces only effective projections. */
final class QuizReviewService
{
    public function __construct(private readonly DatabaseInterface $db,private readonly QuizTimeService $time,private readonly \Closure $audit){}
    private function attempt(int $id,int $enrollment,int $quiz,bool $lock=false):object
    {
        $q=$this->db->createQuery()->select('*')->from('#__jlms_attempts')->where('id = :id')->where('enrollment_id = :enrollment')->where('quiz_id = :quiz')
            ->bind(':id',$id,ParameterType::INTEGER)->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quiz,ParameterType::INTEGER);
        if($lock)$q->setQuery((string)$q.' FOR UPDATE');return $this->db->setQuery($q)->loadObject()?:throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND',404);
    }
    private function progress(int $enrollment,int $lesson):?object
    {
        $q=$this->db->createQuery()->select('*')->from('#__jlms_lesson_progress')->where('enrollment_id = :enrollment')->where('lesson_id = :lesson')
            ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':lesson',$lesson,ParameterType::INTEGER);
        return $this->db->setQuery($q)->loadObject()?:null;
    }
    private function plan(object $e,object $quiz,object $a,bool $refund):array
    {
        $enrollment=(int)$e->id;$quizId=(int)$quiz->id;$lesson=(int)$quiz->lesson_id;
        $q=$this->db->createQuery()->select('id,required,source_revision')->from('#__jlms_lessons')->where('id = :lesson')->bind(':lesson',$lesson,ParameterType::INTEGER);
        $l=$this->db->setQuery($q)->loadObject()?:throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND',404);
        $q=$this->db->createQuery()->select('COUNT(*)')->from('#__jlms_attempts')->where('enrollment_id = :enrollment')->where('quiz_id = :quiz')
            ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quizId,ParameterType::INTEGER);$count=(int)$this->db->setQuery($q)->loadResult();
        if($count>10000)throw new \RuntimeException('COM_JLMS_QUIZ_REVIEW_LIMIT',409);
        $q=$this->db->createQuery()->select('COUNT(*)')->from('#__jlms_certificates')->where('enrollment_id = :enrollment')->bind(':enrollment',$enrollment,ParameterType::INTEGER);$certificates=(int)$this->db->setQuery($q)->loadResult();
        $q=$this->db->createQuery()->select('COUNT(*)')->from('#__jlms_assessment_revisions AS r')->innerJoin('#__jlms_attempts AS a ON a.id=r.attempt_id')
            ->where('a.enrollment_id = :enrollment')->where('a.quiz_id = :quiz')->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quizId,ParameterType::INTEGER);$corrections=(int)$this->db->setQuery($q)->loadResult();
        $best=null;$completedAt=null;$used=0;$last=0;$after=0;
        do{
            $q=$this->db->createQuery()->select('id,status,attempt_no,counted_for_limit,score_points,max_points,passed,submitted_at,rules_snapshot_json')->from('#__jlms_attempts')
                ->where('enrollment_id = :enrollment')->where('quiz_id = :quiz')->where('id > :after')
                ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quizId,ParameterType::INTEGER)->bind(':after',$after,ParameterType::INTEGER)->order('id');
            $rows=$this->db->setQuery($q,0,100)->loadObjectList();
            foreach($rows as$row){
                $after=(int)$row->id;$last=max($last,(int)$row->attempt_no);$target=(int)$row->id===(int)$a->id;
                $used+=$target?($refund?0:1):(int)$row->counted_for_limit;
                if($target||!in_array($row->status,['submitted','expired'],true))continue;
                if($row->score_points===null||$row->passed===null||(int)$row->max_points<1||(int)$row->score_points>(int)$row->max_points)throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);
                $rules=json_decode($row->rules_snapshot_json,true,16,JSON_THROW_ON_ERROR);
                if(!in_array($rules['assessment']??'',['graded','practice'],true)||$row->submitted_at===null)throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);
                if(!$best||(int)$row->score_points*$best['max_points']>=$best['score_points']*(int)$row->max_points)$best=['_schema'=>1,'kind'=>'quiz','best_attempt_id'=>(int)$row->id,'score_points'=>(int)$row->score_points,'max_points'=>(int)$row->max_points];
                if((int)$row->passed||$rules['assessment']==='practice')$completedAt=$completedAt===null?($row->submitted_at):min($completedAt,$row->submitted_at);
            }
        }while(count($rows)===100);
        $p=$this->progress($enrollment,$lesson);
        $q=$this->db->createQuery()->select('COUNT(*) AS completed, MAX(p.completed_at) AS latest')->from('#__jlms_lesson_progress AS p')
            ->innerJoin('#__jlms_lessons AS l ON l.id=p.lesson_id AND l.course_version_id=p.course_version_id')
            ->where('p.enrollment_id = :enrollment')->where('l.required = 1')->where("p.state = 'completed'")->where('l.id <> :lesson')
            ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':lesson',$lesson,ParameterType::INTEGER);$others=$this->db->setQuery($q)->loadObject();
        $total=(int)$others->completed+($completedAt!==null?(int)$l->required:0);
        if($total>(int)$e->required_total)throw new \RuntimeException('COM_JLMS_QUIZ_INVALID',422);
        $latest=$others->latest;
        if((int)$l->required&&$completedAt!==null)$latest=$latest===null?$completedAt:max($latest,$completedAt);
        $courseAt=(int)$e->required_total>0&&$total===(int)$e->required_total?$latest:null;
        $beforeBest=$p?json_decode($p->coverage_json,true,16,JSON_THROW_ON_ERROR):null;
        $impact=['attempt_id'=>(int)$a->id,'enrollment_revision'=>(int)$e->revision,'attempt_revision'=>(int)$a->revision,'return_attempt'=>$refund,
            'before_status'=>$a->status,'after_status'=>'invalidated','before_counted'=>(int)$a->counted_for_limit,'after_counted'=>$refund?0:1,
            'before_best'=>$beforeBest?:null,'after_best'=>$best,'before_lesson_completed'=>$p?->state==='completed','after_lesson_completed'=>$completedAt!==null,
            'before_lesson_completed_at'=>$p?->completed_at,'after_lesson_completed_at'=>$completedAt,'before_completed_total'=>(int)$e->completed_total,'after_completed_total'=>$total,
            'before_course_completed_at'=>$e->completed_at,'after_course_completed_at'=>$courseAt,'used_after'=>$used,'last_number'=>$last,
            'certificate_count'=>$certificates,'correction_count'=>$corrections,'blocked'=>$certificates>0||$corrections>0];
        $impact['hash']=hash('sha256',QuestionDefinition::canonical($impact));
        return [$impact,$p,$l];
    }
    public function editor(User $actor,int $course,int $lesson,int $enrollment=0,int $attempt=0,bool $refund=false,int $before=0,int $after=0):array
    {
        $base=$this->time->editor($actor,$course,$lesson,$enrollment,$after);$base['attempts']=[];$base['selected']=null;$base['impact']=null;$base['refund']=$refund;$base['more_attempts']=false;
        if(!$base['enrollment'])return $base;
        $quiz=(int)$base['quiz']->id;
        $q=$this->db->createQuery()->select('id,attempt_no,status,score_points,max_points,passed,counted_for_limit,revision,invalidation_reason,invalidated_by,invalidated_at')->from('#__jlms_attempts')
            ->where('enrollment_id = :enrollment')->where('quiz_id = :quiz')->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quiz,ParameterType::INTEGER)->order('id DESC');
        if($before>0)$q->where('id < :before')->bind(':before',$before,ParameterType::INTEGER);
        $rows=$this->db->setQuery($q,0,21)->loadObjectList();$base['more_attempts']=count($rows)>20;$base['attempts']=array_slice($rows,0,20);
        if($attempt){$a=$this->attempt($attempt,$enrollment,$quiz);$base['selected']=$a;
            if($a->status!=='invalidated')[$base['impact']]=$this->plan($base['enrollment'],$base['quiz'],$a,$refund);
        }return $base;
    }
    public function cancel(User $identity,int $course,int $lesson,int $enrollment,int $attempt,int $enrollmentRevision,int $attemptRevision,bool $refund,string $reason,string $key,string $impactHash,bool $confirmed):int
    {
        $reason=trim($reason);
        if(!$confirmed||!preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D',$key)||!preg_match('/\A[a-f0-9]{64}\z/D',$impactHash))throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400);
        if($reason===''||strlen($reason)>500||preg_match('/[\x00-\x1f\x7f<>]/',$reason)||!preg_match('//u',$reason))throw new \RuntimeException('COM_JLMS_QUIZ_TIME_REASON',422);
        $this->time->managementContext($identity,$course,$lesson,$enrollment);
        $this->db->transactionStart();
        try{
            $q=$this->db->createQuery()->select('id')->from('#__jlms_enrollments')->where('id = :id')->bind(':id',$enrollment,ParameterType::INTEGER);$q->setQuery((string)$q.' FOR UPDATE');$this->db->setQuery($q)->loadResult();
            [$actor,$quiz,$e]=$this->time->managementContext($identity,$course,$lesson,$enrollment);$a=$this->attempt($attempt,$enrollment,(int)$quiz->id,true);
            $scope=hash('sha256',(int)$actor->id.':quiz.cancel:'.$key);
            $hash=hash('sha256',QuestionDefinition::canonical(['course'=>$course,'lesson'=>$lesson,'enrollment'=>$enrollment,'attempt'=>$attempt,'enrollment_revision'=>$enrollmentRevision,'attempt_revision'=>$attemptRevision,'refund'=>$refund,'reason'=>$reason,'impact'=>$impactHash]));
            $q=$this->db->createQuery()->select('request_hash,result_id')->from('#__jlms_idempotency')->where('scope_hash = :scope')->bind(':scope',$scope);$q->setQuery((string)$q.' FOR UPDATE');$old=$this->db->setQuery($q)->loadObject();
            if($old){if(!hash_equals($old->request_hash,$hash))throw new \RuntimeException('COM_JLMS_QUIZ_CONFLICT',409);$this->db->transactionCommit();return(int)$old->result_id;}
            if($a->status==='invalidated'||!in_array($a->status,['open','submitted','expired'],true)||(int)$e->revision!==$enrollmentRevision||(int)$a->revision!==$attemptRevision)throw new \RuntimeException('COM_JLMS_QUIZ_CONFLICT',409);
            [$impact,$progress,$l]=$this->plan($e,$quiz,$a,$refund);
            if($impact['blocked'])throw new \RuntimeException('COM_JLMS_QUIZ_REVIEW_BLOCKED',409);
            if(!hash_equals($impact['hash'],$impactHash))throw new \RuntimeException('COM_JLMS_QUIZ_CONFLICT',409);
            $now=gmdate('Y-m-d H:i:s');$update=(object)['id'=>$attempt,'status'=>'invalidated','counted_for_limit'=>$refund?0:1,'invalidation_reason'=>$reason,
                'invalidated_by'=>(int)$actor->id,'invalidated_at'=>$now,'revision'=>(int)$a->revision+1];$this->db->updateObject('#__jlms_attempts',$update,'id');
            $q=$this->db->createQuery()->delete('#__jlms_active_attempts')->where('attempt_id = :attempt')->bind(':attempt',$attempt,ParameterType::INTEGER);$this->db->setQuery($q)->execute();
            $completed=$impact['after_lesson_completed_at'];$coverage=QuestionDefinition::canonical($impact['after_best']??[]);
            if($progress){
                $q=$this->db->createQuery()->update('#__jlms_lesson_progress')->set('coverage_json = :coverage')->set('state = :state')->set('last_activity_at = :now')->set('revision = revision + 1')
                    ->set($completed===null?'completed_at = NULL':'completed_at = :completed')->where('enrollment_id = :enrollment')->where('lesson_id = :lesson');
                $state=$completed===null?'started':'completed';$q->bind(':coverage',$coverage)->bind(':state',$state)->bind(':now',$now)->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':lesson',$lesson,ParameterType::INTEGER);
                if($completed!==null)$q->bind(':completed',$completed);$this->db->setQuery($q)->execute();
            }
            elseif($impact['after_best']!==null){
                $row=(object)['enrollment_id'=>$enrollment,'course_version_id'=>(int)$e->course_version_id,'lesson_id'=>$lesson,'source_revision'=>(int)$l->source_revision,
                    'coverage_json'=>$coverage,'state'=>$completed===null?'started':'completed','completed_at'=>$completed,'last_activity_at'=>$now,'revision'=>1];$this->db->insertObject('#__jlms_lesson_progress',$row);
            }
            $update=(object)['id'=>$enrollment,'completed_total'=>$impact['after_completed_total'],'completed_at'=>$impact['after_course_completed_at'],
                'revision'=>(int)$e->revision+1,'modified_by'=>(int)$actor->id,'modified_at'=>$now];$this->db->updateObject('#__jlms_enrollments',$update,'id',true);
            if($e->completed_at===null&&$impact['after_course_completed_at']!==null)
                (new CertificateIssuer($this->db))->issueForCompletedEnrollment($enrollment,(int)$e->course_version_id,(string)$impact['after_course_completed_at']);
            ($this->audit)($actor,['id'=>$attempt,'actor_id'=>(int)$actor->id,'enrollment_id'=>$enrollment,'quiz_id'=>(int)$quiz->id,'reason_code'=>'quiz_attempt_invalidated',
                'reason_reference'=>$attempt,'before_status'=>$a->status,'after_status'=>'invalidated','return_attempt'=>$refund,'before_counted'=>(int)$a->counted_for_limit,'after_counted'=>$refund?0:1,
                'before_completed_total'=>$impact['before_completed_total'],'after_completed_total'=>$impact['after_completed_total'],
                'before_completed_at'=>$e->completed_at,'after_completed_at'=>$impact['after_course_completed_at'],'correlation_id'=>bin2hex(random_bytes(16))]);
            $ledger=(object)['scope_hash'=>$scope,'request_hash'=>$hash,'state'=>'completed','result_type'=>'quiz_cancel','result_id'=>$attempt,'http_status'=>200,
                'created_at'=>$now,'expires_at'=>gmdate('Y-m-d H:i:s',time()+604800)];$this->db->insertObject('#__jlms_idempotency',$ledger);
            $this->db->transactionCommit();return $attempt;
        }catch(\Throwable $error){$this->db->transactionRollback();throw $error;}
    }
}
