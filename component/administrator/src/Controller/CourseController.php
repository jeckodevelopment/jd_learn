<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;

final class CourseController extends FormController
{
    protected $view_list = 'courses';

    public function execute($task)
    {
        if (!in_array(strtolower($task), ['add', 'edit', 'apply', 'save', 'cancel', 'loadhistory', 'programme', 'programmerules', 'trash', 'restore', 'purge'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        if (in_array(strtolower($task), ['programme', 'programmerules'], true)) {
            $this->registerTask($task, 'save');
        }
        return parent::execute($task);
    }


    /** Bulk trash/restore: POST + CSRF + per-course native ACL in the model. */
    private function lifecycle(int $target): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException('COM_JLMS_METHOD_NOT_ALLOWED', 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException('JINVALID_TOKEN', 403);
        }
        $raw = $this->input->post->get('cid', [], 'array');
        if ($raw === [] || count($raw) > 100) {
            throw new \RuntimeException('COM_JLMS_INVALID_COURSE', 422);
        }
        $ids = [];
        foreach ($raw as $id) {
            if (
                (!is_int($id) && !is_string($id))
                || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $id)
            ) {
                throw new \RuntimeException('COM_JLMS_INVALID_COURSE', 422);
            }
            $ids[] = (int) $id;
        }
        $model = $this->app->bootComponent('com_jlms')->getMVCFactory()
            ->createModel('Course', 'Administrator', ['ignore_request' => true]);
        if (!$model->changeStates($ids, $target)) {
            throw new \RuntimeException((string) $model->getError(), 403);
        }
        $this->setRedirect(\Joomla\CMS\Router\Route::link(
            'administrator',
            'index.php?option=com_jlms&view=courses'
            . ($target === 0 ? '&filter[published]=-2' : ''),
            false
        ));
    }

    public function trash(): void
    {
        $this->lifecycle(-2);
    }
    public function restore(): void
    {
        $this->lifecycle(0);
    }

    /** Destructive operation: exactly one trashed, unreferenced course. */
    public function purge(): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException('COM_JLMS_METHOD_NOT_ALLOWED', 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException('JINVALID_TOKEN', 403);
        }
        $raw = $this->input->post->get('cid', [], 'array');
        if (
            count($raw) !== 1 || (!is_int($raw[0]) && !is_string($raw[0]))
            || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $raw[0])
        ) {
            throw new \RuntimeException('COM_JLMS_INVALID_COURSE', 422);
        }
        if ($this->input->post->getInt('confirm_purge', 0) !== 1) {
            throw new \RuntimeException('COM_JLMS_PURGE_PREVIEW_REQUIRED', 422);
        }
        $expectedRevision = $this->input->post->getInt('expected_revision', 0);
        if ($expectedRevision < 1) {
            throw new \RuntimeException('COM_JLMS_REVISION_CONFLICT', 409);
        }
        $model = $this->app->bootComponent('com_jlms')->getMVCFactory()
            ->createModel('Course', 'Administrator', ['ignore_request' => true]);
        if (!$model->purgeEmpty((int) $raw[0], $expectedRevision)) {
            throw new \RuntimeException((string) $model->getError(), 403);
        }
        $this->setRedirect(\Joomla\CMS\Router\Route::link(
            'administrator',
            'index.php?option=com_jlms&view=courses&filter[published]=-2',
            false
        ));
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
                if (strtolower($this->getTask()) === 'programmerules') {
                    $url .= '&edit_rules=1#jlms-teaching-rules';
                }
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
        $actor = \Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::fresh($this->app->getIdentity());
        return \Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::canCreate(\Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class), $actor);
    }

    protected function allowEdit($data = [], $key = 'id')
    {
        $id = (int) ($data[$key] ?? 0);
        $item = $this->getModel()->getItem($id);
        $actor = \Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::fresh($this->app->getIdentity());
        return $id > 0 && $item && \Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::canEdit($actor, $item);
    }
}
