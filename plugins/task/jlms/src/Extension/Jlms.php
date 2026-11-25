<?php

declare(strict_types=1);

namespace Jlms\Plugin\Task\Jlms\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;

final class Jlms extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    protected $autoloadLanguage = true;
    protected const TASKS_MAP = [
        'jlms.quiz.expire' => ['langConstPrefix' => 'PLG_TASK_JLMS_EXPIRE','form' => 'expire',
            'method' => 'expireQuizzes'],
        'jlms.materials.cleanup' => ['langConstPrefix' => 'PLG_TASK_JLMS_CLEANUP','form' => 'expire',
            'method' => 'cleanupMaterials'],
        'jlms.mail.dispatch' => ['langConstPrefix' => 'PLG_TASK_JLMS_MAIL','form' => 'expire',
            'method' => 'dispatchMail'],
    ];
    public static function getSubscribedEvents(): array
    {
        return ['onTaskOptionsList' => 'advertiseRoutines','onExecuteTask' => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm'];
    }
    protected function expireQuizzes(ExecuteTaskEvent $event): int
    {
        $params = $event->getArgument('params');
        $limit = max(1, min(100, (int) ($params->limit ?? 100)));
        $result = $this->getApplication()->bootComponent('com_jlms')->getQuizAttemptService()
            ->expireDue($limit);
        $this->logTask('Quiz expiry: batch_limit=' . $limit . '; closed=' . $result['closed']
            . '; failed=' . $result['failed'] . '; more=' . (int) $result['has_more']);
        return $result['failed'] ? Status::KNOCKOUT : Status::OK;
    }
    protected function cleanupMaterials(ExecuteTaskEvent $event): int
    {
        $params = $event->getArgument('params');
        $limit = max(1, min(100, (int) ($params->limit ?? 100)));
        $result = $this->getApplication()->bootComponent('com_jlms')->getMaterialService()
            ->purgeOrphaned($limit);
        $this->logTask('Material cleanup: batch_limit=' . $limit . '; deleted=' . $result['deleted']
            . '; failed=' . $result['failed'] . '; more=' . (int) $result['has_more']);
        return $result['failed'] ? Status::KNOCKOUT : Status::OK;
    }
    protected function dispatchMail(ExecuteTaskEvent $event): int
    {
        $params = $event->getArgument('params');
        $app = Factory::getApplication();
        $app->bootComponent('com_jlms');
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        // The task's configured limit must bound *all* discovery/enqueue
        // routines as well as dispatch; otherwise a single run can enqueue
        // thousands of notifications even with a small configured batch.
        $limit = max(1, min(100, (int) ($params->limit ?? 25)));
        $expiry = (new \Jlms\Component\Jlms\Administrator\Application\CourseExpiryReminderService($db))
            ->enqueueDue($limit);
        $lessons = (new \Jlms\Component\Jlms\Administrator\Application\LessonReleaseNotice($db))
            ->enqueueDue($limit);
        $updates = (new \Jlms\Component\Jlms\Administrator\Application\CourseVersionChangeNotice($db))
            ->enqueueDue($limit);
        $managers = (new \Jlms\Component\Jlms\Administrator\Application\EnrollmentManagerNotice($db))
            ->enqueueDue($limit);
        $communication = $app->bootComponent('com_jlms')->getCourseCommunicationService()->process($limit);
        $result = (new \Jlms\Component\Jlms\Administrator\Infrastructure\MailQueue($db))
            ->dispatch($limit);
        // A stage reaching its cap may still have work: record saturation
        // rather than claiming an exact pending count without another query.
        $atLimit = (int) (
            $expiry['processed'] >= $limit || $lessons['processed'] >= $limit
            || $updates['processed'] >= $limit || $managers['processed'] >= $limit
            || $communication['processed'] >= $limit || $result['processed'] >= $limit
        );
        $this->logTask('Mail scheduler: batch_limit=' . $limit . '; at_limit=' . $atLimit
            . '; skipped=' . $result['skipped'] . '; course expiry reminders: queued=' . $expiry['queued'] . '; failed=' . $expiry['failed']
            . '; processed=' . $expiry['processed'] . '; lesson releases: queued=' . $lessons['queued']
                . '; failed=' . $lessons['failed'] . '; processed=' . $lessons['processed']
                    . '; course version updates: queued=' . $updates['queued'] . '; failed='
                        . $updates['failed'] . '; processed=' . $updates['processed']
                            . '; approval managers: queued=' . $managers['queued'] . '; failed='
                                . $managers['failed'] . '; processed=' . $managers['processed']
                                    . '; communications: queued=' . $communication['queued'] . '; failed='
                        . $communication['failed'] . '; mail queue: sent=' . $result['sent'] . '; failed='
                        . $result['failed'] . '; processed=' . $result['processed']);
        return ($result['failed'] || $communication['failed'] || $expiry['failed'] || $lessons['failed']
            || $updates['failed'] || $managers['failed']) ? Status::KNOCKOUT : Status::OK;
    }
}
