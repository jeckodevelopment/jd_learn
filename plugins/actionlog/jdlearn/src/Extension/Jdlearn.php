<?php

declare(strict_types=1);

namespace Jlms\Plugin\Actionlog\Jdlearn\Extension;

use Jlms\Component\Jlms\Administrator\Infrastructure\AssessmentActionlogModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

defined('_JEXEC') or die;

/**
 * Native Joomla User Actions Log integration for committed course transitions.
 *
 * Existing assessment/enrollment audit entries are already written directly
 * through AssessmentAudit: never subscribe to or duplicate those events.
 */
final class Jdlearn extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return ['onJlmsCourseTransition' => 'onJlmsCourseTransition'];
    }

    public function onJlmsCourseTransition(Event $event): void
    {
        $app = $this->getApplication();
        if (!$app->isClient('administrator')) {
            return;
        }

        $actorId = (int) $event->getArgument('actorId', 0);
        $courseId = (int) $event->getArgument('courseId', 0);
        $versionId = (int) $event->getArgument('versionId', 0);
        $transitionId = (int) $event->getArgument('transitionId', 0);
        $action = (string) $event->getArgument('action', '');
        if (
            $actorId < 1 || $courseId < 1 || $versionId < 1 || $transitionId < 1
            || $actorId !== (int) $app->getIdentity()->id
            || !in_array($action, ['review', 'return', 'publish', 'unpublish', 'archive', 'restore'], true)
        ) {
            return;
        }

        // Joomla's native model owns the format and storage of #__action_logs.
        // Do not include course titles, reasons, learner identities or content.
        $app->bootComponent('com_jlms');
        $app->bootComponent('com_actionlogs');
        $message = [
            'id' => $courseId,
            'course_id' => $courseId,
            'version_id' => $versionId,
            'transition_id' => $transitionId,
            'action' => $action,
        ];
        $model = new AssessmentActionlogModel(['ignore_request' => true]);
        $model->setDatabase($this->getDatabase());
        $model->setUserFactory(Factory::getContainer()->get(UserFactoryInterface::class));
        $model->addLog([$message], 'PLG_ACTIONLOG_JDLEARN_COURSE_TRANSITION', 'com_jlms.course', $actorId);
    }
}
