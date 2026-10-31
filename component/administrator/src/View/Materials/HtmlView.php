<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Materials;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;

final class HtmlView extends BaseHtmlView
{
    public array $item;
    public $form;

    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');
        $field = new \SimpleXMLElement('<field name="lesson_id" type="list" label="COM_JLMS_MATERIAL_SCOPE" filter="integer" />');
        $field->addChild('option', Text::_('COM_JLMS_COURSE_MATERIALS'))->addAttribute('value', '0');
        foreach ($this->item['lessons'] as $lesson) {
            $field->addChild('option', htmlspecialchars($lesson->title, ENT_XML1))->addAttribute('value', (string) $lesson->id);
        }
        $this->form->setField($field);
        $this->form->bind(['course_id' => $this->item['course']->id, 'version_id' => $this->item['version']->id,
            'expected_version_revision' => $this->item['version']->revision]);
        ToolbarHelper::title(Text::_('COM_JLMS_MATERIALS'), 'file');
        parent::display($tpl);
    }
}
