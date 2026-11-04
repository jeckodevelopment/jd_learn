<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Site\Controller;

final class TeacherController extends \Joomla\CMS\MVC\Controller\FormController
{
    protected $view_item = 'teacheredit';
    protected $view_list = 'teacher';
    public function execute($task)
    {
        if (!in_array(strtolower($task), ['edit', 'save', 'apply', 'cancel'], true)) { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404); }
        return parent::execute($task);
    }
    public function getModel($name = 'Teacheredit', $prefix = 'Site', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
    protected function allowAdd($data = []) { return false; }
    protected function allowEdit($data = [], $key = 'id')
    {
        $actor = new \Joomla\CMS\User\User((int) $this->app->getIdentity()->id);
        $actor->clearAccessRights();
        $item = $this->getModel()->getItem((int) ($data[$key] ?? 0));
        return $item && \Jlms\Component\Jlms\Administrator\Application\TeacherProfiles::canEdit($actor, $item);
    }
    protected function getRedirectToListAppend()
    {
        $data = $this->input->post->get('jform', [], 'array');
        return parent::getRedirectToListAppend() . '&id=' . (int) ($data['id'] ?? $this->input->getInt('id'));
    }
}
