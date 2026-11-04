<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Site\View\Teacher;

final class HtmlView extends \Joomla\CMS\MVC\View\HtmlView
{
    public object $profile;
    public array $items;
    public $pagination;
    public bool $canEditProfile = false;
    public function display($tpl = null)
    {
        $this->profile = $this->get('Profile');
        $app = \Joomla\CMS\Factory::getApplication();
        if ($app->getIdentity()->id) {
            $item = $app->bootComponent('com_jlms')->getMVCFactory()->createModel('Teacher', 'Administrator', ['ignore_request' => true])->getItem((int) $this->profile->id);
            $this->canEditProfile = \Jlms\Component\Jlms\Administrator\Application\TeacherProfiles::canEdit($app->getIdentity(), $item);
        }
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->getDocument()->setTitle($this->profile->title);
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        return parent::display($tpl);
    }
}
