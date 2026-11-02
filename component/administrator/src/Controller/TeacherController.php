<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;

final class TeacherController extends FormController
{
    protected $view_list = 'teachers';

    public function execute($task)
    {
        if (!in_array(strtolower($task), ['add', 'edit', 'apply', 'save', 'cancel'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }


    protected function allowAdd($data = [])
    {
        return $this->app->getIdentity()->authorise('jlms.teachers.manage', 'com_jlms') && $this->app->getIdentity()->authorise('core.create', 'com_jlms');
    }

    protected function allowEdit($data = [], $key = 'id')
    {
        $id = (int) ($data[$key] ?? 0);
        $item = $this->getModel()->getItem($id);
        $actor = $this->app->getIdentity();
        return $actor->authorise('jlms.teachers.manage', 'com_jlms') && $id > 0 && $item && ($actor->authorise('core.edit', 'com_jlms.teacher.' . $id)
            || ((int) $item->created_by === (int) $actor->id && $actor->authorise('core.edit.own', 'com_jlms.teacher.' . $id)));
    }
}
