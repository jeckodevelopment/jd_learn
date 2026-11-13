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
    protected $autoloadLanguage=true;
    protected const TASKS_MAP=[
        'jlms.quiz.expire'=>['langConstPrefix'=>'PLG_TASK_JLMS_EXPIRE','form'=>'expire','method'=>'expireQuizzes'],
        'jlms.materials.cleanup'=>['langConstPrefix'=>'PLG_TASK_JLMS_CLEANUP','form'=>'expire','method'=>'cleanupMaterials'],
        'jlms.mail.dispatch'=>['langConstPrefix'=>'PLG_TASK_JLMS_MAIL','form'=>'expire','method'=>'dispatchMail'],
    ];
    public static function getSubscribedEvents():array
    {
        return ['onTaskOptionsList'=>'advertiseRoutines','onExecuteTask'=>'standardRoutineHandler','onContentPrepareForm'=>'enhanceTaskItemForm'];
    }
    protected function expireQuizzes(ExecuteTaskEvent $event):int
    {
        $params=$event->getArgument('params');
        $result=$this->getApplication()->bootComponent('com_jlms')->getQuizAttemptService()->expireDue((int)($params->limit??100));
        $this->logTask('Quiz expiry: closed='.$result['closed'].'; failed='.$result['failed'].'; more='.(int)$result['has_more']);
        return $result['failed']?Status::KNOCKOUT:Status::OK;
    }
    protected function cleanupMaterials(ExecuteTaskEvent $event):int
    {
        $params=$event->getArgument('params');
        $result=$this->getApplication()->bootComponent('com_jlms')->getMaterialService()->purgeOrphaned((int)($params->limit??100));
        $this->logTask('Material cleanup: deleted='.$result['deleted'].'; failed='.$result['failed'].'; more='.(int)$result['has_more']);
        return $result['failed']?Status::KNOCKOUT:Status::OK;
    }
    protected function dispatchMail(ExecuteTaskEvent $event):int
    {
        $params=$event->getArgument('params');
        $app=Factory::getApplication();
        $app->bootComponent('com_jlms');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $expiry=(new \Jlms\Component\Jlms\Administrator\Application\CourseExpiryReminderService($db))->enqueueDue();
        $communication=$app->bootComponent('com_jlms')->getCourseCommunicationService()->process((int)($params->limit??25));
        $result=(new \Jlms\Component\Jlms\Administrator\Infrastructure\MailQueue($db))->dispatch((int)($params->limit??25));
        $this->logTask('Course expiry reminders: queued='.$expiry['queued'].'; failed='.$expiry['failed'].'; processed='.$expiry['processed'].'; communications: queued='.$communication['queued'].'; failed='.$communication['failed'].'; mail queue: sent='.$result['sent'].'; failed='.$result['failed'].'; processed='.$result['processed']);
        return ($result['failed'] || $communication['failed'] || $expiry['failed'])?Status::KNOCKOUT:Status::OK;
    }
}
