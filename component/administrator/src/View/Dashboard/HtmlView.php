<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\View\Dashboard;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;
use Jlms\Component\Jlms\Administrator\Application\DashboardProviderStatus;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public array $providers = [];
    public array $shortcutGroups = [];

    public function display($tpl = null)
    {
        $actor=\Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::fresh(Factory::getApplication()->getIdentity());
        ToolbarHelper::title('JD Learn', 'graduation-cap');
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.admin-dashboard');
        // Each section is rendered only when the current actor can access a shortcut.
        $this->shortcutGroups = [];
        $addShortcut = function (string $group, string $key, string $icon, string $url): void {
            $this->shortcutGroups[$group][] = [
                'key' => $key,
                'icon' => $icon,
                'url' => \Joomla\CMS\Router\Route::_($url),
            ];
        };
        if ($actor->authorise('core.manage', 'com_jlms')) {
            $addShortcut('COM_JLMS_DASHBOARD_GROUP_DIDACTICS', 'COM_JLMS_COURSES', 'icon-book', 'index.php?option=com_jlms&view=courses');
            if ($actor->authorise('jlms.pathways.manage', 'com_jlms')) {
                $addShortcut('COM_JLMS_DASHBOARD_GROUP_DIDACTICS', 'COM_JLMS_PATHWAYS', 'icon-map-signs', 'index.php?option=com_jlms&view=pathways');
            }
        }
        if ($actor->authorise('core.manage', 'com_categories')
            && $actor->authorise('core.manage', 'com_jlms.course')) {
            $addShortcut('COM_JLMS_DASHBOARD_GROUP_DIDACTICS', 'COM_JLMS_COURSE_CATEGORIES', 'icon-folder', 'index.php?option=com_categories&view=categories&extension=com_jlms.course');
        }
        if ($actor->authorise('jlms.pathways.manage', 'com_jlms')
            && $actor->authorise('core.manage', 'com_categories')
            && $actor->authorise('core.manage', 'com_jlms.pathway')) {
            $addShortcut('COM_JLMS_DASHBOARD_GROUP_DIDACTICS', 'COM_JLMS_PATHWAY_CATEGORIES', 'icon-folder', 'index.php?option=com_categories&view=categories&extension=com_jlms.pathway');
        }
        if ($actor->authorise('core.manage', 'com_jlms')) {
            $addShortcut('COM_JLMS_DASHBOARD_GROUP_PEOPLE', 'COM_JLMS_TEACHERS', 'icon-users', 'index.php?option=com_jlms&view=teachers');
            if ($actor->authorise('jlms.quiz.manage', 'com_jlms')) {
                $addShortcut('COM_JLMS_DASHBOARD_GROUP_PEOPLE', 'COM_JLMS_QUESTION_BANK', 'icon-question-circle', 'index.php?option=com_jlms&view=questionbank');
            }
            if ($actor->authorise('jlms.certificates.manage', 'com_jlms')) {
                $addShortcut('COM_JLMS_DASHBOARD_GROUP_PEOPLE', 'COM_JLMS_CERTIFICATES', 'icon-certificate', 'index.php?option=com_jlms&view=certificates');
            }
            $addShortcut('COM_JLMS_DASHBOARD_GROUP_COMMUNICATION', 'COM_JLMS_MAIL_QUEUE', 'icon-envelope', 'index.php?option=com_jlms&view=mailqueue');
        }
        if ($actor->authorise('core.manage', 'com_mails') && $actor->authorise('core.edit', 'com_mails')) {
            // Use com_mails' native extension filter; it remains editable in Joomla.
            $addShortcut('COM_JLMS_DASHBOARD_GROUP_COMMUNICATION', 'COM_JLMS_MAIL_TEMPLATES', 'icon-file-edit',
                'index.php?option=com_mails&view=templates&filter[extension]=com_jlms&filter[search]=&filter[language]=&limitstart=0');
        }
        if ($actor->authorise('core.manage', 'com_fields')
            && $actor->authorise('core.manage', 'com_jlms')) {
            $addShortcut('COM_JLMS_DASHBOARD_GROUP_CONFIGURATION', 'COM_JLMS_CUSTOM_FIELDS', 'icon-list', 'index.php?option=com_fields&view=fields&context=com_jlms.course');
        }
        if ($actor->authorise('core.manage', 'com_plugins')) {
            $addShortcut('COM_JLMS_DASHBOARD_GROUP_CONFIGURATION', 'COM_JLMS_VIDEO_PLUGINS', 'icon-plug', 'index.php?option=com_plugins&view=plugins&filter[folder]=jlmsvideo&filter[search]=&filter[enabled]=&filter[access]=&filter[element]=&limitstart=0');
        }
        if ($actor->authorise('core.options','com_jlms') || $actor->authorise('core.admin','com_jlms')) { ToolbarHelper::preferences('com_jlms'); }
        $registry = new VideoProviderRegistry();
        PluginHelper::importPlugin('jlmsvideo');
        Factory::getApplication()->getDispatcher()->dispatch(
            RegisterVideoProvidersEvent::NAME,
            new RegisterVideoProvidersEvent($registry),
        );
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $extensions = $db->getQuery(true)
            ->select($db->quoteName(['element', 'name', 'enabled']))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('jlmsvideo'))
            ->where($db->quoteName('client_id') . ' = 0');
        $extensionMap = $db->setQuery($extensions)->loadObjectList('element');
        $this->providers = DashboardProviderStatus::summarize($extensionMap, $registry->descriptors());
        parent::display($tpl);
    }
}
