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
    public int $replacement = 0;
    public int $replacementLesson = 0;
    public string $scheduleTimezone = 'UTC';
    public array $dateValues = ['publish_up' => '', 'publish_down' => ''];

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
        $data = ['course_id' => $this->item['course']->id, 'version_id' => $this->item['version']->id,
            'expected_version_revision' => $this->item['version']->revision, 'replace_id' => 0, 'preview' => 0];
        $data['publish_up'] = '';
        $data['publish_down'] = '';
        $selectedLesson = \Joomla\CMS\Factory::getApplication()->getInput()->getInt('lesson_id');
        if ($selectedLesson) {
            if (!in_array($selectedLesson, array_map(static fn($lesson): int => (int) $lesson->id, $this->item['lessons']), true)) { throw new \RuntimeException(Text::_('COM_JLMS_RESOURCE_NOT_FOUND'), 404); }
            $data['lesson_id'] = $selectedLesson;
        }
        $replaceId = \Joomla\CMS\Factory::getApplication()->getInput()->getInt('replace_id', 0);
        if ($replaceId) {
            foreach ($this->item['files']['items'] as $file) {
                if ((int) $file->id === $replaceId && $this->item['version']->status === 'draft') {
                    $data = array_replace($data, ['title' => $file->title, 'description' => $file->description, 'lesson_id' => (int) $file->lesson_id, 'preview' => (int) $file->preview,
                        'publish_up' => \Jlms\Component\Jlms\Administrator\Domain\MaterialAvailability::editor($file->publish_up, $this->item['schedule_timezone']),
                        'publish_down' => \Jlms\Component\Jlms\Administrator\Domain\MaterialAvailability::editor($file->publish_down, $this->item['schedule_timezone'])]);
                    $data['replace_id'] = $replaceId;
                    $this->replacement = $replaceId;
                    $this->replacementLesson = (int) $file->lesson_id;
                    $this->form->setFieldAttribute('lesson_id', 'disabled', 'true');
                }
            }
            if (!$this->replacement) {throw new \RuntimeException(Text::_('COM_JLMS_RESOURCE_NOT_FOUND'), 404);}
        }
        foreach ($this->item['files']['items'] as $file) {
            $file->publish_up_editor = \Jlms\Component\Jlms\Administrator\Domain\MaterialAvailability::editor($file->publish_up, $this->item['schedule_timezone']);
            $file->publish_down_editor = \Jlms\Component\Jlms\Administrator\Domain\MaterialAvailability::editor($file->publish_down, $this->item['schedule_timezone']);
        }
        $this->scheduleTimezone = $this->item['schedule_timezone'];
        $this->dateValues = ['publish_up' => $data['publish_up'], 'publish_down' => $data['publish_down']];
        $this->form->bind($data);
        ToolbarHelper::title(Text::_('COM_JLMS_MATERIALS'), 'file');
        parent::display($tpl);
    }
}
