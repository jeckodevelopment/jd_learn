<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition;

/** Append-only individual allowances. Published teaching rules and existing attempts are never edited. */
final class QuizTimeService
{
    public function __construct(private readonly DatabaseInterface $db,private readonly LearningService $learning,private readonly \Closure $audit) {}

    private function scope(User $identity,int $course,int $lesson,int $enrollment=0):array
    {
        $actor=$this->learning->freshActor($identity);$asset='com_jlms.course.'.$course;
        if(!$actor->authorise('core.manage','com_jlms')||!$actor->authorise('jlms.enrollments.manage',$asset)||!$actor->authorise('jlms.assessment.correct',$asset))throw new \RuntimeException('COM_JLMS_LEARNING_DENIED',403);
        $q=$this->db->createQuery()->select('q.*, l.title AS lesson_title, c.title AS course_title, c.id AS course_id, v.status AS version_status')
            ->from('#__jlms_quizzes AS q')->innerJoin('#__jlms_lessons AS l ON l.id=q.lesson_id AND l.course_version_id=q.course_version_id')
            ->innerJoin('#__jlms_course_versions AS v ON v.id=q.course_version_id')->innerJoin('#__jlms_courses AS c ON c.id=v.course_id')
            ->where("l.kind = 'quiz' AND v.status = 'published'")->where('c.id = :course')->where('q.lesson_id = :lesson')
            ->bind(':course',$course,ParameterType::INTEGER)->bind(':lesson',$lesson,ParameterType::INTEGER);
        $quiz=$this->db->setQuery($q)->loadObject()?:throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND',404);
        $e=null;
        if($enrollment){
            $version=(int)$quiz->course_version_id;
            $q=$this->db->createQuery()->select('e.*, u.name AS learner_name')->from('#__jlms_enrollments AS e')->innerJoin('#__users AS u ON u.id=e.user_id')
                ->where('e.id = :id')->where('e.course_id = :course')->where('e.course_version_id = :version')
                ->bind(':id',$enrollment,ParameterType::INTEGER)->bind(':course',$course,ParameterType::INTEGER)->bind(':version',$version,ParameterType::INTEGER);
            $e=$this->db->setQuery($q)->loadObject()?:throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND',404);
        }
        return [$actor,$quiz,$e];
    }

    /** Shared course-scoped authorization and parent validation for assessment administration. */
    public function managementContext(User $identity,int $course,int $lesson,int $enrollment):array
    {
        return $this->scope($identity,$course,$lesson,$enrollment);
    }

    /** Read under the caller's enrollment lock when preparing an attempt. No reason/actor enters the learner DTO. */
    public function allowance(int $enrollment,int $quiz):?object
    {
        $q=$this->db->createQuery()->select('*')->from('#__jlms_quiz_time_adjustments')->where('enrollment_id = :enrollment')->where('quiz_id = :quiz')
            ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quiz,ParameterType::INTEGER)->order('revision_no DESC');
        return $this->db->setQuery($q,0,1)->loadObject()?:null;
    }

    public function rules(array $rules,int $enrollment,int $quiz):array
    {
        $record=$this->allowance($enrollment,$quiz);$base=(int)$rules['time_seconds'];$extra=$record?(int)$record->extra_seconds:0;
        if($extra<0||$extra>86400||($base===0&&$extra!==0)||$base+$extra>172800)throw new \RuntimeException('COM_JLMS_QUIZ_TIME_INVALID',422);
        $rules['base_time_seconds']=$base;$rules['extra_time_seconds']=$extra;$rules['time_adjustment_id']=$record?(int)$record->id:null;
        $rules['time_seconds']=$base===0?0:$base+$extra;return $rules;
    }

    public function editor(User $identity,int $course,int $lesson,int $enrollment=0,int $after=0):array
    {
        [, $quiz,$e]=$this->scope($identity,$course,$lesson,$enrollment);$version=(int)$quiz->course_version_id;$quizId=(int)$quiz->id;
        $after=max(0,$after);
        $q=$this->db->createQuery()->select('e.id,e.status,u.name AS learner_name')->from('#__jlms_enrollments AS e')->innerJoin('#__users AS u ON u.id=e.user_id')
            ->where('e.course_id = :course')->where('e.course_version_id = :version')->where('e.id > :after')
            ->bind(':course',$course,ParameterType::INTEGER)->bind(':version',$version,ParameterType::INTEGER)->bind(':after',$after,ParameterType::INTEGER)->order('e.id');
        $candidates=$this->db->setQuery($q,0,21)->loadObjectList();$more=count($candidates)>20;$candidates=array_slice($candidates,0,20);
        $current=$e?$this->allowance($enrollment,$quizId):null;$history=[];$active=null;
        if($e){
            $q=$this->db->createQuery()->select('id,revision_no,extra_seconds,reason,actor_user_id,created_at')->from('#__jlms_quiz_time_adjustments')->where('enrollment_id = :enrollment')->where('quiz_id = :quiz')
                ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quizId,ParameterType::INTEGER)->order('revision_no DESC');$history=$this->db->setQuery($q,0,20)->loadObjectList();
            $q=$this->db->createQuery()->select('a.id,a.deadline_at,a.rules_snapshot_json')->from('#__jlms_active_attempts AS s')->innerJoin('#__jlms_attempts AS a ON a.id=s.attempt_id')
                ->where('s.enrollment_id = :enrollment')->where('s.quiz_id = :quiz')->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quizId,ParameterType::INTEGER);
            $active=$this->db->setQuery($q)->loadObject();
            if($active){$rules=json_decode($active->rules_snapshot_json,true,16,JSON_THROW_ON_ERROR);$active=(object)['id'=>(int)$active->id,'deadline_at'=>$active->deadline_at,'time_seconds'=>(int)$rules['time_seconds']];}
        }
        return ['course_id'=>$course,'lesson_id'=>$lesson,'quiz'=>$quiz,'enrollment'=>$e,'candidates'=>$candidates,'more'=>$more,'current'=>$current,'history'=>$history,'active'=>$active];
    }

    public function save(User $identity,int $course,int $lesson,int $enrollment,int $expected,int $minutes,string $reason,string $key):int
    {
        if($expected<0||$expected>10000||$minutes<0||$minutes>1440||!preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D',$key))throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400);
        $reason=trim($reason);
        if($reason===''||strlen($reason)>500||preg_match('/[\x00-\x1f\x7f<>]/',$reason)||!preg_match('//u',$reason))throw new \RuntimeException('COM_JLMS_QUIZ_TIME_REASON',422);
        $this->scope($identity,$course,$lesson,$enrollment);
        $this->db->transactionStart();
        try{
            // As for learner mutations: lock enrollment before fresh core authorization establishes the read view.
            $q=$this->db->createQuery()->select('id')->from('#__jlms_enrollments')->where('id = :id')->bind(':id',$enrollment,ParameterType::INTEGER);
            $q->setQuery((string)$q.' FOR UPDATE');$this->db->setQuery($q)->loadResult();
            [$actor,$quiz]=$this->scope($identity,$course,$lesson,$enrollment);$quizId=(int)$quiz->id;$seconds=$minutes*60;
            $hash=hash('sha256',QuestionDefinition::canonical(['actor'=>(int)$actor->id,'course'=>$course,'lesson'=>$lesson,'enrollment'=>$enrollment,'revision'=>$expected,'extra_seconds'=>$seconds,'reason'=>$reason]));
            $q=$this->db->createQuery()->select('id,request_hash')->from('#__jlms_quiz_time_adjustments')->where('enrollment_id = :enrollment')->where('quiz_id = :quiz')->where('mutation_id = :mutation')
                ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':quiz',$quizId,ParameterType::INTEGER)->bind(':mutation',$key);$q->setQuery((string)$q.' FOR UPDATE');$replay=$this->db->setQuery($q)->loadObject();
            if($replay){if(!hash_equals($replay->request_hash,$hash))throw new \RuntimeException('COM_JLMS_QUIZ_CONFLICT',409);$this->db->transactionCommit();return(int)$replay->id;}
            $current=$this->allowance($enrollment,$quizId);$revision=$current?(int)$current->revision_no:0;
            if($revision!==$expected||$revision>=10000)throw new \RuntimeException('COM_JLMS_QUIZ_CONFLICT',409);
            if($seconds>0&&(int)$quiz->time_limit_seconds===0)throw new \RuntimeException('COM_JLMS_QUIZ_TIME_INVALID',422);
            $record=(object)['enrollment_id'=>$enrollment,'course_version_id'=>(int)$quiz->course_version_id,'quiz_id'=>$quizId,'revision_no'=>$revision+1,
                'extra_seconds'=>$seconds,'reason'=>$reason,'actor_user_id'=>(int)$actor->id,'mutation_id'=>$key,'request_hash'=>$hash,'created_at'=>gmdate('Y-m-d H:i:s')];
            $this->db->insertObject('#__jlms_quiz_time_adjustments',$record,'id');
            ($this->audit)($actor,['id'=>(int)$record->id,'actor_id'=>(int)$actor->id,'enrollment_id'=>$enrollment,'quiz_id'=>$quizId,
                'before_seconds'=>$current?(int)$current->extra_seconds:0,'after_seconds'=>$seconds,'reason_code'=>'individual_quiz_time','reason_reference'=>(int)$record->id,'correlation_id'=>bin2hex(random_bytes(16))]);
            $this->db->transactionCommit();return(int)$record->id;
        }catch(\Throwable $error){$this->db->transactionRollback();throw $error;}
    }
}
