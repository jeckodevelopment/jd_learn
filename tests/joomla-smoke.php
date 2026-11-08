<?php

declare(strict_types=1);


/** Run after Joomla's CLI installer and extension:install in an isolated CI site. */
$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new \RuntimeException('Disposable Joomla site required.');
}
// Set fixture plugin state before Joomla caches its enabled-plugin list.
// The retained-data mode must not alter state, so it can detect upgrade regressions.
if (($argv[2] ?? '') !== 'retained') {
    $fixtureDb = new \PDO(getenv('JLMS_TEST_DSN'), getenv('JLMS_TEST_USER'), getenv('JLMS_TEST_PASSWORD'), [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    $fixtureDb->exec("UPDATE joomla_extensions SET enabled=1 WHERE type='plugin' AND (folder='jlmsvideo' OR (folder='privacy' AND element='jdlearn'))");
    unset($fixtureDb);
}

// Simulate the web request context required by AdministratorApplication in a CLI smoke test.
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/administrator/index.php?option=com_jlms';
$_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
$_SERVER['PHP_SELF'] = '/administrator/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/administrator/index.php';
\define('_JEXEC', 1);
\define('JPATH_BASE', $root . '/administrator');
require $root . '/administrator/includes/defines.php';
require $root . '/administrator/includes/framework.php';
\define('JPATH_COMPONENT', JPATH_ADMINISTRATOR . '/components/com_jlms');
\define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_COMPONENT);
\define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/com_jlms');

use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Event\Privacy\CollectCapabilitiesEvent;
use Joomla\CMS\Event\Privacy\CanRemoveDataEvent;
use Joomla\CMS\Event\Privacy\ExportRequestEvent;
use Joomla\CMS\Event\Privacy\RemoveDataEvent;
use Joomla\Component\Privacy\Administrator\Table\RequestTable;
use Joomla\CMS\Language\Text;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;

$container = Factory::getContainer();
$container->alias('session.web', 'session.web.administrator')
    ->alias('session', 'session.web.administrator')
    ->alias(Session::class, 'session.web.administrator')
    ->alias(SessionInterface::class, 'session.web.administrator');
$app = $container->get(AdministratorApplication::class);
Factory::$application = $app;
$app->createExtensionNamespaceMap();
$db = $container->get(DatabaseInterface::class);
// Native extension installer must create the submenu once and preserve it on upgrade.
$menuParent = (int) $db->setQuery("SELECT id FROM #__menu WHERE client_id=1 AND menutype='main' AND link='index.php?option=com_jlms&view=courses' AND parent_id=1")->loadResult();
$menuLinks = $db->setQuery('SELECT link FROM #__menu WHERE client_id=1 AND parent_id=' . $menuParent)->loadColumn();
$expectedLinks = ['index.php?option=com_jlms&view=courses', 'index.php?option=com_categories&view=categories&extension=com_jlms.course', 'index.php?option=com_jlms&view=questionbank', 'index.php?option=com_jlms&view=teachers', 'index.php?option=com_plugins&view=plugins&filter[folder]=jlmsvideo&filter[search]=&filter[enabled]=&filter[access]=&filter[element]=&limitstart=0'];
sort($menuLinks); sort($expectedLinks);
if (!$menuParent || $menuLinks !== $expectedLinks) { throw new \RuntimeException('Native administrator menu missing or duplicated after installation/upgrade.'); }


$userId = (int) $db->setQuery("SELECT id FROM #__users WHERE username = 'cioperator'")->loadResult();
if (!$userId) {
    throw new \RuntimeException('Test Joomla operator missing.');
}
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserById($userId));

// The package must install and activate JD Learn's native Joomla Privacy event subscriber.
PluginHelper::importPlugin('privacy');
$privacyCapabilities = new CollectCapabilitiesEvent('onPrivacyCollectAdminCapabilities');
$app->getDispatcher()->dispatch('onPrivacyCollectAdminCapabilities', $privacyCapabilities);
$privacyData = $privacyCapabilities->getArgument('result', []);
$privacyTitle = Text::_('PLG_PRIVACY_JDLEARN_CAPABILITY_TITLE');
$hasPrivacyCapabilities = false;
foreach ($privacyData as $capabilitySet) {
    if (is_array($capabilitySet) && array_key_exists($privacyTitle, $capabilitySet)) {
        $hasPrivacyCapabilities = true;
        break;
    }
}
if (!$hasPrivacyCapabilities) {
    throw new \RuntimeException('JD Learn privacy capabilities were not collected by Joomla.');
}
$privacyRequest = new RequestTable($db);
$privacyExport = new ExportRequestEvent('onPrivacyExportRequest', ['subject' => $privacyRequest, 'user' => $app->getIdentity()]);
$app->getDispatcher()->dispatch('onPrivacyExportRequest', $privacyExport);
$privacyExportData = $privacyExport->getArgument('result', []);
$privacyDomainNames = [];
foreach ($privacyExportData as $contribution) {
    if (!is_array($contribution)) {
        continue;
    }

    foreach ($contribution as $domain) {
        if ($domain instanceof \Joomla\Component\Privacy\Administrator\Export\Domain && str_starts_with($domain->name, 'jdlearn_')) {
            $privacyDomainNames[] = $domain->name;
        }
    }
}
sort($privacyDomainNames);
$expectedPrivacyDomainNames = ['jdlearn_answers', 'jdlearn_attempts', 'jdlearn_certificates', 'jdlearn_enrollments', 'jdlearn_enrollment_requests', 'jdlearn_progress'];
sort($expectedPrivacyDomainNames);
if ($privacyDomainNames !== $expectedPrivacyDomainNames) {
    throw new \RuntimeException('JD Learn privacy export domains missing: ' . implode(', ', $privacyDomainNames));
}
$privacyPlugin = new \Jlms\Plugin\Privacy\Jdlearn\Extension\Jdlearn((array) PluginHelper::getPlugin('privacy', 'jdlearn'));
$privacyPlugin->setApplication($app);
$privacyPlugin->setDatabase($db);
$privacyCheck = new CanRemoveDataEvent('onPrivacyCanRemoveData', ['subject' => $privacyRequest, 'user' => $app->getIdentity()]);
$privacyPlugin->onPrivacyCanRemoveData($privacyCheck);
$privacyCheckData = $privacyCheck->getArgument('result', []);
if (count($privacyCheckData) !== 1 || !$privacyCheckData[0]->canRemove) {
    throw new \RuntimeException('JD Learn privacy plugin blocked removal for a user without JD Learn data.');
}
$db->setQuery("INSERT INTO #__jlms_jobs (kind, owner_user_id, scope_json, payload_json, status, available_at, created_at) VALUES ('privacy-fixture', " . (int) $userId . ", '{}', '{}', 'complete', UTC_TIMESTAMP(6), UTC_TIMESTAMP(6))")->execute();
$privacyBlocked = new CanRemoveDataEvent('onPrivacyCanRemoveData', ['subject' => $privacyRequest, 'user' => $app->getIdentity()]);
$privacyPlugin->onPrivacyCanRemoveData($privacyBlocked);
$privacyBlockedData = $privacyBlocked->getArgument('result', []);
$db->setQuery("DELETE FROM #__jlms_jobs WHERE kind='privacy-fixture' AND owner_user_id=" . (int) $userId)->execute();
if (count($privacyBlockedData) !== 1 || $privacyBlockedData[0]->canRemove || !$privacyBlockedData[0]->reason) {
    throw new \RuntimeException('JD Learn privacy plugin did not explain the removal blocker for an owned job.');
}
$privacyRemoval = new RemoveDataEvent('onPrivacyRemoveData', ['subject' => $privacyRequest, 'user' => $app->getIdentity()]);
$privacyPlugin->onPrivacyRemoveData($privacyRemoval);
$component = $app->bootComponent('com_jlms');
$factory = $component->getMVCFactory();
if (($argv[2] ?? '') === 'retained') {
    $record = $db->setQuery("SELECT title, revision FROM #__jlms_courses WHERE alias='test-course'")->loadObject();
    $enabled = (int) $db->setQuery("SELECT COUNT(*) FROM #__extensions WHERE type='plugin' AND folder='jlmsvideo' AND enabled=1")->loadResult();
    if (!$record || $record->title !== 'Updated course' || (int) $record->revision !== 2 || $enabled !== 4) {
        throw new \RuntimeException('Package update lost draft data or plugin state.');
    }
    echo json_encode(['admin_menu_install_checks' => 1, 'package_upgrade' => 'passed', 'draft_revision' => 2, 'enabled_providers' => $enabled], JSON_THROW_ON_ERROR) . PHP_EOL;
    exit(0);
}

$categoryModel = $app->bootComponent('com_categories')->getMVCFactory()->createModel('Category', 'Administrator', ['ignore_request'=>true]);
if (!$categoryModel->save(['id'=>0,'parent_id'=>1,'extension'=>'com_jlms.course','title'=>'CI Courses','alias'=>'ci-courses','published'=>1,'language'=>'*','access'=>1])) {
    throw new \RuntimeException('Native Joomla category save failed: ' . $categoryModel->getError());
}
$catid = (int) $categoryModel->getState('category.id');
$model = $factory->createModel('Course','Administrator',['ignore_request'=>true]);
$form = $model->getForm([], false);
if (!$form) {
    throw new \RuntimeException('Joomla course form failed to load.');
}
$data=['id'=>0,'title'=>'Test course','alias'=>'test-course','catid'=>$catid,'language'=>'*','catalog_access'=>1,'content_access'=>2,'enrollment_mode'=>'auto','summary'=>'Summary','revision'=>1];
if (!$model->save($data + ['state'=>1,'asset_id'=>999999,'current_version_id'=>999999])) {
    throw new \RuntimeException('Native draft save failed: ' . $model->getError());
}
$id = (int) $model->getState('course.id');
$record = $model->getItem($id);
if ($id < 1 || (int) $record->state !== 0 || $record->current_version_id !== null || (int) $record->revision !== 1) {
    throw new \RuntimeException('Draft mass assignment or initial revision invariant failed.');
}
$parent = (int) $db->setQuery('SELECT asset_id FROM #__categories WHERE id=' . $catid)->loadResult();
$actual = (int) $db->setQuery('SELECT parent_id FROM #__assets WHERE id=' . (int)$record->asset_id)->loadResult();
if ($parent !== $actual) {
    throw new \RuntimeException('Course asset does not inherit native category ACL.');
}
$data['id']=$id;$data['title']='Updated course';
if (!$model->save($data)) {
    throw new \RuntimeException('Draft update failed: ' . $model->getError());
}
if ($model->save($data)) {
    throw new \RuntimeException('Obsolete revision overwrote a newer draft.');
}
// Enable providers only inside the disposable fixture to exercise Joomla's real plugin boot/dispatcher.
PluginHelper::importPlugin('jlmsvideo');
$registry = new VideoProviderRegistry();
$app->getDispatcher()->dispatch(RegisterVideoProvidersEvent::NAME,new RegisterVideoProvidersEvent($registry));
$keys=array_map(static fn ($x): string => $x->key,$registry->descriptors());sort($keys);
if ($keys!==['cloudflarestream','html5','vimeo','youtube']) {
    throw new \RuntimeException('Four video providers were not registered by Joomla.');
}
// Instantiate all concrete MVC classes so incompatible inheritance signatures fail in CI.
foreach (['Dashboard','Courses','Course'] as $view) {
    $factory->createView($view,'Administrator','Html');
}
$factory->createView('Courses','Site','Html');
$categoryService=$component->getCategory([], 'course');
if (!$categoryService) {
    throw new \RuntimeException('Native category service failed.');
}
echo json_encode(['admin_menu_install_checks'=>1,'joomla'=>JVERSION,'status'=>'passed','native_course_id'=>$id,'providers'=>$keys,'draft_revision_conflict'=>'denied','mass_assignment'=>'denied'],JSON_THROW_ON_ERROR) . PHP_EOL;
