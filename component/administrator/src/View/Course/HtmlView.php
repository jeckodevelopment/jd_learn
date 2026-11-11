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
    public ?array $programme = null;
    public bool $readOnly = false;
    public bool $checkedOutByOther = false;
    public array $editorRights = [];
    public bool $canConfigure = false;
    public bool $canManageCourseRules = false;

    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        $actor=\Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::fresh(Factory::getApplication()->getIdentity());
        $id=(int)($this->item->id ?? 0);
        $allowed=$id>0 ? \Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::canEdit($actor,$this->item)
            : \Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::canCreate(Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class),$actor);
        if (!$allowed) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        if ($id > 0) { $this->programme = Factory::getApplication()->bootComponent('com_jlms')->getCurriculumService()->outline($actor, $id); }
        $this->form = $this->get('Form');
        if (!$this->form) {
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
        $this->getDocument()->getWebAssetManager()->getRegistry()->addExtensionRegistryFile('com_jlms');
        $this->getDocument()->getWebAssetManager()->useScript('com_jlms.courseAssociations');
        ToolbarHelper::title(Text::_('COM_JLMS_COURSE'), 'graduation-cap');
        $this->checkedOutByOther=$id>0 && $this->item->checked_out && (int)$this->item->checked_out!==(int)$actor->id;
        $asset=$id>0?'com_jlms.course.'.$id:'com_jlms';
        $this->canConfigure=$actor->authorise('core.options','com_jlms') || $actor->authorise('core.admin','com_jlms');
        if ($id > 0 && $actor->authorise('core.admin', $asset) && !$this->checkedOutByOther) {
            $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $assetId = (int) $this->item->asset_id;
            $query = $db->createQuery()->select('id')->from('#__assets')
                ->where('id = :id AND name = :name')
                ->bind(':id', $assetId, \Joomla\Database\ParameterType::INTEGER)->bind(':name', $asset);
            $this->canManageCourseRules = (bool) $db->setQuery($query)->loadResult();
            $this->form->setValue('asset_id', null, $assetId);
        }
        $this->editorRights=['COM_JLMS_EDITOR_RIGHT_CREATE'=>\Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::canCreate(Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class),$actor),'COM_JLMS_EDITOR_RIGHT_EDIT'=>$allowed,'COM_JLMS_EDITOR_RIGHT_PUBLISH'=>$actor->authorise('core.edit.state',$asset),'COM_JLMS_EDITOR_RIGHT_OPTIONS'=>$this->canConfigure];
        $this->readOnly = $this->checkedOutByOther;
        if ($this->readOnly) {
            foreach ($this->form->getFieldsets('com_fields') as $fieldset) {
                foreach ($this->form->getFieldset($fieldset->name) as $field) {
                    $this->form->setFieldAttribute($field->fieldname, 'disabled', 'true', $field->group);
                }
            }
            $this->form->setFieldAttribute('version_note', 'disabled', 'true');
            foreach (\Jlms\Component\Jlms\Administrator\Application\CourseSchedule::FIELDS as $field) { $this->form->setFieldAttribute($field,'disabled','true'); }
            $this->form->setFieldAttribute('teacher_ids','disabled','true');
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
            ToolbarHelper::custom('course.programme', 'list', 'list', 'COM_JLMS_COURSE_SAVE_PROGRAMME', false);
        }
        if ($id > 0) { ToolbarHelper::versions('com_jlms.course', $id); }
        ToolbarHelper::cancel('course.cancel');
        parent::display($tpl);
    }
}
