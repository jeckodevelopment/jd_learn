<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Site\View\Teacheredit;

final class HtmlView extends \Joomla\CMS\MVC\View\HtmlView
{
    public object $item;
    public $form;
    public function display($tpl = null)
    {
        $actor = new \Joomla\CMS\User\User((int) \Joomla\CMS\Factory::getApplication()->getIdentity()->id);
        $actor->clearAccessRights();
        $this->item = $this->get('Item');
        if (!$this->item || !\Jlms\Component\Jlms\Administrator\Application\TeacherProfiles::canEdit($actor, $this->item)) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }
        $this->form = $this->get('Form');
        if (!$actor->authorise('jlms.teachers.manage', 'com_jlms')) {
            foreach (['user_id', 'state', 'access', 'language'] as $field) { $this->form->setFieldAttribute($field, 'disabled', 'true'); }
        }
        \Joomla\CMS\Factory::getApplication()->setHeader('Cache-Control', 'private, no-store', true);
        return parent::display($tpl);
    }
}
