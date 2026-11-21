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
    public array $mailScheduler = [];
    public array $schedulerHealth = [];
    public bool $canManageScheduler = false;
    public array $shortcutGroups = [];
    /** Read-only guidance, never actions. Items are shown only with the matching ACL. */
    public array $safeOperations = [];
    /** Non-mutating, local-only configuration checks visible to component managers. */
    public array $configurationDiagnostics = [];

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
                $addShortcut('COM_JLMS_DASHBOARD_GROUP_PEOPLE', 'COM_JLMS_CERTIFICATES', 'fas fa-certificate', 'index.php?option=com_jlms&view=certificates');
            }
            if ($actor->authorise('jlms.audit.view', 'com_jlms')) {
    $addShortcut('COM_JLMS_DASHBOARD_GROUP_COMMUNICATION', 'COM_JLMS_MAIL_QUEUE', 'icon-envelope', 'index.php?option=com_jlms&view=mailqueue');
            }
        }
        if ($actor->authorise('core.manage', 'com_mails') && $actor->authorise('core.edit', 'com_mails')) {
            // Use com_mails' native extension filter; it remains editable in Joomla.
            $addShortcut('COM_JLMS_DASHBOARD_GROUP_COMMUNICATION', 'COM_JLMS_MAIL_TEMPLATES', 'fas fa-envelope',
                'index.php?option=com_mails&view=templates&filter[extension]=com_jlms&filter[search]=&filter[language]=&limitstart=0');
        }
        if ($actor->authorise('core.manage', 'com_jlms')) {
            $addShortcut('COM_JLMS_DASHBOARD_GROUP_COMMUNICATION', 'COM_JLMS_DASHBOARD_MAIL_VARIABLES', 'icon-info-circle', 'index.php?option=com_jlms&view=mailvariables');
        }
        if ($actor->authorise('core.manage', 'com_fields')
            && $actor->authorise('core.manage', 'com_jlms')) {
            $addShortcut('COM_JLMS_DASHBOARD_GROUP_CONFIGURATION', 'COM_JLMS_CUSTOM_FIELDS', 'icon-list', 'index.php?option=com_fields&view=fields&context=com_jlms.course');
        }
        if ($actor->authorise('core.manage', 'com_plugins')) {
            $addShortcut('COM_JLMS_DASHBOARD_GROUP_CONFIGURATION', 'COM_JLMS_VIDEO_PLUGINS', 'icon-plug', 'index.php?option=com_plugins&view=plugins&filter[folder]=jlmsvideo&filter[search]=&filter[enabled]=&filter[access]=&filter[element]=&limitstart=0');
        }
        if ($actor->authorise('core.manage', 'com_jlms')) {
            $addShortcut('COM_JLMS_DASHBOARD_GROUP_CONFIGURATION', 'COM_JLMS_DASHBOARD_CATEGORY_DEPENDENCIES', 'icon-folder', 'index.php?option=com_jlms&view=categoryreassignment');
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
        $this->canManageScheduler = $actor->authorise('core.manage', 'com_scheduler');
        if ($actor->authorise('core.manage', 'com_jlms')) {
            $this->mailScheduler = \Jlms\Component\Jlms\Administrator\Infrastructure\MailSchedulerSetup::status($db);
            $this->schedulerHealth = \Jlms\Component\Jlms\Administrator\Infrastructure\SchedulerHealth::inspect($db);
        }
        // A local-only diagnostic view: no external probes and no credentials in output.
        $this->configurationDiagnostics = $actor->authorise('core.manage', 'com_jlms')
            ? \Jlms\Component\Jlms\Administrator\Application\ConfigurationDiagnostics::summarize($this->schedulerHealth, $this->providers)
            : [];
        if ($actor->authorise('core.manage', 'com_jlms')) {
            $storageRoot = (string) \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('material_storage_root', '');
            $this->configurationDiagnostics[] = \Jlms\Component\Jlms\Administrator\Application\ConfigurationDiagnostics::storage(
                $storageRoot, JPATH_ROOT, defined('JPATH_PUBLIC') ? JPATH_PUBLIC : JPATH_ROOT,
                is_string($_SERVER['DOCUMENT_ROOT'] ?? null) ? $_SERVER['DOCUMENT_ROOT'] : ''
            );
            // Report only fixed diagnostic keys and severity, never raw config values.
            $componentParams = \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms');
            $settingNames = [
                'material_scan_required', 'material_clamd_socket', 'video_delivery_mode',
                'certificate_enabled', 'catalog_page_size', 'catalog_default_order',
                'mail_manager_request_enabled', 'mail_course_update_enabled', 'mail_lesson_release_enabled',
                'mail_teacher_assignment_enabled', 'mail_approval_enabled', 'mail_rejection_enabled',
                'mail_request_enabled', 'mail_enrollment_enabled', 'mail_completion_enabled',
                'mail_suspension_enabled', 'mail_cancellation_enabled', 'mail_reactivation_enabled',
                'mail_certificate_enabled', 'mail_communication_enabled', 'mail_expiry_enabled',
            ];
            $settings = [];
            foreach ($settingNames as $name) {
                $settings[$name] = $componentParams->get($name);
            }
            array_push($this->configurationDiagnostics,
                ...\Jlms\Component\Jlms\Administrator\Application\ConfigurationDiagnostics::operationalSettings($settings));
            array_push($this->configurationDiagnostics,
                ...\Jlms\Component\Jlms\Administrator\Application\ConfigurationDiagnostics::localVideoPrerequisites(
                    (string) $componentParams->get('video_delivery_mode', 'disabled'),
                    (string) $componentParams->get('video_storage_root', ''),
                    (string) $componentParams->get('video_ffprobe_path', ''),
                    JPATH_ROOT, defined('JPATH_PUBLIC') ? JPATH_PUBLIC : JPATH_ROOT,
                    is_string($_SERVER['DOCUMENT_ROOT'] ?? null) ? $_SERVER['DOCUMENT_ROOT'] : ''
                ));

        }
        if ($actor->authorise('core.manage', 'com_jlms')) {
            // Query only keys needed to establish canonical default-template readiness.
            // Never read or render subjects, message bodies, params or recipients.
            $requiredTemplates = \Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateInstaller::templateIds();
            $mailTemplateQuery = $db->getQuery(true)
                ->select($db->quoteName(['template_id', 'language']))
                ->from($db->quoteName('#__mail_templates'))
                ->whereIn($db->quoteName('template_id'), $requiredTemplates, \Joomla\Database\ParameterType::STRING);
            $installedTemplates = array_map(
                static fn (object $row): array => ['template_id' => (string) $row->template_id, 'language' => (string) $row->language],
                $db->setQuery($mailTemplateQuery)->loadObjectList()
            );
            $this->configurationDiagnostics[] = \Jlms\Component\Jlms\Administrator\Application\ConfigurationDiagnostics::mailTemplates(
                $requiredTemplates, $installedTemplates
            );
        }
        if ($actor->authorise('core.manage', 'com_jlms')) {
            // Bounded metadata-only reads: no learner records, names, email
            // addresses or raw settings are ever rendered or logged.
            $courseOptionsQuery = $db->getQuery(true)
                ->select($db->quoteName('params_json'))
                ->from($db->quoteName('#__jlms_courses'))
                ->order($db->quoteName('id') . ' ASC');
            $courseOptions = $db->setQuery($courseOptionsQuery, 0, 501)->loadColumn();

            $menuOptionsQuery = $db->getQuery(true)
                ->select($db->quoteName(['link', 'params']))
                ->from($db->quoteName('#__menu'))
                ->where($db->quoteName('client_id') . ' = 0')
                // Only catalogue/category entries are consumed by inheritance().
                // Other component menus must not exhaust the bounded diagnostic scan.
                ->where('(' . $db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_jlms&view=courses')
                    . ' OR ' . $db->quoteName('link') . ' LIKE ' . $db->quote('index.php?option=com_jlms&view=courses&%')
                    . ' OR ' . $db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_jlms&view=category')
                    . ' OR ' . $db->quoteName('link') . ' LIKE ' . $db->quote('index.php?option=com_jlms&view=category&%') . ')')
                ->order($db->quoteName('id') . ' ASC');
            $menuOptions = array_map(
                static fn (object $item): array => ['link' => (string) $item->link, 'params' => (string) $item->params],
                $db->setQuery($menuOptionsQuery, 0, 501)->loadObjectList()
            );
            $coursesComplete = count($courseOptions) <= 500;
            $menusComplete = count($menuOptions) <= 500;
            array_push($this->configurationDiagnostics,
                ...\Jlms\Component\Jlms\Administrator\Application\ConfigurationDiagnostics::inheritance(
                    array_slice($courseOptions, 0, 500), array_slice($menuOptions, 0, 500),
                    $coursesComplete, $menusComplete
                ));
        }
        // Visibility is based on current ACL rather than the presence of a dashboard shortcut.
        // No diagnostic here triggers sending, deleting, regenerating or mutating data.
        $this->safeOperations = [];
        if ($actor->authorise('core.manage', 'com_jlms')) {
            $this->safeOperations[] = 'STORAGE';
            if ($actor->authorise('jlms.audit.view', 'com_jlms')) {
                $this->safeOperations[] = 'MAIL';
            }
            if ($actor->authorise('jlms.certificates.manage', 'com_jlms')) {
                $this->safeOperations[] = 'CERTIFICATES';
            }
            $this->safeOperations[] = 'COURSE';
        }
        if ($actor->authorise('core.manage', 'com_scheduler')) {
            $this->safeOperations[] = 'SCHEDULER';
        }
        ToolbarHelper::help('dashboard', true);
        parent::display($tpl);
    }
}
