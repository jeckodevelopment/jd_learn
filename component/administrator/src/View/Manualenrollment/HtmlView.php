<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\View\Manualenrollment;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends BaseHtmlView
{
    public array $item; public $form; public $selectionForm;
    public function display($tpl = null)
    {
        Factory::getApplication()->setHeader('Cache-Control', 'private, no-store', true);
        $this->item = $this->get('Item'); $this->form = $this->get('Form'); $this->selectionForm = $this->get('SelectionForm');
        $this->selectionForm->bind($this->item['selection']);
        if ($this->item['preview']) {
            $p = $this->item['preview']['impact']; $k = bin2hex(random_bytes(16));
            $k = substr($k,0,8).'-'.substr($k,8,4).'-4'.substr($k,13,3).'-a'.substr($k,17,3).'-'.substr($k,20);
            $this->form->bind(['course_id' => $p['course_id'], 'user_id' => $p['user_id'], 'language' => $p['language'], 'impact_hash' => $p['hash'], 'mutation_id' => $k]);
        }
        ToolbarHelper::title(Text::_('COM_JLMS_MANUAL_TITLE'), 'user-plus'); parent::display($tpl);
    }
}
