<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Mailqueue;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Jlms\Component\Jlms\Administrator\Application\MailQueueAuditService;

final class HtmlView extends BaseHtmlView
{
    public array $data = [];

    public function display($tpl = null)
    {
        Factory::getApplication()->setHeader('Cache-Control', 'private, no-store', true);
        $input = Factory::getApplication()->getInput();
        $filters = $input->get('filter', [], 'array');
        $start = max(0, $input->getInt('limitstart', 0));
        $this->data = Factory::getContainer()->get(MailQueueAuditService::class)
            ->page(Factory::getApplication()->getIdentity(), $filters, $start, 50);
        ToolbarHelper::title(Text::_('COM_JLMS_MAIL_QUEUE_TITLE'), 'envelope');
        parent::display($tpl);
    }
}
