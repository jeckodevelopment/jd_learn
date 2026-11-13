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
    public function record(User $actor,array $message,string $kind = 'quiztime'):void
    {
        $key=match($kind){'quiztime'=>'COM_JLMS_LOG_QUIZ_TIME','quizcancel'=>'COM_JLMS_LOG_QUIZ_CANCEL','quizextend'=>'COM_JLMS_LOG_QUIZ_EXTEND','enrollmentstate'=>'COM_JLMS_LOG_ENROLLMENT_STATE','manualenrollment'=>'COM_JLMS_LOG_MANUAL_ENROLLMENT','enrollmentmigration'=>'COM_JLMS_LOG_ENROLLMENT_MIGRATION','programmerestore'=>'COM_JLMS_LOG_PROGRAMME_RESTORE','courseoperations'=>'COM_JLMS_LOG_COURSE_OPERATIONS','coursecommunication'=>'COM_JLMS_LOG_COURSE_COMMUNICATION',default=>throw new \InvalidArgumentException('Invalid assessment audit context')};
        $context='com_jlms.'.$kind;
        Factory::getApplication()->bootComponent('com_actionlogs');
        $model=new AssessmentActionlogModel(['ignore_request'=>true]);$model->setDatabase($this->db);
        $model->setUserFactory(Factory::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class));
        $model->addLog([$message],$key,$context,(int)$actor->id);
        $logId=(int)$this->db->insertid();
        // Core addLog intentionally suppresses insert failures. This operation must instead roll back without an audit.
        $id=(int)$message['id'];$user=(int)$actor->id;$correlation=$message['correlation_id'];
        $q=$this->db->createQuery()->select('COUNT(*)')->from('#__action_logs')->where('id = :log')->where('extension = :context')->where('item_id = :id')->where('user_id = :actor')
            ->where("JSON_UNQUOTE(JSON_EXTRACT(message, '$.correlation_id')) = :correlation")
            ->bind(':log',$logId,ParameterType::INTEGER)->bind(':context',$context)->bind(':id',$id,ParameterType::INTEGER)->bind(':actor',$user,ParameterType::INTEGER)->bind(':correlation',$correlation);
        if((int)$this->db->setQuery($q)->loadResult()!==1)throw new \RuntimeException(match($kind){'quiztime'=>'COM_JLMS_QUIZ_TIME_AUDIT_FAILED','quizextend'=>'COM_JLMS_QUIZ_EXTEND_AUDIT_FAILED','enrollmentstate'=>'COM_JLMS_ENROLLMENT_STATE_AUDIT_FAILED','manualenrollment'=>'COM_JLMS_MANUAL_AUDIT_FAILED','enrollmentmigration'=>'COM_JLMS_MIGRATION_AUDIT_FAILED','programmerestore'=>'COM_JLMS_PROGRAMME_RESTORE_AUDIT_FAILED','courseoperations'=>'COM_JLMS_COURSE_OPERATIONS_AUDIT_FAILED','coursecommunication'=>'COM_JLMS_COMMUNICATION_AUDIT_FAILED',default=>'COM_JLMS_QUIZ_REVIEW_AUDIT_FAILED'},503);
    }
}
