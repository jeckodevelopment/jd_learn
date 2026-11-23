<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Quiz;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $item;
    public $form;
    public bool $canManageQuizTime = false;
    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');
        $data = $this->item['data'];
        $actor = \Joomla\CMS\Factory::getApplication()->getIdentity();
        $asset = 'com_jlms.course.' . (int) $data['course_id'];
        $this->canManageQuizTime = $this->item['version']->status !== 'draft'
            && (int) $data['lesson_id'] > 0
            && $actor->authorise('jlms.enrollments.manage', $asset)
            && $actor->authorise('jlms.assessment.correct', $asset);
        $field = new \SimpleXMLElement('<field name="module_id" type="list" label="COM_JLMS_MODULE" required="true" />');
        foreach ($this->item['modules'] as $module) {
            $field->addChild('option', htmlspecialchars($module->title, ENT_XML1))->addAttribute('value', (string)$module->id);
        }
        if (empty($this->item['data']['lesson_id'])) {
            $selected = \Joomla\CMS\Factory::getApplication()->getInput()->getInt('module_id');
            if ($selected) {
                if (!in_array($selected, array_map(static fn($m): int => (int)$m->id, $this->item['modules']), true)) {
                    throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
                }
                $this->item['data']['module_id'] = $selected;
            }
        }
        $this->form->setField($field);
        $this->form->bind($this->item['data']);
        \Joomla\CMS\Toolbar\ToolbarHelper::title(\Joomla\CMS\Language\Text::_('COM_JLMS_QUIZ_EDITOR'), 'list');
        ToolbarHelper::help('quiz', true);
        parent::display($tpl);
    }
}
