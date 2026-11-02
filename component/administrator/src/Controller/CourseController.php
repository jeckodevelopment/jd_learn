<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;

final class CourseController extends FormController
{
    protected $view_list = 'courses';

    public function execute($task)
    {
        if (!in_array(strtolower($task), ['add', 'edit', 'apply', 'save', 'cancel'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
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
