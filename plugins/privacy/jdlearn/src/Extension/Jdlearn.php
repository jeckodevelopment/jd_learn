<?php

declare(strict_types=1);

namespace Jlms\Plugin\Privacy\Jdlearn\Extension;

use Joomla\CMS\Event\Privacy\CanRemoveDataEvent;
use Joomla\CMS\Event\Privacy\CollectCapabilitiesEvent;
use Joomla\CMS\Event\Privacy\ExportRequestEvent;
use Joomla\CMS\Event\Privacy\RemoveDataEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Component\Privacy\Administrator\Plugin\PrivacyPlugin;
use Joomla\Component\Privacy\Administrator\Removal\Status;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

final class Jdlearn extends PrivacyPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onPrivacyCollectAdminCapabilities' => 'onPrivacyCollectAdminCapabilities',
            'onPrivacyExportRequest' => 'onPrivacyExportRequest',
            'onPrivacyCanRemoveData' => 'onPrivacyCanRemoveData',
            'onPrivacyRemoveData' => 'onPrivacyRemoveData',
        ];
    }

    public function onPrivacyCollectAdminCapabilities(CollectCapabilitiesEvent $event): void
    {
        $this->loadPrivacyLanguage();

        $event->addResult([
            Text::_('PLG_PRIVACY_JDLEARN_CAPABILITY_TITLE') => [
                Text::_('PLG_PRIVACY_JDLEARN_CAPABILITY_ENROLLMENTS'),
                Text::_('PLG_PRIVACY_JDLEARN_CAPABILITY_PROGRESS'),
                Text::_('PLG_PRIVACY_JDLEARN_CAPABILITY_ATTEMPTS'),
                Text::_('PLG_PRIVACY_JDLEARN_CAPABILITY_CERTIFICATES'),
                Text::_('PLG_PRIVACY_JDLEARN_CAPABILITY_MAIL_CONSENT'),
            ],
        ]);
    }

    public function onPrivacyExportRequest(ExportRequestEvent $event): void
    {
        $this->loadPrivacyLanguage();

        $user = $event->getUser();

        if (!$user) {
            return;
        }

        $db = $this->getDatabase();
        $enrollmentDomain = $this->createDomain('jdlearn_enrollments', 'plg_privacy_jdlearn_domain_enrollments');
        $requestDomain = $this->createDomain('jdlearn_enrollment_requests', 'plg_privacy_jdlearn_domain_requests');
        $progressDomain = $this->createDomain('jdlearn_progress', 'plg_privacy_jdlearn_domain_progress');
        $attemptDomain = $this->createDomain('jdlearn_attempts', 'plg_privacy_jdlearn_domain_attempts');
        $answerDomain = $this->createDomain('jdlearn_answers', 'plg_privacy_jdlearn_domain_answers');
        $certificateDomain = $this->createDomain('jdlearn_certificates', 'plg_privacy_jdlearn_domain_certificates');
        $consentDomain = $this->createDomain('jdlearn_course_announcement_consent', 'plg_privacy_jdlearn_domain_course_announcement_consent');

        $query = $db->createQuery()
            ->select([
                $db->quoteName('id'), $db->quoteName('course_id'), $db->quoteName('course_version_id'),
                $db->quoteName('edition_id'), $db->quoteName('status'), $db->quoteName('origin'),
                $db->quoteName('required_total'), $db->quoteName('completed_total'),
                $db->quoteName('started_at'), $db->quoteName('completed_at'), $db->quoteName('last_activity_at'),
                $db->quoteName('starts_at'), $db->quoteName('ends_at'), $db->quoteName('created_at'),
            ])
            ->from($db->quoteName('#__jlms_enrollments'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->bind(':userId', $user->id);
        $enrollments = $db->setQuery($query)->loadObjectList();

        foreach ($enrollments as $enrollment) {
            $enrollmentDomain->addItem($this->createItemFromArray((array) $enrollment));
        }

        $requestQuery = $db->createQuery()
            ->select([
                $db->quoteName('id'), $db->quoteName('edition_id'), $db->quoteName('status'),
                $db->quoteName('reason'), $db->quoteName('decided_at'), $db->quoteName('created_at'),
            ])
            ->from($db->quoteName('#__jlms_enrollment_requests'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->bind(':userId', $user->id);
        foreach ($db->setQuery($requestQuery)->loadObjectList() as $request) {
            $requestDomain->addItem($this->createItemFromArray((array) $request));
        }

        $progressQuery = $db->createQuery()
            ->select([
                $db->quoteName('p.course_version_id'), $db->quoteName('p.lesson_id'),
                $db->quoteName('p.source_revision'), $db->quoteName('p.covered_ms'),
                $db->quoteName('p.resume_ms'), $db->quoteName('p.state'),
                $db->quoteName('p.coverage_json'), $db->quoteName('p.completed_at'), $db->quoteName('p.last_activity_at'),
                $db->quoteName('e.course_id'), $db->quoteName('e.edition_id'),
            ])
            ->from($db->quoteName('#__jlms_lesson_progress', 'p'))
            ->innerJoin($db->quoteName('#__jlms_enrollments', 'e') . ' ON ' . $db->quoteName('e.id') . ' = ' . $db->quoteName('p.enrollment_id'))
            ->where($db->quoteName('e.user_id') . ' = :userId')
            ->bind(':userId', $user->id);
        foreach ($db->setQuery($progressQuery)->loadObjectList() as $progress) {
            $progressDomain->addItem($this->createItemFromArray((array) $progress));
        }

        $attemptQuery = $db->createQuery()
            ->select([
                $db->quoteName('a.id'), $db->quoteName('e.course_id'), $db->quoteName('a.course_version_id'),
                $db->quoteName('a.quiz_id'), $db->quoteName('a.attempt_no'), $db->quoteName('a.status'),
                $db->quoteName('a.max_points'), $db->quoteName('a.score_points'), $db->quoteName('a.passed'),
                $db->quoteName('a.started_at'), $db->quoteName('a.submitted_at'),
            ])
            ->from($db->quoteName('#__jlms_attempts', 'a'))
            ->innerJoin($db->quoteName('#__jlms_enrollments', 'e') . ' ON ' . $db->quoteName('e.id') . ' = ' . $db->quoteName('a.enrollment_id'))
            ->where($db->quoteName('e.user_id') . ' = :userId')
            ->bind(':userId', $user->id);
        foreach ($db->setQuery($attemptQuery)->loadObjectList() as $attempt) {
            $attemptDomain->addItem($this->createItemFromArray((array) $attempt));
        }

        $answerQuery = $db->createQuery()
            ->select([
                $db->quoteName('a.id', 'attempt_id'), $db->quoteName('a.quiz_id'),
                $db->quoteName('aq.display_order'), $db->quoteName('ans.answer_json'), $db->quoteName('ans.saved_at'),
            ])
            ->from($db->quoteName('#__jlms_attempt_answers', 'ans'))
            ->innerJoin($db->quoteName('#__jlms_attempt_questions', 'aq') . ' ON ' . $db->quoteName('aq.id') . ' = ' . $db->quoteName('ans.attempt_question_id'))
            ->innerJoin($db->quoteName('#__jlms_attempts', 'a') . ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('aq.attempt_id'))
            ->innerJoin($db->quoteName('#__jlms_enrollments', 'e') . ' ON ' . $db->quoteName('e.id') . ' = ' . $db->quoteName('a.enrollment_id'))
            ->where($db->quoteName('e.user_id') . ' = :userId')
            ->bind(':userId', $user->id);
        foreach ($db->setQuery($answerQuery)->loadObjectList() as $answer) {
            $answerDomain->addItem($this->createItemFromArray((array) $answer));
        }

        $certificateQuery = $db->createQuery()
            ->select([
                $db->quoteName('c.id'), $db->quoteName('c.course_version_id'), $db->quoteName('c.issued_at'),
                $db->quoteName('d.serial'), $db->quoteName('d.identity_snapshot_json'), $db->quoteName('d.status'),
                $db->quoteName('d.issued_at', 'document_issued_at'), $db->quoteName('e.course_id'),
            ])
            ->from($db->quoteName('#__jlms_certificates', 'c'))
            ->innerJoin($db->quoteName('#__jlms_enrollments', 'e') . ' ON ' . $db->quoteName('e.id') . ' = ' . $db->quoteName('c.enrollment_id'))
            ->leftJoin($db->quoteName('#__jlms_certificate_documents', 'd') . ' ON ' . $db->quoteName('d.certificate_id') . ' = ' . $db->quoteName('c.id'))
            ->where($db->quoteName('e.user_id') . ' = :userId')
            ->bind(':userId', $user->id);
        foreach ($db->setQuery($certificateQuery)->loadObjectList() as $certificate) {
            $certificateDomain->addItem($this->createItemFromArray((array) $certificate));
        }

        $consentQuery = $db->createQuery()->select(['state', 'origin', 'policy_version', 'requested_at', 'confirmed_at', 'revoked_at'])
            ->from($db->quoteName('#__jlms_mail_consents'))->where($db->quoteName('user_id') . ' = :userId')->bind(':userId', $user->id);
        foreach ($db->setQuery($consentQuery)->loadObjectList() as $consent) {
            $consentDomain->addItem($this->createItemFromArray((array) $consent));
        }

        $event->addResult([$enrollmentDomain, $requestDomain, $progressDomain, $attemptDomain, $answerDomain, $certificateDomain, $consentDomain]);
    }

    public function onPrivacyCanRemoveData(CanRemoveDataEvent $event): void
    {
        $this->loadPrivacyLanguage();

        $user = $event->getUser();
        $status = new Status();

        if (!$user) {
            $event->addResult($status);
            return;
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__jlms_certificate_documents', 'd'))
            ->innerJoin($db->quoteName('#__jlms_certificates', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('d.certificate_id'))
            ->innerJoin($db->quoteName('#__jlms_enrollments', 'e') . ' ON ' . $db->quoteName('e.id') . ' = ' . $db->quoteName('c.enrollment_id'))
            ->where($db->quoteName('e.user_id') . ' = :userId')
            ->bind(':userId', $user->id);
        $documents = (int) $db->setQuery($query)->loadResult();

        $jobsQuery = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__jlms_jobs'))
            ->where($db->quoteName('owner_user_id') . ' = :userId')
            ->bind(':userId', $user->id);
        $jobs = (int) $db->setQuery($jobsQuery)->loadResult();

        $outboxQuery = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__jlms_outbox', 'o'))
            ->where($db->quoteName('o.aggregate_type') . ' = ' . $db->quote('enrollment'))
            ->where($db->quoteName('o.aggregate_id') . ' IN (' . $db->createQuery()
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__jlms_enrollments'))
                ->where($db->quoteName('user_id') . ' = ' . (int) $user->id) . ')');
        $outbox = (int) $db->setQuery($outboxQuery)->loadResult();

        if ($documents > 0 || $jobs > 0 || $outbox > 0) {
            $status->canRemove = false;
            $status->reason = Text::_('PLG_PRIVACY_JDLEARN_ERROR_RETENTION_BLOCK');
        }

        $event->addResult($status);
    }

    public function onPrivacyRemoveData(RemoveDataEvent $event): void
    {
        $user = $event->getUser();

        if (!$user) {
            return;
        }

        $db = $this->getDatabase();
        $userId = (int) $user->id;
        $db->transactionStart();

        try {
            $db->setQuery($db->createQuery()->delete($db->quoteName('#__jlms_mail_consents'))
                ->where($db->quoteName('user_id') . ' = ' . $userId))->execute();
            $enrollments = $db->createQuery()
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__jlms_enrollments'))
                ->where($db->quoteName('user_id') . ' = ' . $userId);
            $db->setQuery(
                $db->createQuery()->delete($db->quoteName('#__jlms_progress_sessions'))
                    ->where($db->quoteName('enrollment_id') . ' IN (' . $enrollments . ')')
            )->execute();

            $answerQuery = $db->createQuery()->update($db->quoteName('#__jlms_attempt_answers', 'ans'))
                ->set($db->quoteName('ans.answer_json') . " = '{}' ")
                ->where($db->quoteName('ans.attempt_question_id') . ' IN (' .
                    $db->createQuery()->select($db->quoteName('aq.id'))
                        ->from($db->quoteName('#__jlms_attempt_questions', 'aq'))
                        ->innerJoin($db->quoteName('#__jlms_attempts', 'a') . ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('aq.attempt_id'))
                        ->where($db->quoteName('a.enrollment_id') . ' IN (' . $enrollments . ')') . ')');
            $db->setQuery($answerQuery)->execute();

            $db->setQuery($db->createQuery()->update($db->quoteName('#__jlms_enrollment_requests'))
                ->set($db->quoteName('user_id') . ' = NULL')
                ->set($db->quoteName('decided_by') . ' = NULL')
                ->set($db->quoteName('reason') . ' = NULL')
                ->where('(' . $db->quoteName('user_id') . ' = ' . $userId . ' OR ' . $db->quoteName('decided_by') . ' = ' . $userId . ')'))->execute();
            $db->setQuery($db->createQuery()->update($db->quoteName('#__jlms_manual_enrollments'))
                ->set($db->quoteName('actor_user_id') . ' = NULL')
                ->where($db->quoteName('actor_user_id') . ' = ' . $userId))->execute();

            foreach (['#__jlms_enrollment_state_changes', '#__jlms_quiz_time_adjustments'] as $table) {
                $db->setQuery($db->createQuery()->update($db->quoteName($table))
                    ->set($db->quoteName('actor_user_id') . ' = NULL')
                    ->where($db->quoteName('actor_user_id') . ' = ' . $userId))->execute();
            }

            $db->setQuery($db->createQuery()->update($db->quoteName('#__jlms_attempts'))
                ->set($db->quoteName('invalidated_by') . ' = NULL')
                ->where($db->quoteName('invalidated_by') . ' = ' . $userId))->execute();

            $db->setQuery($db->createQuery()->update($db->quoteName('#__jlms_anomalies'))
                ->set($db->quoteName('resolved_by') . ' = NULL')
                ->where($db->quoteName('resolved_by') . ' = ' . $userId))->execute();

            $db->setQuery($db->createQuery()->update($db->quoteName('#__jlms_course_operations'))
                ->set($db->quoteName('actor_user_id') . ' = NULL')
                ->where($db->quoteName('actor_user_id') . ' = ' . $userId))->execute();

            $db->setQuery($db->createQuery()->update($db->quoteName('#__jlms_quiz_deadline_extensions'))
                ->set($db->quoteName('actor_user_id') . ' = NULL')
                ->where($db->quoteName('actor_user_id') . ' = ' . $userId))->execute();

            $db->setQuery($db->createQuery()->update($db->quoteName('#__jlms_enrollments'))
                ->set($db->quoteName('user_id') . ' = NULL')
                ->where($db->quoteName('user_id') . ' = ' . $userId))->execute();

            $db->transactionCommit();
        } catch (\Throwable $error) {
            $db->transactionRollback();
            throw $error;
        }
    }

    private function loadPrivacyLanguage(): void
    {
        // Text::_() resolves through Factory::getLanguage(); load into that same instance.
        $language = Factory::getLanguage();

        if (!$language) {
            return;
        }

        if (!$language->load('plg_privacy_jdlearn', JPATH_ADMINISTRATOR)) {
            $language->load('plg_privacy_jdlearn', JPATH_PLUGINS . '/privacy/jdlearn');
        }
    }
}
