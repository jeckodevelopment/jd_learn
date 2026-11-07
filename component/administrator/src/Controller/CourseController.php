<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;

final class CourseController extends FormController
{
    protected $view_list = 'courses';

    public function execute($task)
    {
        if (!in_array(strtolower($task), ['add', 'edit', 'apply', 'save', 'cancel', 'loadhistory', 'programme', 'programmerules'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        if (in_array(strtolower($task), ['programme', 'programmerules'], true)) { $this->registerTask($task, 'save'); }
        return parent::execute($task);
    }


    protected function postSaveHook(\Joomla\CMS\MVC\Model\BaseDatabaseModel $model, $validData = [])
    {
        if (in_array(strtolower($this->getTask()), ['programme', 'programmerules'], true)) {
            $id = (int) $model->getState('course.id');
            try {
                $actor = \Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::fresh($this->app->getIdentity());
                $item = $model->getItem($id);
                $version = $this->app->bootComponent('com_jlms')->getCurriculumService()->createDraft($actor, $id, (int) $item->revision);
                $url = 'index.php?option=com_jlms&view=curriculum&id=' . $id . '&version_id=' . $version;
                if (strtolower($this->getTask()) === 'programmerules') { $url .= '&edit_rules=1#jlms-teaching-rules'; }
            } catch (\Throwable) {
                // The editorial save has succeeded; keep it and explain a failed programme preparation.
                $this->setMessage(\Joomla\CMS\Language\Text::_('COM_JLMS_COURSE_PROGRAMME_SAVED_WARNING'), 'warning');
                $url = 'index.php?option=com_jlms&view=course&layout=edit&id=' . $id;
            }
            $this->setRedirect(\Joomla\CMS\Router\Route::link('administrator', $url, false));
        }
    }

    protected function allowAdd($data = [])
    {
        $actor=\Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::fresh($this->app->getIdentity());
        return \Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::canCreate(\Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class),$actor);
    }

    protected function allowEdit($data = [], $key = 'id')
    {
        $id = (int) ($data[$key] ?? 0);
        $item = $this->getModel()->getItem($id);
        $actor=\Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::fresh($this->app->getIdentity());
        return $id>0 && $item && \Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::canEdit($actor,$item);
    }
}
