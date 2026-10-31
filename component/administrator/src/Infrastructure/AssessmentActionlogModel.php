<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Infrastructure;

/** Native core storage/format, without notification delivery inside an assessment transaction. */
final class AssessmentActionlogModel extends \Joomla\Component\Actionlogs\Administrator\Model\ActionlogModel
{
    protected function sendNotificationEmails($messages,$username,$context)
    {
        // Assessment changes do not send mail. Notification delivery requires a separate transactional outbox.
    }
}
