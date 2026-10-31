<?php

declare(strict_types=1);

$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') { throw new \RuntimeException('Disposable Joomla site required'); }
$pdo = new \PDO(getenv('JLMS_TEST_DSN'),getenv('JLMS_TEST_USER'),getenv('JLMS_TEST_PASSWORD'),[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION]);
$state = dirname($root).'/association-http-plugin.json';
if (($argv[2] ?? '') === 'disable') {
    $saved = json_decode(file_get_contents($state),true,16,JSON_THROW_ON_ERROR);
    $query = $pdo->prepare("UPDATE joomla_extensions SET enabled=?,params=? WHERE type='plugin' AND folder='system' AND element='languagefilter'");
    $query->execute([$saved['enabled'],$saved['params']]);
    exit;
}
file_put_contents($state,json_encode($pdo->query("SELECT enabled,params FROM joomla_extensions WHERE type='plugin' AND folder='system' AND element='languagefilter'")->fetch(\PDO::FETCH_ASSOC),JSON_THROW_ON_ERROR));
$pdo->exec("UPDATE joomla_extensions SET enabled=1,params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.item_associations',1,'$.detect_browser',0,'$.remove_default_prefix',0) WHERE type='plugin' AND folder='system' AND element='languagefilter'");
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
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;

$container = Factory::getContainer();
$container->alias('session.web', 'session.web.administrator')
    ->alias('session', 'session.web.administrator')
    ->alias(Session::class, 'session.web.administrator')
    ->alias(SessionInterface::class, 'session.web.administrator');
$app = $container->get(AdministratorApplication::class);
Factory::$application = $app;
$app->loadLanguage($container->get(\Joomla\CMS\Language\LanguageFactoryInterface::class)->createLanguage('en-GB', false));
$app->createExtensionNamespaceMap();
$db = $container->get(DatabaseInterface::class);
$userId = (int) $db->setQuery("SELECT id FROM #__users WHERE username = 'cioperator'")->loadResult();
if (!$userId) {
    throw new \RuntimeException('Test Joomla operator missing.');
}
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserById($userId));
$component = $app->bootComponent('com_jlms');
$factory = $component->getMVCFactory();

$componentId=(int)$db->setQuery("SELECT extension_id FROM #__extensions WHERE type='component' AND element='com_jlms'")->loadResult();
foreach (['en-GB'=>'association-home-en','it-IT'=>'association-home-it'] as $tag=>$alias) {
    $menu=new \Joomla\CMS\Table\Menu($db);
    $menu->setLocation(1,'last-child');
    $menu->bind(['title'=>$alias,'alias'=>$alias,'menutype'=>'mainmenu','type'=>'component','link'=>'index.php?option=com_jlms&view=courses',
        'component_id'=>$componentId,'published'=>1,'access'=>1,'language'=>$tag,'home'=>1,'params'=>'{}','browserNav'=>0]);
    if (!$menu->check() || !$menu->store()) { throw new \RuntimeException('Native language menu fixture failed: '.$menu->getError()); }
}
$module=new \Joomla\CMS\Table\Module($db);
$module->bind(['title'=>'Association language switcher','module'=>'mod_languages','position'=>'sidebar-right','published'=>1,'access'=>1,'language'=>'*',
    'client_id'=>0,'showtitle'=>1,'params'=>'{"dropdown":0,"image":0,"full_name":1,"active":1}','content'=>'','ordering'=>1]);
if (!$module->check() || !$module->store()) { throw new \RuntimeException('Native language switcher fixture failed'); }
$record=(object)['moduleid'=>(int)$module->id,'menuid'=>0];$db->insertObject('#__modules_menu',$record);
echo json_encode(['native_language_module'=>(int)$module->id],JSON_THROW_ON_ERROR).PHP_EOL;
