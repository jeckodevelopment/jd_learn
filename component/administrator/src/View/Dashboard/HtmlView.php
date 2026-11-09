<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\View\Dashboard;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;

final class HtmlView extends BaseHtmlView
{
    public array $providers = [];
    public array $shortcuts = [];

    public function display($tpl = null)
    {
        $actor=\Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::fresh(Factory::getApplication()->getIdentity());
        ToolbarHelper::title('JD Learn', 'graduation-cap');
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.admin-dashboard');
        $this->shortcuts = [];
        $addShortcut = function (string $key, string $icon, string $url) : void {
            $this->shortcuts[] = ['key' => $key, 'icon' => $icon, 'url' => \Joomla\CMS\Router\Route::_($url)];
        };
        if ($actor->authorise('core.manage', 'com_jlms')) {
            $addShortcut('COM_JLMS_COURSES', 'icon-book', 'index.php?option=com_jlms&view=courses');
            if ($actor->authorise('jlms.quiz.manage', 'com_jlms')) {
                $addShortcut('COM_JLMS_QUESTION_BANK', 'icon-question-circle', 'index.php?option=com_jlms&view=questionbank');
            }
            $addShortcut('COM_JLMS_TEACHERS', 'icon-users', 'index.php?option=com_jlms&view=teachers');
        }
        if ($actor->authorise('core.manage', 'com_categories')
            && $actor->authorise('core.manage', 'com_jlms.course')) {
            $addShortcut('COM_JLMS_COURSE_CATEGORIES', 'icon-folder', 'index.php?option=com_categories&view=categories&extension=com_jlms.course');
        }
        if ($actor->authorise('core.manage', 'com_fields')
            && $actor->authorise('core.manage', 'com_jlms')) {
            $addShortcut('COM_JLMS_CUSTOM_FIELDS', 'icon-list', 'index.php?option=com_fields&view=fields&context=com_jlms.course');
        }
        if ($actor->authorise('core.manage', 'com_plugins')) {
            $addShortcut('COM_JLMS_VIDEO_PLUGINS', 'icon-plug', 'index.php?option=com_plugins&view=plugins&filter[folder]=jlmsvideo&filter[search]=&filter[enabled]=&filter[access]=&filter[element]=&limitstart=0');
        }
        if ($actor->authorise('core.options','com_jlms') || $actor->authorise('core.admin','com_jlms')) { ToolbarHelper::preferences('com_jlms'); }
        $registry = new VideoProviderRegistry();
        PluginHelper::importPlugin('jlmsvideo');
        Factory::getApplication()->getDispatcher()->dispatch(
            RegisterVideoProvidersEvent::NAME,
            new RegisterVideoProvidersEvent($registry),
        );
        $this->providers = $registry->descriptors();
        parent::display($tpl);
    }
}
