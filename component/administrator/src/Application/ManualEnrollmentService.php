<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Application;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition;

/** Single-user administrative admission to the current default edition. No group grants or implicit reset. */
final class ManualEnrollmentService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly LearningService $learning,
        private readonly CatalogService $catalog, private readonly EnrollmentRosterService $roster, private readonly \Closure $audit) {}

    /** Administrative assignment does not grant viewing rights. Editorial availability is still mandatory. */
    private function assignmentCourse(int $courseId, string $language): object
    {
        $q = $this->db->createQuery()->select('c.id, c.title, c.catid, c.language, c.content_access, c.current_version_id, c.enrollment_mode, c.admissions_open')
            ->from('#__jlms_courses AS c')
            ->innerJoin('#__jlms_course_versions AS v ON v.id = c.current_version_id AND v.course_id = c.id')
            ->innerJoin('#__categories AS cat ON cat.id = c.catid')
            ->innerJoin('#__viewlevels AS cv ON cv.id = c.catalog_access')
            ->innerJoin('#__viewlevels AS lv ON lv.id = c.content_access')
            ->where('c.id = :course')->where('c.state = 1')->where("v.status = 'published'")
            ->where("cat.extension = 'com_jlms.course' AND cat.published = 1")
            ->where("(c.language = '*' OR c.language = :language)")
            ->where('(c.publish_up IS NULL OR c.publish_up <= :nowUp)')->where('(c.publish_down IS NULL OR c.publish_down > :nowDown)')
            ->bind(':course', $courseId, ParameterType::INTEGER)->bind(':language', $language);
        $now = gmdate('Y-m-d H:i:s'); $q->bind(':nowUp', $now)->bind(':nowDown', $now);
        // Unlike catalog access, lack of a viewing level is permitted only by the explicit command flag.
        // Missing levels, unpublished ancestors or language mismatches are invalid editorial configuration.
        $ancestor = $this->db->createQuery()->select('1')->from('#__categories AS ancestor')
            ->where('ancestor.lft <= cat.lft AND ancestor.rgt >= cat.rgt AND ancestor.id <> 1')
            ->where("(ancestor.published <> 1 OR (ancestor.language <> '*' AND ancestor.language <> :ancestorLanguage) OR NOT EXISTS (SELECT 1 FROM #__viewlevels AS av WHERE av.id = ancestor.access))");
        $q->where('NOT EXISTS (' . $ancestor . ')')->bind(':ancestorLanguage', $language);
        return $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
    }

    private function context(User $identity, int $courseId, int $userId, string $language, bool $preassignment = false): array
    {
        $actor = $this->learning->freshActor($identity);
        $this->roster->course($actor, $courseId);
        if ($userId < 1 || !preg_match('/\A[a-z]{2,3}-[A-Z]{2}\z/D', $language)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        if (!isset(\Joomla\CMS\Language\LanguageHelper::getInstalledLanguages(0)[$language])) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        $learner = new User($userId);
        if (!(int) $learner->id) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        $learner = $this->learning->freshActor($learner);
        $languageAllowed = isset(LearningLanguages::available($learner, $language)[$language]);
        if (!$preassignment) {
            // Preserve the default admission policy and legacy command/hash format.
            if (!$languageAllowed) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
            $course = $this->catalog->course($learner, $language, $courseId);
            $hasAccess = in_array((int) $course->content_access, array_map('intval', $learner->getAuthorisedViewLevels()), true);
            if (!$hasAccess) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
        } else {
            $course = $this->assignmentCourse($courseId, $language);
            if (\Joomla\CMS\Language\Multilanguage::isEnabled()) {
                $q = $this->db->createQuery()->select('lang.access')->from('#__languages AS lang')
                    ->where('lang.lang_code = :language')->where('lang.published = 1')->bind(':language', $language)
                    ->where('(lang.access = 0 OR EXISTS (SELECT 1 FROM #__viewlevels AS validLevel WHERE validLevel.id = lang.access))');
                if ($this->db->setQuery($q)->loadResult() === null) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
            }
            $hasAccess = false;
            try {
                $visible = $this->catalog->course($learner, $language, $courseId);
                $hasAccess = $languageAllowed && in_array((int) $visible->content_access, array_map('intval', $learner->getAuthorisedViewLevels()), true);
            } catch (\RuntimeException $error) {
                if ($error->getCode() !== 404 || $error->getMessage() !== 'JERROR_LAYOUT_PAGE_NOT_FOUND') { throw $error; }
            }
        }
        // Approval is a distinct workflow; this increment does not bypass pending requests.
        if (!in_array($course->enrollment_mode, ['auto', 'explicit'], true)) { throw new \RuntimeException('COM_JLMS_MANUAL_MODE_PENDING', 409); }
        if (!(int) $course->admissions_open) { throw new \RuntimeException('COM_JLMS_COURSE_ADMISSIONS_CLOSED', 409); }
        $versionId = (int) $course->current_version_id;
        $q = $this->db->createQuery()->select('id, version_no, revision, rules_json')->from('#__jlms_course_versions')
            ->where('id = :version')->where('course_id = :course')->where("status = 'published'")
            ->bind(':version', $versionId, ParameterType::INTEGER)->bind(':course', $courseId, ParameterType::INTEGER);
        $version = $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        try { $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode((string) $version->rules_json); }
        catch (\DomainException) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
        $code = 'default-v' . (int) $version->version_no;
        $q = $this->db->createQuery()->select('id, revision, starts_at, ends_at, status, capacity')->from('#__jlms_editions')
            ->where('course_id = :course')->where('course_version_id = :version')->where('code = :code')
            ->bind(':course', $courseId, ParameterType::INTEGER)->bind(':version', $versionId, ParameterType::INTEGER)->bind(':code', $code);
        $edition = $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
        if ($edition->status !== 'open' || ($edition->starts_at !== null && $edition->starts_at > $now) || ($edition->ends_at !== null && $edition->ends_at <= $now)) { throw new \RuntimeException('COM_JLMS_LEARNING_WINDOW', 403); }
        $editionId = (int) $edition->id;
        $this->learning->assertEnrollmentScheduleReachable($courseId, $versionId, $editionId,
            $edition->starts_at, $edition->ends_at);
        $q = $this->db->createQuery()->select('id, status')->from('#__jlms_enrollments')->where('user_id = :user')->where('edition_id = :edition')
            ->bind(':user', $userId, ParameterType::INTEGER)->bind(':edition', $editionId, ParameterType::INTEGER);
        $existing = $this->db->setQuery($q)->loadObject();
        $q = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_enrollments')->where('edition_id = :edition')->where("status <> 'cancelled'")->bind(':edition', $editionId, ParameterType::INTEGER);
        $used = (int) $this->db->setQuery($q)->loadResult();
        $q = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_lessons')->where('course_version_id = :version')->where($this->db->quoteName('required') . ' = 1')->bind(':version', $versionId, ParameterType::INTEGER);
        $total = (int) $this->db->setQuery($q)->loadResult();
        if ($total < 1 && $rules['mode'] !== 'consultation') { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
        $p = ['course_id' => $courseId, 'user_id' => $userId, 'language' => $language, 'version_id' => $versionId, 'version_revision' => (int) $version->revision,
            'edition_id' => $editionId, 'edition_revision' => (int) $edition->revision, 'starts_at' => $edition->starts_at, 'ends_at' => $edition->ends_at,
            'capacity' => $edition->capacity === null ? null : (int) $edition->capacity, 'used' => $used, 'required_total' => $total,
            'existing_id' => $existing ? (int) $existing->id : null, 'existing_status' => $existing->status ?? null,
            'blocked' => $existing ? 'COM_JLMS_MANUAL_EXISTS' : ($edition->capacity !== null && $used >= (int) $edition->capacity ? 'COM_JLMS_EDITION_FULL' : null)];
        $hashPlan = $p;
        if ($preassignment) { $hashPlan['preassignment'] = ['requested' => true, 'has_current_access' => $hasAccess]; }
        $p['hash'] = hash('sha256', QuestionDefinition::canonical($hashPlan));
        $p['preassignment'] = $preassignment;
        $p['has_current_access'] = $hasAccess;
        return [$actor, $learner, $course, $p];
    }

    public function editor(User $identity, int $course, int $user, string $language, bool $preassignment = false): array
    {
        [, $learner, $c, $p] = $this->context($identity, $course, $user, $language, $preassignment);
        $receipt = null;
        if ($p['existing_id']) {
            $id = $p['existing_id'];
            $q = $this->db->createQuery()->select('id, reason, actor_user_id, created_at')->from('#__jlms_manual_enrollments')->where('enrollment_id = :id')->bind(':id', $id, ParameterType::INTEGER);
            $receipt = $this->db->setQuery($q)->loadObject();
        }
        return ['course' => (object) ['id' => (int) $c->id, 'title' => $c->title], 'learner_name' => $learner->name,
            'username' => $learner->username, 'impact' => $p, 'receipt' => $receipt];
    }

    public function apply(User $identity, int $course, int $user, string $language, string $reason, string $key, string $impact, bool $confirmed, bool $preassignment = false): int
    {
        $reason = trim($reason);
        if (!$confirmed || !preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D', $key) || !preg_match('/\A[a-f0-9]{64}\z/D', $impact)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        if ($reason === '' || strlen($reason) > 500 || preg_match('/[\x00-\x1f\x7f<>]/', $reason) || !preg_match('//u', $reason)) { throw new \RuntimeException('COM_JLMS_MANUAL_REASON', 422); }
        [, , , $pre] = $this->context($identity, $course, $user, $language, $preassignment);
        $edition = $pre['edition_id'];
        $this->db->transactionStart();
        try {
            // Same serialization point as automatic start, before fresh authorization establishes a read view.
            $q = $this->db->createQuery()->select('id')->from('#__jlms_editions')->where('id = :id')->bind(':id', $edition, ParameterType::INTEGER);
            $q->setQuery((string) $q . ' FOR UPDATE'); $this->db->setQuery($q)->loadResult();
            [$actor, $learner, $courseRecord, $p] = $this->context($identity, $course, $user, $language, $preassignment);
            if ($p['edition_id'] !== $edition) { throw new \RuntimeException('COM_JLMS_MANUAL_CONFLICT', 409); }
            $payload = ['actor' => (int) $actor->id, 'course' => $course, 'user' => $user, 'language' => $language, 'reason' => $reason, 'impact' => $impact];
            if ($preassignment) { $payload['preassignment'] = true; }
            $hash = hash('sha256', QuestionDefinition::canonical($payload));
            $nonce = bin2hex(random_bytes(16)); $now = gmdate('Y-m-d H:i:s'); $actorId = (int) $actor->id;
            // Claim the UUID in the same transaction. No committed receipt may remain without a result.
            $q = $this->db->createQuery();
            $q->setQuery('INSERT INTO #__jlms_manual_enrollments (actor_user_id,mutation_id,request_hash,claim_token,impact_hash,reason,created_at) VALUES (:actor,:mutation,:hash,:claim,:impact,:reason,:now) ON DUPLICATE KEY UPDATE id=id')
                ->bind(':actor', $actorId, ParameterType::INTEGER)->bind(':mutation', $key)->bind(':hash', $hash)->bind(':claim', $nonce)->bind(':impact', $impact)->bind(':reason', $reason)->bind(':now', $now);
            $this->db->setQuery($q)->execute();
            $q = $this->db->createQuery()->select('id, enrollment_id, request_hash, claim_token')->from('#__jlms_manual_enrollments')->where('mutation_id = :mutation')->bind(':mutation', $key);
            $q->setQuery((string) $q . ' FOR UPDATE'); $receipt = $this->db->setQuery($q)->loadObject();
            if (!$receipt || !hash_equals($receipt->request_hash, $hash)) { throw new \RuntimeException('COM_JLMS_MANUAL_CONFLICT', 409); }
            if ($receipt->enrollment_id !== null) { $this->db->transactionCommit(); return (int) $receipt->enrollment_id; }
            if (!hash_equals($receipt->claim_token, $nonce) || $p['blocked'] !== null || !hash_equals($p['hash'], $impact)) { throw new \RuntimeException('COM_JLMS_MANUAL_CONFLICT', 409); }
            $hex = bin2hex(random_bytes(16)); $subject = substr($hex,0,8).'-'.substr($hex,8,4).'-4'.substr($hex,13,3).'-a'.substr($hex,17,3).'-'.substr($hex,20);
            $q = $this->db->createQuery();
            $q->setQuery('INSERT INTO #__jlms_subjects (subject_key,user_id,created_at) VALUES (:subject,:user,:now) ON DUPLICATE KEY UPDATE user_id=user_id')
                ->bind(':subject', $subject)->bind(':user', $user, ParameterType::INTEGER)->bind(':now', $now); $this->db->setQuery($q)->execute();
            $q = $this->db->createQuery()->select('subject_key')->from('#__jlms_subjects')->where('user_id = :user')->bind(':user', $user, ParameterType::INTEGER);
            $q->setQuery((string) $q . ' FOR UPDATE'); $subject = (string) $this->db->setQuery($q)->loadResult();
            $record = (object) ['user_id' => $user, 'subject_key' => $subject, 'course_id' => $course, 'course_version_id' => $p['version_id'], 'edition_id' => $edition,
                'status' => 'active', 'origin' => 'manual', 'required_total' => $p['required_total'], 'completed_total' => 0, 'revision' => 1,
                'created_by' => $actorId, 'modified_by' => $actorId, 'created_at' => $now, 'modified_at' => $now];
            $this->db->insertObject('#__jlms_enrollments', $record, 'id'); $id = (int) $record->id;
            $update = (object) ['id' => (int) $receipt->id, 'enrollment_id' => $id, 'course_version_id' => $p['version_id']]; $this->db->updateObject('#__jlms_manual_enrollments', $update, 'id');
            ($this->audit)($actor, ['id' => (int) $receipt->id, 'actor_id' => $actorId, 'enrollment_id' => $id, 'reason_reference' => (int) $receipt->id,
                'reason_code' => 'manual_enrollment_created', 'preassignment_requested' => $preassignment, 'access_at_assignment' => $p['has_current_access'], 'correlation_id' => bin2hex(random_bytes(16))]);
            // A preassignment does not grant course access, so it must not look like a confirmed admission.
            if (!$preassignment || $p['has_current_access']) {
                (new \Jlms\Component\Jlms\Administrator\Infrastructure\MailQueue($this->db))->enqueue(
                    $course, hash('sha256', 'enrollment.confirmed:' . $id), 'com_jlms.enrollment.confirmed',
                    (string) ($learner->getParam('language', '') ?: $language), (string) $learner->email, trim((string) $learner->name),
                    ['USER_FULLNAME' => trim((string) $learner->name), 'COURSE_TITLE' => (string) $courseRecord->title,
                        'COURSE_URL' => \Joomla\CMS\Uri\Uri::root() . 'index.php?option=com_jlms&view=classroom&enrollment_id=' . $id,
                        'SITE_NAME' => (string) \Joomla\CMS\Factory::getApplication()->get('sitename')]
                );
            }
            $this->db->transactionCommit(); return $id;
        } catch (\Throwable $error) { $this->db->transactionRollback(); throw $error; }
    }
}
