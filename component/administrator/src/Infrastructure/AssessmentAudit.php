<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Infrastructure;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class AssessmentAudit
{
    public function __construct(private readonly DatabaseInterface $db){}
    public function record(User $actor,array $message):void
    {
        Factory::getApplication()->bootComponent('com_actionlogs');
        $model=new AssessmentActionlogModel(['ignore_request'=>true]);$model->setDatabase($this->db);
        $model->setUserFactory(Factory::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class));
        $model->addLog([$message],'COM_JLMS_LOG_QUIZ_TIME','com_jlms.quiztime',(int)$actor->id);
        // Core addLog intentionally suppresses insert failures. This operation must instead roll back without an audit.
        $id=(int)$message['id'];$user=(int)$actor->id;$correlation=$message['correlation_id'];
        $q=$this->db->createQuery()->select('COUNT(*)')->from('#__action_logs')->where("extension = 'com_jlms.quiztime'")->where('item_id = :id')->where('user_id = :actor')
            ->where("JSON_UNQUOTE(JSON_EXTRACT(message, '$.correlation_id')) = :correlation")
            ->bind(':id',$id,ParameterType::INTEGER)->bind(':actor',$user,ParameterType::INTEGER)->bind(':correlation',$correlation);
        if((int)$this->db->setQuery($q)->loadResult()!==1)throw new \RuntimeException('COM_JLMS_QUIZ_TIME_AUDIT_FAILED',503);
    }
}
