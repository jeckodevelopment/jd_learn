<?php

declare(strict_types=1);
namespace Jlms\Plugin\Task\Jlms\Extension;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;

final class Jlms extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;
    protected $autoloadLanguage=true;
    protected const TASKS_MAP=['jlms.quiz.expire'=>['langConstPrefix'=>'PLG_TASK_JLMS_EXPIRE','form'=>'expire','method'=>'expireQuizzes']];
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
}
