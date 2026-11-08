<?php
declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition;

/** Audited, course-scoped switches for new admissions and active delivery. */
final class CourseOperationsService
{
    private const FIELDS = ['admissions' => 'admissions_open', 'delivery' => 'delivery_enabled'];
    public function __construct(private readonly DatabaseInterface $db, private readonly EnrollmentRosterService $roster, private readonly \Closure $audit) {}

    private function preview(User $actor, int $courseId, string $operation): array
    {
        if (!isset(self::FIELDS[substr($operation, 0, strrpos($operation, '_'))])
            || !in_array($operation, ['admissions_close','admissions_open','delivery_suspend','delivery_resume'], true)) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        $course = $this->roster->course($actor, $courseId);
        $field = self::FIELDS[substr($operation, 0, strrpos($operation, '_'))];
        $target = str_ends_with($operation, '_open') || str_ends_with($operation, '_resume') ? 1 : 0;
        $q = $this->db->createQuery()->select('id,title,revision,' . $field . ' AS current_value')->from('#__jlms_courses')
            ->where('id=:course')->bind(':course',$courseId,ParameterType::INTEGER);
        $course = $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND',404);
        $q = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_enrollments')->where('course_id=:course')->where("status IN ('active','completed')")
            ->bind(':course',$courseId,ParameterType::INTEGER);
        $learners = (int)$this->db->setQuery($q)->loadResult();
        $q = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_attempts AS a')->innerJoin('#__jlms_enrollments AS e ON e.id=a.enrollment_id')
            ->where('e.course_id=:course')->where("a.status = 'open'")->bind(':course',$courseId,ParameterType::INTEGER);
        $attempts = (int)$this->db->setQuery($q)->loadResult();
        $state = ['course_id'=>$courseId,'revision'=>(int)$course->revision,'operation'=>$operation,'current'=>(int)$course->current_value,'target'=>$target,'learners'=>$learners,'open_attempts'=>$attempts];
        $state['hash'] = hash('sha256', QuestionDefinition::canonical($state));
        $state['blocked'] = $state['current'] === $target;
        return ['course'=>$course,'impact'=>$state];
    }

    public function editor(User $actor, int $courseId, string $operation): array
    {
        $item=$this->preview($actor,$courseId,$operation);
        $q=$this->db->createQuery()->select('revision_no,operation,previous_value,target_value,reason,actor_user_id,created_at')->from('#__jlms_course_operations')->where('course_id=:course')->bind(':course',$courseId,ParameterType::INTEGER)->order('revision_no DESC');
        $item['history']=$this->db->setQuery($q,0,20)->loadObjectList(); return $item;
    }

    public function apply(User $actor, int $courseId, string $operation, int $revision, string $reason, string $mutation, string $impactHash): void
    {
        $this->roster->course($actor, $courseId);
        $reason=trim($reason);
        if ($reason==='' || strlen($reason)>500 || preg_match('/[\x00-\x1f\x7f<>]/',$reason) || !preg_match('//u',$reason)
            || !preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D',$mutation)
            || !preg_match('/\A[a-f0-9]{64}\z/D',$impactHash)) throw new \RuntimeException('COM_JLMS_COURSE_OPERATION_REASON',422);
        $this->db->transactionStart();
        try {
            $q=$this->db->createQuery()->select('id')->from('#__jlms_courses')->where('id=:course')->bind(':course',$courseId,ParameterType::INTEGER);
            $q->setQuery((string)$q.' FOR UPDATE'); $this->db->setQuery($q)->loadResult();
            if (str_starts_with($operation,'admissions_')) {
                $q=$this->db->createQuery()->select('id')->from('#__jlms_editions')->where('course_id=:course')->bind(':course',$courseId,ParameterType::INTEGER)->order('id');
                $q->setQuery((string)$q.' FOR UPDATE'); $this->db->setQuery($q)->loadColumn();
            }
            $actor=new User((int)$actor->id); $actor->clearAccessRights();
            $item=$this->preview($actor,$courseId,$operation); $p=$item['impact'];
            $q=$this->db->createQuery()->select('id,actor_user_id,operation,impact_hash,reason')->from('#__jlms_course_operations')->where('mutation_id=:mutation')->bind(':mutation',$mutation);
            $prior=$this->db->setQuery($q)->loadObject();
            if ($prior) {
                if ((int)$prior->actor_user_id===(int)$actor->id && $prior->operation===$operation && hash_equals($prior->impact_hash,$impactHash) && hash_equals($prior->reason,$reason)) { $this->db->transactionCommit(); return; }
                throw new \RuntimeException('COM_JLMS_COURSE_OPERATION_CONFLICT',409);
            }
            if ($p['blocked'] || $p['revision']!==$revision || !hash_equals($p['hash'],$impactHash)) throw new \RuntimeException('COM_JLMS_COURSE_OPERATION_CONFLICT',409);
            $field=self::FIELDS[substr($operation,0,strrpos($operation,'_'))]; $target=(int)$p['target']; $actorId=(int)$actor->id; $now=gmdate('Y-m-d H:i:s.u');
            $q=$this->db->createQuery()->update('#__jlms_courses')->set($this->db->quoteName($field).'=:target')->set('revision=revision+1')->set('modified_by=:actor')->set('modified_at=:now')->where('id=:course')->where('revision=:revision')
                ->bind(':target',$target,ParameterType::INTEGER)->bind(':actor',$actorId,ParameterType::INTEGER)->bind(':now',$now)->bind(':course',$courseId,ParameterType::INTEGER)->bind(':revision',$revision,ParameterType::INTEGER);
            $this->db->setQuery($q)->execute(); if ($this->db->getAffectedRows()!==1) throw new \RuntimeException('COM_JLMS_COURSE_OPERATION_CONFLICT',409);
            $q=$this->db->createQuery()->select('COALESCE(MAX(revision_no),0)+1')->from('#__jlms_course_operations')->where('course_id=:course')->bind(':course',$courseId,ParameterType::INTEGER);
            $sequence=(int)$this->db->setQuery($q)->loadResult();
            $record=(object)['course_id'=>$courseId,'revision_no'=>$sequence,'operation'=>$operation,'previous_value'=>(int)$p['current'],'target_value'=>$target,'impact_hash'=>$impactHash,'reason'=>$reason,'actor_user_id'=>(int)$actor->id,'mutation_id'=>$mutation,'created_at'=>$now];
            $this->db->insertObject('#__jlms_course_operations',$record,'id');
            ($this->audit)($actor,['id'=>(int)$record->id,'actor_id'=>(int)$actor->id,'course_id'=>$courseId,'operation'=>$operation,
                'previous_value'=>(int)$p['current'],'target_value'=>$target,'reason_reference'=>(int)$record->id,'correlation_id'=>bin2hex(random_bytes(16))]);
            $this->db->transactionCommit();
        } catch (\Throwable $e) { $this->db->transactionRollback(); throw $e; }
    }
}
