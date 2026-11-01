<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\View\Enrollmentstate;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends BaseHtmlView
{
    public array $item; public $form;
    public function display($tpl = null)
    {
        Factory::getApplication()->setHeader('Cache-Control', 'private, no-store', true);
        $this->item = $this->get('Item'); $this->form = $this->get('Form'); $p = $this->item['impact'];
        $key = bin2hex(random_bytes(16));
        $key = substr($key,0,8).'-'.substr($key,8,4).'-4'.substr($key,13,3).'-a'.substr($key,17,3).'-'.substr($key,20);
        $this->form->bind(['course_id' => (int) $this->item['course']->id, 'enrollment_id' => $this->item['enrollment_id'],
            'enrollment_revision' => $p['enrollment_revision'], 'target_status' => $p['target_status'], 'impact_hash' => $p['hash'], 'mutation_id' => $key]);
        ToolbarHelper::title(Text::_('COM_JLMS_ENROLLMENT_STATE_TITLE'), 'users'); parent::display($tpl);
    }
}
