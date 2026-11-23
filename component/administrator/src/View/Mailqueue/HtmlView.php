<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Mailqueue;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Jlms\Component\Jlms\Administrator\Application\MailQueueAuditService;
use Jlms\Component\Jlms\Administrator\Application\ConsentAuditService;

final class HtmlView extends BaseHtmlView
{
    public array $data = [];
    public array $mailScheduler = [];
    public array $consentData = [];

    public function display($tpl = null)
    {
        Factory::getApplication()->setHeader('Cache-Control', 'private, no-store', true);
        $input = Factory::getApplication()->getInput();
        $filters = $input->get('filter', [], 'array');
        $start = max(0, $input->getInt('limitstart', 0));
        $this->data = (new MailQueueAuditService(Factory::getContainer()
            ->get(\Joomla\Database\DatabaseInterface::class)))
            ->page(Factory::getApplication()->getIdentity(), $filters, $start, 50);
        $this->consentData = (new ConsentAuditService(Factory::getContainer()
            ->get(\Joomla\Database\DatabaseInterface::class)))
            ->page(
                Factory::getApplication()->getIdentity(),
                max(0, $input->getInt('consent_user_id', 0)),
                max(0, $input->getInt('consentstart', 0)),
                25
            );
        $this->mailScheduler = \Jlms\Component\Jlms\Administrator\Infrastructure\MailSchedulerSetup::status(
            Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class)
        );
        ToolbarHelper::title(Text::_('COM_JLMS_MAIL_QUEUE_TITLE'), 'envelope');
        ToolbarHelper::help('mailqueue', true);
        parent::display($tpl);
    }
}
