<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;

final class TeacherController extends FormController
{
    protected $view_list = 'teachers';

    public function execute($task)
    {
        if (!in_array(strtolower($task), ['add', 'edit', 'apply', 'save', 'cancel', 'loadhistory', 'trash', 'restore', 'publish', 'unpublish', 'purge'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }


    /** Server-side POST, CSRF and per-record ACL checks for bulk operations. */
    private function transition(int $target): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException('COM_JLMS_METHOD_NOT_ALLOWED', 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException('JINVALID_TOKEN', 403);
        }
        $raw = $this->input->post->get('cid', [], 'array');
        if ($raw === [] || count($raw) > 100) {
            throw new \RuntimeException('COM_JLMS_TEACHER_INVALID', 422);
        }
        $ids = [];
        foreach ($raw as $value) {
            if (
                (!is_string($value) && !is_int($value))
                || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $value)
            ) {
                throw new \RuntimeException('COM_JLMS_TEACHER_INVALID', 422);
            }
            $ids[] = (int) $value;
        }
        $model = $this->app->bootComponent('com_jlms')->getMVCFactory()
            ->createModel('Teacher', 'Administrator', ['ignore_request' => true]);
        if (!$model->changeStates($ids, $target)) {
            throw new \RuntimeException((string) $model->getError(), 403);
        }
        $this->setRedirect(\Joomla\CMS\Router\Route::link(
            'administrator',
            'index.php?option=com_jlms&view=teachers'
            . ($target === 0 ? '&filter[published]=-2' : ''),
            false
        ));
    }

    public function trash(): void
    {
        $this->transition(-2);
    }
    public function restore(): void
    {
        $this->transition(0);
    }
    public function publish(): void
    {
        $this->transition(1);
    }
    public function unpublish(): void
    {
        $this->transition(0);
    }

    /** Exactly one teacher, explicitly confirmed in the trash view. */
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
            throw new \RuntimeException('COM_JLMS_TEACHER_INVALID', 422);
        }
        $model = $this->app->bootComponent('com_jlms')->getMVCFactory()
            ->createModel('Teacher', 'Administrator', ['ignore_request' => true]);
        if (!$model->purgeUnreferenced((int) $raw[0])) {
            throw new \RuntimeException((string) $model->getError(), 403);
        }
        $this->setRedirect(\Joomla\CMS\Router\Route::link(
            'administrator',
            'index.php?option=com_jlms&view=teachers&filter[published]=-2',
            false
        ));
    }

    protected function allowAdd($data = [])
    {
        $actor = new \Joomla\CMS\User\User((int) $this->app->getIdentity()->id);
        $actor->clearAccessRights();
        return $actor->id && !$actor->block && $actor->authorise('jlms.teachers.manage', 'com_jlms') && $actor->authorise('core.create', 'com_jlms');
    }

    protected function allowEdit($data = [], $key = 'id')
    {
        $id = (int) ($data[$key] ?? 0);
        $item = $this->getModel()->getItem($id);
        $actor = new \Joomla\CMS\User\User((int) $this->app->getIdentity()->id);
        $actor->clearAccessRights();
        return $actor->authorise('core.manage', 'com_jlms') && $id > 0 && $item
            && \Jlms\Component\Jlms\Administrator\Application\TeacherProfiles::canEdit($actor, $item);
    }
}
