<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Application;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition;

/** Append-only deadline amendments; answer revisions and frozen teaching snapshots do not change. */
final class QuizDeadlineService
{
    public function __construct(private readonly DatabaseInterface $db,private readonly QuizTimeService $time,private readonly \Closure $audit,private readonly \Closure $clock){}
    private static function timestamp(string $date):int{return(new \DateTimeImmutable($date,new \DateTimeZone('UTC')))->getTimestamp();}
    private function attempt(int $id,int $enrollment,int $quiz,bool $lock=false):object
    {
        $q=$this->db->createQuery()->select('*')->from('#__jlms_attempts')->where('id = :id')->where('enrollment_id = :enrollment')->where('quiz_id = :quiz')
            ->bind(':id',$id,ParameterType::INTEGER)->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quiz,ParameterType::INTEGER);
        if($lock)$q->setQuery((string)$q.' FOR UPDATE');return $this->db->setQuery($q)->loadObject()?:throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND',404);
    }
    private function latest(int $attempt):?object
    {
        $q=$this->db->createQuery()->select('*')->from('#__jlms_quiz_deadline_extensions')->where('attempt_id = :attempt')->bind(':attempt',$attempt,ParameterType::INTEGER)->order('revision_no DESC');
        return $this->db->setQuery($q,0,1)->loadObject()?:null;
    }
    private function plan(object $e,object $a,int $minutes):array
    {
        if($minutes<1||$minutes>1440)throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400);
        $rules=json_decode($a->rules_snapshot_json,true,16,JSON_THROW_ON_ERROR);$original=(int)($rules['time_seconds']??0);$last=$this->latest((int)$a->id);
        $started=self::timestamp($a->started_at);$old=$a->deadline_at===null?null:self::timestamp($a->deadline_at);$new=$old===null?null:$old+$minutes*60;
        $hash=hash('sha256',$a->rules_snapshot_json);$block=null;
        if($a->status!=='open'||!in_array($e->status,['active','completed'],true))$block='COM_JLMS_QUIZ_EXTEND_CLOSED';
        elseif($old===null||$original===0)$block='COM_JLMS_QUIZ_EXTEND_UNTIMED';
        elseif($old<=($this->clock)())$block='COM_JLMS_QUIZ_EXTEND_EXPIRED';
        elseif($new-$started>172800||($last&&(int)$last->revision_no>=1000))$block='COM_JLMS_QUIZ_EXTEND_LIMIT';
        if($old!==null){
            $expected=$last?self::timestamp($last->new_deadline):$started+$original;
            if($expected!==$old||($last&&!hash_equals($last->rules_hash,$hash)))throw new \RuntimeException('COM_JLMS_QUIZ_EXTEND_INCONSISTENT',422);
        }
        $p=['attempt_id'=>(int)$a->id,'enrollment_revision'=>(int)$e->revision,'attempt_revision'=>(int)$a->revision,'extension_revision'=>$last?(int)$last->revision_no:0,
            'status'=>$a->status,'original_seconds'=>$original,'previous_deadline'=>$old,'new_deadline'=>$new,'added_seconds'=>$minutes*60,'effective_seconds'=>$new===null?0:$new-$started,
            'rules_hash'=>$hash,'blocked'=>$block];$p['hash']=hash('sha256',QuestionDefinition::canonical($p));return $p;
    }
    public function editor(User $actor,int $course,int $lesson,int $enrollment,int $attempt,int $minutes=5):array
    {
        [, $quiz,$e]=$this->time->managementContext($actor,$course,$lesson,$enrollment);$a=$this->attempt($attempt,$enrollment,(int)$quiz->id);
        $q=$this->db->createQuery()->select('revision_no,previous_deadline,new_deadline,added_seconds,actor_user_id,reason,created_at')->from('#__jlms_quiz_deadline_extensions')
            ->where('attempt_id = :attempt')->bind(':attempt',$attempt,ParameterType::INTEGER)->order('revision_no DESC');
        return ['course_id'=>$course,'lesson_id'=>$lesson,'quiz'=>$quiz,'enrollment'=>$e,'attempt'=>$a,'minutes'=>$minutes,'impact'=>$this->plan($e,$a,$minutes),'history'=>$this->db->setQuery($q,0,20)->loadObjectList()];
    }
    public function extend(User $identity,int $course,int $lesson,int $enrollment,int $attempt,int $enrollmentRevision,int $attemptRevision,int $minutes,string $reason,string $key,string $impactHash,bool $confirmed):int
    {
        $reason=trim($reason);
        if(!$confirmed||!preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D',$key)||!preg_match('/\A[a-f0-9]{64}\z/D',$impactHash)||$minutes<1||$minutes>1440)throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400);
        if($reason===''||strlen($reason)>500||preg_match('/[\x00-\x1f\x7f<>]/',$reason)||!preg_match('//u',$reason))throw new \RuntimeException('COM_JLMS_QUIZ_TIME_REASON',422);
        $this->time->managementContext($identity,$course,$lesson,$enrollment);$this->db->transactionStart();
        try{
            $q=$this->db->createQuery()->select('id')->from('#__jlms_enrollments')->where('id = :id')->bind(':id',$enrollment,ParameterType::INTEGER);$q->setQuery((string)$q.' FOR UPDATE');$this->db->setQuery($q)->loadResult();
            [$actor,$quiz,$e]=$this->time->managementContext($identity,$course,$lesson,$enrollment);$a=$this->attempt($attempt,$enrollment,(int)$quiz->id,true);
            $hash=hash('sha256',QuestionDefinition::canonical(['actor'=>(int)$actor->id,'course'=>$course,'lesson'=>$lesson,'enrollment'=>$enrollment,'attempt'=>$attempt,'enrollment_revision'=>$enrollmentRevision,'attempt_revision'=>$attemptRevision,'minutes'=>$minutes,'reason'=>$reason,'impact'=>$impactHash]));
            $q=$this->db->createQuery()->select('id,request_hash')->from('#__jlms_quiz_deadline_extensions')->where('attempt_id = :attempt')->where('mutation_id = :mutation')
                ->bind(':attempt',$attempt,ParameterType::INTEGER)->bind(':mutation',$key);$q->setQuery((string)$q.' FOR UPDATE');$old=$this->db->setQuery($q)->loadObject();
            if($old){if(!hash_equals($old->request_hash,$hash))throw new \RuntimeException('COM_JLMS_QUIZ_CONFLICT',409);$this->db->transactionCommit();return(int)$old->id;}
            $p=$this->plan($e,$a,$minutes);
            if($p['blocked']!==null)throw new \RuntimeException($p['blocked'],409);
            if((int)$e->revision!==$enrollmentRevision||(int)$a->revision!==$attemptRevision||!hash_equals($p['hash'],$impactHash))throw new \RuntimeException('COM_JLMS_QUIZ_CONFLICT',409);
            $now=gmdate('Y-m-d H:i:s',($this->clock)());$new=gmdate('Y-m-d H:i:s',$p['new_deadline']);
            $record=(object)['attempt_id'=>$attempt,'revision_no'=>$p['extension_revision']+1,'previous_deadline'=>$a->deadline_at,'new_deadline'=>$new,'added_seconds'=>$minutes*60,
                'rules_hash'=>$p['rules_hash'],'reason'=>$reason,'actor_user_id'=>(int)$actor->id,'mutation_id'=>$key,'request_hash'=>$hash,'created_at'=>$now];$this->db->insertObject('#__jlms_quiz_deadline_extensions',$record,'id');
            $update=(object)['id'=>$attempt,'deadline_at'=>$new];$this->db->updateObject('#__jlms_attempts',$update,'id');
            $update=(object)['id'=>$enrollment,'revision'=>(int)$e->revision+1,'modified_by'=>(int)$actor->id,'modified_at'=>$now];$this->db->updateObject('#__jlms_enrollments',$update,'id');
            ($this->audit)($actor,['id'=>(int)$record->id,'actor_id'=>(int)$actor->id,'attempt_id'=>$attempt,'enrollment_id'=>$enrollment,'previous_deadline'=>$a->deadline_at,'new_deadline'=>$new,
                'added_seconds'=>$minutes*60,'reason_reference'=>(int)$record->id,'reason_code'=>'quiz_deadline_extended','correlation_id'=>bin2hex(random_bytes(16))]);
            $this->db->transactionCommit();return(int)$record->id;
        }catch(\Throwable $error){$this->db->transactionRollback();throw $error;}
    }
}
