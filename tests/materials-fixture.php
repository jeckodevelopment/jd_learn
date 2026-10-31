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
    $fixtureDb->exec("UPDATE joomla_extensions SET enabled=1 WHERE type='plugin' AND folder='jlmsvideo'");
    $fixtureDb->exec("UPDATE joomla_extensions SET params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.mail_to_user',0) WHERE type='plugin' AND folder='user' AND element='joomla'");
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



$mode = $argv[2] ?? '';
if ($mode === 'configure') {
    $changes = json_decode($argv[3] ?? '{}', true, 8, JSON_THROW_ON_ERROR);
    $allowed = ['material_storage_root', 'material_max_mib', 'material_scan_required', 'material_clamd_socket', 'material_php_delivery'];
    if (array_diff(array_keys($changes), $allowed)) { throw new \RuntimeException('Unexpected fixture configuration'); }
    $row = $db->setQuery("SELECT extension_id, params FROM #__extensions WHERE type='component' AND element='com_jlms'")->loadObject();
    $row->params = json_encode(array_replace(json_decode($row->params ?: '{}', true), $changes), JSON_THROW_ON_ERROR);
    $db->updateObject('#__extensions', $row, 'extension_id');echo "configured\n";exit(0);
}
$fixturePath = dirname($root) . '/materials-fixture.json';
if ($mode === 'denied') {
    $fixture = json_decode(file_get_contents($fixturePath), true, 16, JSON_THROW_ON_ERROR);
    try {
        $component->getMaterialService()->administration(new \Joomla\CMS\User\User((int)$fixture['http_user_id']), (int)$fixture['course_id'], (int)$fixture['version_id']);
    } catch (\RuntimeException $error) {
        if ($error->getCode()===403) {echo 'material ACL passed';exit(0);}
        throw $error;
    }
    throw new \RuntimeException('Material-specific ACL bypassed');
}
if ($mode === 'retained') {
    $fixture = json_decode(file_get_contents($fixturePath), true, 16, JSON_THROW_ON_ERROR);
    $course = $db->setQuery('SELECT current_version_id FROM #__jlms_courses WHERE id=' . (int) $fixture['course_id'])->loadObject();
    $count = (int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_attachments WHERE course_version_id=' . (int) $course->current_version_id)->loadResult();
    if ($count < 2) { throw new \RuntimeException('Package update lost materials'); }
    echo json_encode(['materials_upgrade'=>'passed','attachments'=>$count],JSON_THROW_ON_ERROR).PHP_EOL;exit(0);
}
if ($mode === 'inspect') {
    $fixture = json_decode(file_get_contents($fixturePath), true, 16, JSON_THROW_ON_ERROR);
    $rows = $db->setQuery('SELECT a.id, a.title, a.lesson_id, a.asset_id, f.status, f.storage_key, f.sha256, f.scan_json FROM #__jlms_attachments AS a JOIN #__jlms_assets AS f ON f.id=a.asset_id WHERE a.course_version_id='.(int)$fixture['version_id'].' ORDER BY a.id')->loadAssocList();
    echo json_encode(['files'=>$rows],JSON_THROW_ON_ERROR).PHP_EOL;exit(0);
}
$learningFixture = json_decode(file_get_contents(dirname($root) . '/learning-fixture.json'), true, 16, JSON_THROW_ON_ERROR);
$actor = $app->getIdentity();$author = $component->getCurriculumService();
$catid=(int)$db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
$level=(int)$db->setQuery('SELECT content_access FROM #__jlms_courses WHERE id='.(int)$learningFixture['course_id'])->loadResult();
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
$fixture=[];
foreach (['materials-fixture','materials-foreign'] as $alias) {
    if (!$model->save(['id'=>0,'revision'=>1,'title'=>$alias,'alias'=>$alias,'catid'=>$catid,'language'=>'*',
        'catalog_access'=>1,'content_access'=>$level,'enrollment_mode'=>'auto','summary'=>'Protected materials fixture'])) {throw new \RuntimeException($model->getError());}
    $courseId=(int)$model->getState('course.id');$versionId=$author->createDraft($actor,$courseId,1);
    $module=$author->saveModule($actor,['course_id'=>$courseId,'version_id'=>$versionId,'expected_version_revision'=>1,'id'=>0,'title'=>'Materials chapter']);
    $lesson=$author->saveLesson($actor,['course_id'=>$courseId,'version_id'=>$versionId,'expected_version_revision'=>2,'id'=>0,'module_id'=>$module,
        'title'=>'Materials text','kind'=>'text','required'=>'1','body'=>'<p>PROTECTED_MATERIAL_LESSON</p>']);
    $component->getMaterialService()->administration($actor,$courseId,$versionId);
    if ($alias==='materials-fixture') {$fixture=['course_id'=>$courseId,'version_id'=>$versionId,'lesson_id'=>$lesson];}
    else {$fixture['foreign_course_id']=$courseId;$fixture['foreign_version_id']=$versionId;}
}
$fixture['http_user_id']=$learningFixture['http_user_id'];$fixture['group_id']=$learningFixture['group_id'];
file_put_contents($fixturePath,json_encode($fixture,JSON_THROW_ON_ERROR));echo "materials prepared\n";
