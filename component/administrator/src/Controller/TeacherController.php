<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;

final class TeacherController extends FormController
{
    protected $view_list = 'teachers';

    public function execute($task)
    {
        if (!in_array(strtolower($task), ['add', 'edit', 'apply', 'save', 'cancel', 'loadhistory'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }


    protected function allowAdd($data = [])
    {
        $actor = new \Joomla\CMS\User\User((int) $this->app->getIdentity()->id); $actor->clearAccessRights();
        return $actor->id && !$actor->block && $actor->authorise('jlms.teachers.manage', 'com_jlms') && $actor->authorise('core.create', 'com_jlms');
    }

    protected function allowEdit($data = [], $key = 'id')
    {
        $id = (int) ($data[$key] ?? 0);
        $item = $this->getModel()->getItem($id);
        $actor = new \Joomla\CMS\User\User((int) $this->app->getIdentity()->id); $actor->clearAccessRights();
        return $actor->authorise('core.manage', 'com_jlms') && $id > 0 && $item
            && \Jlms\Component\Jlms\Administrator\Application\TeacherProfiles::canEdit($actor, $item);
    }
}
