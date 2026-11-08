<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition;

/** Explicit, single-enrollment migration between immutable published course versions. */
final class EnrollmentMigrationService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly LearningService $learning,
        private readonly EnrollmentRosterService $roster, private readonly \Closure $audit) {}

    private function access(User $identity, int $course): User
    {
        $actor = $this->learning->freshActor($identity);
        $this->roster->course($actor, $course);
        if (!$actor->authorise('jlms.enrollments.migrate', 'com_jlms.course.' . $course)) {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        return $actor;
    }

    private function versions(int $course, int $source, int $target): array
    {
        $q = $this->db->createQuery()->select('id, course_id, version_no, status, title_snapshot')
            ->from('#__jlms_course_versions')->where('course_id = :course')
            ->where('id IN (:source, :target)')
            ->bind(':course', $course, ParameterType::INTEGER)->bind(':source', $source, ParameterType::INTEGER)
            ->bind(':target', $target, ParameterType::INTEGER);
        $rows = $this->db->setQuery($q)->loadObjectList('id');
        if (!isset($rows[$source], $rows[$target])) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        if (!in_array($rows[$source]->status, ['published', 'retired'], true) || $rows[$target]->status !== 'published' || $source === $target) {
            throw new \RuntimeException('COM_JLMS_MIGRATION_VERSION_INVALID', 409);
        }
        $q = $this->db->createQuery()->select('current_version_id')->from('#__jlms_courses')->where('id = :course')
            ->bind(':course', $course, ParameterType::INTEGER);
        if ((int) $this->db->setQuery($q)->loadResult() !== $target) { throw new \RuntimeException('COM_JLMS_MIGRATION_TARGET_NOT_CURRENT', 409); }
        return [$rows[$source], $rows[$target]];
    }

    /** Preview only. Mapping is a list of source_lesson_id => target_lesson_id pairs. */
    public function preview(User $identity, int $course, int $enrollment, array $mapping = [], bool $useSavedMappings = false): array
    {
        $actor = $this->access($identity, $course);
        if ($enrollment < 1 || count($mapping) > 100) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        $q = $this->db->createQuery()->select('e.*, u.name AS learner_name, v.title_snapshot AS source_title, v.version_no AS source_version_no')
            ->from('#__jlms_enrollments AS e')->leftJoin('#__users AS u ON u.id=e.user_id')
            ->innerJoin('#__jlms_course_versions AS v ON v.id=e.course_version_id AND v.course_id=e.course_id')
            ->where('e.id=:enrollment')->where('e.course_id=:course')
            ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':course',$course,ParameterType::INTEGER);
        $source = $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND',404);
        if (!in_array($source->status, ['active','completed','suspended'], true) || !(int) $source->user_id) {
            throw new \RuntimeException('COM_JLMS_MIGRATION_SOURCE_INVALID',409);
        }
        $q = $this->db->createQuery()->select('current_version_id, delivery_enabled')->from('#__jlms_courses')->where('id=:course')
            ->bind(':course',$course,ParameterType::INTEGER);
        $courseRecord=$this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND',404);
        $targetVersion = (int)$courseRecord->current_version_id;
        if (!$targetVersion || $targetVersion === (int)$source->course_version_id) { throw new \RuntimeException('COM_JLMS_MIGRATION_TARGET_NOT_CURRENT',409); }
        [$sourceVersion, $target] = $this->versions($course, (int)$source->course_version_id, $targetVersion);
        $defaultEditionCode='default-v'.(int)$target->version_no;
        $q=$this->db->createQuery()->select('id,status,starts_at,ends_at,capacity')->from('#__jlms_editions')
            ->where('course_id=:course')->where('course_version_id=:version')->where('code=:code')
            ->bind(':course',$course,ParameterType::INTEGER)->bind(':version',$targetVersion,ParameterType::INTEGER)->bind(':code',$defaultEditionCode);
        $targetEdition=$this->db->setQuery($q)->loadObject();
        $now=(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
        $q = $this->db->createQuery()->select('id, title, kind, required, source_revision')->from('#__jlms_lessons')
            ->where('course_version_id=:version')->where("kind <> 'quiz'")->order('ordering,id')
            ->bind(':version',$source->course_version_id,ParameterType::INTEGER);
        $sourceLessons = $this->db->setQuery($q,0,5001)->loadObjectList('id');
        if (count($sourceLessons)>5000) { throw new \RuntimeException('COM_JLMS_MIGRATION_LIMIT',409); }
        $q = $this->db->createQuery()->select('id, title, kind, required, source_revision')->from('#__jlms_lessons')
            ->where('course_version_id=:version')->where("kind <> 'quiz'")->order('ordering,id')
            ->bind(':version',$targetVersion,ParameterType::INTEGER);
        $targetLessons = $this->db->setQuery($q,0,5001)->loadObjectList('id');
        if (count($targetLessons)>5000) { throw new \RuntimeException('COM_JLMS_MIGRATION_LIMIT',409); }
        $q = $this->db->createQuery()->select('p.lesson_id,p.state,p.completed_at,l.title,l.kind')
            ->from('#__jlms_lesson_progress AS p')->innerJoin('#__jlms_lessons AS l ON l.id=p.lesson_id AND l.course_version_id=p.course_version_id')
            ->where('p.enrollment_id=:enrollment')->where('p.course_version_id=:version')->where("p.state='completed'")
            ->where("l.kind <> 'quiz'")->order('p.lesson_id')
            ->bind(':enrollment',$enrollment,ParameterType::INTEGER)->bind(':version',$source->course_version_id,ParameterType::INTEGER);
        $completed = $this->db->setQuery($q,0,1001)->loadObjectList();
        if (count($completed)>1000) { throw new \RuntimeException('COM_JLMS_MIGRATION_LIMIT',409); }
        if ($mapping === [] && $useSavedMappings) {
            $completedSet=array_fill_keys(array_map(static fn(object $row):int=>(int)$row->lesson_id,$completed),true);
            $q=$this->db->createQuery()->select('e.source_lesson_id,e.target_lesson_id')->from('#__jlms_version_equivalences AS e')
                ->innerJoin('#__jlms_lessons AS s ON s.id=e.source_lesson_id')->innerJoin('#__jlms_lessons AS t ON t.id=e.target_lesson_id')
                ->where('s.course_version_id=:source')->where('t.course_version_id=:target')
                ->bind(':source',$source->course_version_id,ParameterType::INTEGER)->bind(':target',$targetVersion,ParameterType::INTEGER);
            foreach ($this->db->setQuery($q,0,1001)->loadObjectList() as $eq) {
                if (isset($completedSet[(int)$eq->source_lesson_id])) { $mapping[(int)$eq->source_lesson_id]=(int)$eq->target_lesson_id; }
            }
            if (count($mapping)>100) { throw new \RuntimeException('COM_JLMS_MIGRATION_LIMIT',409); }
        }
        $completedIds=[];
        foreach ($completed as $row) { $completedIds[(int)$row->lesson_id]=true; }
        $normal = []; $usedTargets = [];
        foreach ($mapping as $from => $to) {
            if ((!is_int($from) && (!is_string($from) || !preg_match('/\A[1-9][0-9]{0,18}\z/D',$from)))
                || (!is_int($to) && (!is_string($to) || !preg_match('/\A(?:0|[1-9][0-9]{0,18})\z/D',$to)))) {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400);
            }
            $from=(int)$from; $to=(int)$to;
            if ($to===0) { continue; }
            if (!isset($sourceLessons[$from],$targetLessons[$to]) || !isset($completedIds[$from]) || isset($normal[$from]) || isset($usedTargets[$to])) {
                throw new \RuntimeException('COM_JLMS_MIGRATION_MAPPING_INVALID',422);
            }
            $normal[$from]=$to; $usedTargets[$to]=true;
        }
        ksort($normal,SORT_NUMERIC);
        $eligible = []; $missing = [];
        foreach ($completed as $progress) {
            $id=(int)$progress->lesson_id;
            if (!isset($normal[$id])) { $missing[]=['lesson_id'=>$id,'title'=>$progress->title]; continue; }
            $dest=$targetLessons[$normal[$id]];
            $eligible[]=['source_lesson_id'=>$id,'source_title'=>$progress->title,'target_lesson_id'=>(int)$dest->id,
                'target_title'=>$dest->title,'required'=>(bool)$dest->required,'completed_at'=>$progress->completed_at];
        }
        $mappingConflict=false;
        foreach ($normal as $from=>$to) {
            $q=$this->db->createQuery()->select('e.target_lesson_id')->from('#__jlms_version_equivalences AS e')
                ->innerJoin('#__jlms_lessons AS t ON t.id=e.target_lesson_id')->where('e.source_lesson_id=:source')
                ->where('t.course_version_id=:version')->where('e.target_lesson_id<>:target')
                ->bind(':source',$from,ParameterType::INTEGER)->bind(':version',$targetVersion,ParameterType::INTEGER)->bind(':target',$to,ParameterType::INTEGER);
            if ($this->db->setQuery($q)->loadResult()) { $mappingConflict=true; break; }
        }
        $q = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_active_attempts')
            ->where('enrollment_id=:enrollment')->bind(':enrollment',$enrollment,ParameterType::INTEGER);
        $openAttempts=(int)$this->db->setQuery($q)->loadResult();
        $q = $this->db->createQuery()->select('id')->from('#__jlms_enrollments')
            ->where('subject_key=:subject')->where('course_version_id=:version')
            ->bind(':subject',$source->subject_key)->bind(':version',$targetVersion,ParameterType::INTEGER);
        $existingTarget=(int)$this->db->setQuery($q)->loadResult();
        $editionFull=false;
        if ($targetEdition && $targetEdition->capacity!==null) {
            $editionId=(int)$targetEdition->id;
            $q=$this->db->createQuery()->select('COUNT(*)')->from('#__jlms_enrollments')->where('edition_id=:edition')->where("status <> 'cancelled'")
                ->bind(':edition',$editionId,ParameterType::INTEGER);
            $editionFull=(int)$this->db->setQuery($q)->loadResult()>=(int)$targetEdition->capacity;
        }
        $editionUnavailable=!$targetEdition || $targetEdition->status!=='open'
            || ($targetEdition->starts_at!==null && $targetEdition->starts_at>$now) || ($targetEdition->ends_at!==null && $targetEdition->ends_at<=$now);
        $plan=['course_id'=>$course,'source_enrollment_id'=>$enrollment,'source_version_id'=>(int)$source->course_version_id,
            'target_version_id'=>$targetVersion,'target_version_no'=>(int)$target->version_no,'source_version_no'=>(int)$sourceVersion->version_no,
            'source_status'=>$source->status,'source_revision'=>(int)$source->revision,'user_id'=>(int)$source->user_id,
            'target_edition_id'=>(int)($targetEdition->id??0),
            'learner_name'=>(string)($source->learner_name??''),'mapping'=>$normal,'transfers'=>$eligible,'unmapped'=>$missing,
            'open_attempts'=>$openAttempts,'existing_target_id'=>$existingTarget,
            'blocked'=>$openAttempts?'COM_JLMS_MIGRATION_OPEN_ATTEMPT':($existingTarget?'COM_JLMS_MIGRATION_TARGET_EXISTS':($mappingConflict?'COM_JLMS_MIGRATION_MAPPING_CONFLICT':(!$courseRecord->delivery_enabled?'COM_JLMS_COURSE_DELIVERY_SUSPENDED':($editionUnavailable?'COM_JLMS_MIGRATION_EDITION_UNAVAILABLE':($editionFull?'COM_JLMS_MIGRATION_EDITION_FULL':null)))))];
        $plan['hash']=hash('sha256',QuestionDefinition::canonical($plan));
        $plan['completed_source_lessons']=array_map(static fn(object $row): array=>['id'=>(int)$row->lesson_id,'title'=>$row->title],$completed);
        $plan['target_lessons']=array_map(static fn(object $row): array=>['id'=>(int)$row->id,'title'=>$row->title,'required'=>(bool)$row->required],array_values($targetLessons));
        return $plan;
    }

    /** @return int target enrollment id */
    public function apply(User $identity,int $course,int $enrollment,int $expectedRevision,array $mapping,
        string $reason,string $mutationId,string $impactHash,bool $confirmed): int
    {
        $actor=$this->access($identity,$course);
        if (!$confirmed || $expectedRevision<1 || !preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D',$mutationId)
            || !preg_match('/\A[a-f0-9]{64}\z/D',$impactHash)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400); }
        $reason=trim($reason);
        if ($reason==='' || strlen($reason)>500 || preg_match('/[\x00-\x1f\x7f<>]/',$reason) || !preg_match('//u',$reason)) {
            throw new \RuntimeException('COM_JLMS_MIGRATION_REASON',422);
        }
        if (count($mapping) > 100) { throw new \RuntimeException('COM_JLMS_MIGRATION_LIMIT', 409); }
        $validatedMapping = [];
        foreach ($mapping as $from => $to) {
            if ((!is_int($from) && (!is_string($from) || !preg_match('/\\A[1-9][0-9]{0,18}\\z/D', $from)))
                || (!is_int($to) && (!is_string($to) || !preg_match('/\\A[1-9][0-9]{0,18}\\z/D', $to)))) {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
            }
            $validatedMapping[(int) $from] = (int) $to;
        }
        $mapping = $validatedMapping;
        ksort($mapping, SORT_NUMERIC);
        $requestHash=hash('sha256',QuestionDefinition::canonical(['actor'=>(int)$actor->id,'course'=>$course,'enrollment'=>$enrollment,
            'revision'=>$expectedRevision,'mapping'=>$mapping,'reason'=>$reason,'impact_hash'=>$impactHash]));
        $scopeHash=hash('sha256','enrollment-migration:'.$actor->id.':'.$course.':'.$enrollment);
        $this->db->transactionStart();
        try {
            $q=$this->db->createQuery()->select('id')->from('#__jlms_courses')->where('id=:course')->bind(':course',$course,ParameterType::INTEGER);
            $q->setQuery((string)$q.' FOR UPDATE');
            if (!$this->db->setQuery($q)->loadResult()) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND',404); }
            $q=$this->db->createQuery()->select('id')->from('#__jlms_enrollments')->where('id=:id')->where('course_id=:course')
                ->bind(':id',$enrollment,ParameterType::INTEGER)->bind(':course',$course,ParameterType::INTEGER);
            $q->setQuery((string)$q.' FOR UPDATE');
            if (!$this->db->setQuery($q)->loadResult()) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND',404); }
            $q=$this->db->createQuery()->select('request_hash,result_id')->from('#__jlms_idempotency')->where('scope_hash=:scope')->bind(':scope',$scopeHash);
            $old=$this->db->setQuery($q)->loadObject();
            if ($old) {
                if (!hash_equals($old->request_hash,$requestHash) || !(int)$old->result_id) { throw new \RuntimeException('COM_JLMS_MIGRATION_CONFLICT',409); }
                $this->db->transactionCommit(); return (int)$old->result_id;
            }
            $plan=$this->preview($identity,$course,$enrollment,$mapping,false);
            if ($plan['blocked']!==null) { throw new \RuntimeException($plan['blocked'],409); }
            $q=$this->db->createQuery()->select('id')->from('#__jlms_editions')->where('id=:edition')->where('course_id=:course')->where('course_version_id=:version')
                ->bind(':edition',$plan['target_edition_id'],ParameterType::INTEGER)->bind(':course',$course,ParameterType::INTEGER)->bind(':version',$plan['target_version_id'],ParameterType::INTEGER);
            $q->setQuery((string)$q.' FOR UPDATE');
            if (!$this->db->setQuery($q)->loadResult()) { throw new \RuntimeException('COM_JLMS_MIGRATION_EDITION_MISSING',409); }
            $plan=$this->preview($identity,$course,$enrollment,$mapping,false);
            if ($plan['blocked']!==null) { throw new \RuntimeException($plan['blocked'],409); }
            if ($plan['source_revision']!==$expectedRevision || !hash_equals($plan['hash'],$impactHash)) { throw new \RuntimeException('COM_JLMS_MIGRATION_CONFLICT',409); }
            $now=gmdate('Y-m-d H:i:s');
            $q=$this->db->createQuery()->select('*')->from('#__jlms_enrollments')->where('id=:id')->bind(':id',$enrollment,ParameterType::INTEGER);
            $source=$this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND',404);
            $editionId=$plan['target_edition_id'];
            if (!$editionId) { throw new \RuntimeException('COM_JLMS_MIGRATION_EDITION_MISSING',409); }
            $q=$this->db->createQuery()->select('COUNT(*)')->from('#__jlms_lessons')->where('course_version_id=:version')->where('required=1')
                ->bind(':version',$plan['target_version_id'],ParameterType::INTEGER);
            $required=(int)$this->db->setQuery($q)->loadResult();
            $completedRequired=count(array_filter($plan['transfers'],static fn(array $row):bool=>$row['required']));
            $targetStatus=$source->status==='suspended'?'suspended':($required>0 && $completedRequired >= $required ? 'completed':'active');
            $target=(object)['user_id'=>(int)$source->user_id,'subject_key'=>$source->subject_key,'course_id'=>$course,
                'course_version_id'=>$plan['target_version_id'],'edition_id'=>$editionId,'status'=>$targetStatus,'origin'=>'migration',
                'required_total'=>$required,'completed_total'=>$completedRequired,'started_at'=>$source->started_at ?: $now,
                'completed_at'=>$targetStatus==='completed'?$now:null,'last_activity_at'=>$now,'last_lesson_id'=>null,
                'starts_at'=>$source->starts_at,'ends_at'=>$source->ends_at,'revision'=>1,'created_by'=>(int)$actor->id,
                'created_at'=>$now,'modified_by'=>(int)$actor->id,'modified_at'=>$now];
            $this->db->insertObject('#__jlms_enrollments',$target,'id'); $targetId=(int)$target->id;
            $snapshot=['mapping'=>$plan['mapping'],'transfers'=>$plan['transfers'],'unmapped'=>$plan['unmapped'],
                'source_version_id'=>$plan['source_version_id'],'target_version_id'=>$plan['target_version_id'],'reason'=>$reason];
            $job=(object)['kind'=>'enrollment_migration','owner_user_id'=>(int)$actor->id,
                'scope_json'=>json_encode(['course_id'=>$course,'source_enrollment_id'=>$enrollment],JSON_THROW_ON_ERROR),
                'payload_json'=>json_encode($snapshot,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'status'=>'succeeded',
                'cursor_json'=>json_encode(['source_enrollment_id'=>$enrollment,'target_enrollment_id'=>$targetId],JSON_THROW_ON_ERROR),
                'progress_done'=>1,'progress_total'=>1,'attempts'=>1,'available_at'=>$now,'created_at'=>$now,'finished_at'=>$now];
            $this->db->insertObject('#__jlms_jobs',$job,'id');
            $idempotency=(object)['scope_hash'=>$scopeHash,'request_hash'=>$requestHash,'state'=>'completed','result_type'=>'enrollment_migration',
                'result_id'=>$targetId,'result_json'=>json_encode(['target_enrollment_id'=>$targetId],JSON_THROW_ON_ERROR),
                'http_status'=>200,'created_at'=>$now,'expires_at'=>gmdate('Y-m-d H:i:s',time()+172800)];
            $this->db->insertObject('#__jlms_idempotency',$idempotency);
            foreach ($plan['mapping'] as $from=>$to) {
                $sourceLessonId=(int)$from; $targetLessonId=(int)$to;
                $q=$this->db->createQuery()->select('COUNT(*)')->from('#__jlms_version_equivalences')
                    ->where('source_lesson_id=:source')->where('target_lesson_id=:target')
                    ->bind(':source',$sourceLessonId,ParameterType::INTEGER)->bind(':target',$targetLessonId,ParameterType::INTEGER);
                if (!(int)$this->db->setQuery($q)->loadResult()) {
                    $equivalence=(object)['source_lesson_id'=>(int)$from,'target_lesson_id'=>(int)$to,
                        'policy_json'=>json_encode(['transfer'=>'completion_only'],JSON_THROW_ON_ERROR),
                        'approved_by'=>(int)$actor->id,'approved_at'=>$now];
                    $this->db->insertObject('#__jlms_version_equivalences',$equivalence);
                }
            }
            foreach ($plan['transfers'] as $row) {
                $progress=(object)['enrollment_id'=>$targetId,'course_version_id'=>$plan['target_version_id'],'lesson_id'=>$row['target_lesson_id'],
                    'source_revision'=>1,'coverage_json'=>'[]','covered_ms'=>0,'resume_ms'=>0,'state'=>'completed',
                    'completed_at'=>$row['completed_at'] ?: $now,'last_activity_at'=>$now,'revision'=>1];
                $q=$this->db->createQuery()->select('source_revision')->from('#__jlms_lessons')->where('id=:lesson')->bind(':lesson',$row['target_lesson_id'],ParameterType::INTEGER);
                $progress->source_revision=(int)$this->db->setQuery($q)->loadResult();
                $this->db->insertObject('#__jlms_lesson_progress',$progress);
            }
            $migration=(object)['source_enrollment_id'=>$enrollment,'target_enrollment_id'=>$targetId,'job_id'=>(int)$job->id,
                'mapping_snapshot_json'=>json_encode($snapshot,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'created_at'=>$now];
            $this->db->insertObject('#__jlms_enrollment_migrations',$migration);
            $migratedStatus='migrated'; $actorId=(int)$actor->id;
            $q=$this->db->createQuery()->update('#__jlms_enrollments')->set('status=:status')->set('revision=revision+1')->set('modified_by=:actor')->set('modified_at=:now')
                ->where('id=:id')->bind(':status',$migratedStatus)->bind(':actor',$actorId,ParameterType::INTEGER)->bind(':now',$now)
                ->bind(':id',$enrollment,ParameterType::INTEGER);
            $this->db->setQuery($q)->execute();
            ($this->audit)($actor,['id'=>(int)$job->id,'actor_id'=>(int)$actor->id,'course_id'=>$course,
                'source_enrollment_id'=>$enrollment,'target_enrollment_id'=>$targetId,'target_version_id'=>$plan['target_version_id'],
                'transfer_count'=>count($plan['transfers']),'reason_reference'=>(int)$job->id,
                'reason_code'=>'course_version_enrollment_migrated','correlation_id'=>bin2hex(random_bytes(16))]);
            $this->db->transactionCommit();
            return $targetId;
        } catch (\Throwable $error) { $this->db->transactionRollback(); throw $error; }
    }
}
