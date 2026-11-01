<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\View\Course;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

final class HtmlView extends BaseHtmlView
{
    public $form;
    public $item;
    public bool $readOnly = false;

    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        $actor = Factory::getApplication()->getIdentity();
        $id = (int) ($this->item->id ?? 0);
        $allowed = $id > 0
            ? ($actor->authorise('core.edit', 'com_jlms.course.' . $id)
                || ((int) $this->item->created_by === (int) $actor->id && $actor->authorise('core.edit.own', 'com_jlms.course.' . $id)))
            : $actor->authorise('core.create', 'com_jlms');
        if (!$allowed) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $this->form = $this->get('Form');
        if (!$this->form) {
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
        ToolbarHelper::title(Text::_('COM_JLMS_COURSE'), 'graduation-cap');
        $this->readOnly = $id > 0 && ($this->item->current_version_id !== null || (int) $this->item->state !== 0);
        if ($this->readOnly) {
            foreach (['title', 'alias', 'catid', 'language', 'catalog_access', 'content_access', 'enrollment_mode', 'summary'] as $field) {
                $this->form->setFieldAttribute($field, 'disabled', 'true');
            }
            foreach (\Jlms\Component\Jlms\Administrator\Application\CoursePresentation::FIELDS as $field) {
                $this->form->setFieldAttribute('presentation_' . $field, 'disabled', 'true');
            }
            foreach ($this->form->getFieldset('item_associations') as $field) {
                $this->form->setFieldAttribute($field->fieldname, 'disabled', 'true', 'associations');
            }
        } else {
            ToolbarHelper::apply('course.apply');
            ToolbarHelper::save('course.save');
        }
        ToolbarHelper::cancel('course.cancel');
        parent::display($tpl);
    }
}
