<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\User\User;

final class PathwayController extends FormController
{
    protected $view_list = 'pathways';

    public function execute($task)
    {
        if (!in_array(strtolower($task), ['add', 'edit', 'apply', 'save', 'cancel'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    protected function allowAdd($data = [])
    {
        $actor = new User((int) $this->app->getIdentity()->id);
        $actor->clearAccessRights();
        return $actor->id && !$actor->block
            && $actor->authorise('jlms.pathways.manage', 'com_jlms')
            && $actor->authorise('core.create', 'com_jlms');
    }

    protected function allowEdit($data = [], $key = 'id')
    {
        $id = (int) ($data[$key] ?? 0);
        $actor = new User((int) $this->app->getIdentity()->id);
        $actor->clearAccessRights();
        return $id > 0 && $actor->id && !$actor->block
            && $actor->authorise('jlms.pathways.manage', 'com_jlms')
            && $actor->authorise('core.edit', 'com_jlms.pathway.' . $id);
    }
}
