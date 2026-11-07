<?php

declare(strict_types=1);


/** Run after Joomla's CLI installer and extension:install in an isolated CI site. */
$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new \RuntimeException('Disposable Joomla site required.');
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
$operator = $container->get(UserFactoryInterface::class)->loadUserById($userId);
$app->loadIdentity($operator);
// ComponentHelper::filterText reads the native session user via Factory::getUser().
// A CLI fixture must establish both parts of a web login, otherwise the HTML
// policy is evaluated for Public while MVC authorisation uses the operator.
$app->getSession()->set('user', $operator);
if ((int) Factory::getUser()->id !== (int) $app->getIdentity()->id) {
    throw new \RuntimeException('Fixture session and application identities must match.');
}
$component = $app->bootComponent('com_jlms');
$factory = $component->getMVCFactory();


$checks=0;
function fieldsExpect(bool $ok,string $message): void { global $checks; if (!$ok) { throw new RuntimeException($message); } $checks++; }
$actor=$app->getIdentity();
$catid=(int)$db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
PluginHelper::importPlugin('system','fields');
$fieldModel=$app->bootComponent('com_fields')->getMVCFactory()->createModel('Field','Administrator',['ignore_request'=>true]);
fieldsExpect($fieldModel->save(['id'=>0,'context'=>'com_jlms.course','title'=>'Course editorial reference','name'=>'course-editorial-reference','type'=>'text','state'=>1,'access'=>1,'language'=>'*','group_id'=>0,'default_value'=>'','description'=>'','params'=>[],'fieldparams'=>['filter'=>'string']]),'Native course field created');
$field=(int)$fieldModel->getState('field.id');
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
fieldsExpect($model->save(['id'=>0,'revision'=>1,'title'=>'Native course fields fixture','alias'=>'native-course-fields-fixture','catid'=>$catid,'language'=>'*','catalog_access'=>1,'content_access'=>2,'enrollment_mode'=>'auto','summary'=>'Fields fixture','com_fields'=>['course-editorial-reference'=>'Initial native course value']]),'Create course through native model with custom value');
$course=(int)$model->getState('course.id');
$value=static fn(): string => (string)$db->setQuery('SELECT value FROM #__fields_values WHERE field_id='.$field.' AND item_id='.$db->quote((string)$course))->loadResult();
fieldsExpect($value()==='Initial native course value','Fields plugin persists course value, not a component table');
$record=$model->getItem($course);
fieldsExpect($model->save(['id'=>$course,'revision'=>$record->revision,'title'=>'Native course fields fixture updated','com_fields'=>['course-editorial-reference'=>'Updated native course value']]),'Course update carries native custom value');
fieldsExpect($value()==='Updated native course value','Updated custom value persists');
$record=$model->getItem($course);
fieldsExpect($model->save(['id'=>$course,'revision'=>$record->revision,'summary'=>'Metadata-only correction']),'Legacy metadata client without custom fields still saves');
fieldsExpect($value()==='Updated native course value','Omitted custom fields do not erase existing values');
$before=$value();$record=$model->getItem($course);
fieldsExpect(!$model->save(['id'=>$course,'revision'=>(int)$record->revision-1,'com_fields'=>['course-editorial-reference'=>'Stale overwrite']]),'Stale course revision rejected');
fieldsExpect($value()===$before,'Rejected stale edit cannot change field value');
file_put_contents(dirname($root).'/course-fields-fixture.json',json_encode(['course'=>$course,'field'=>$field],JSON_THROW_ON_ERROR));
echo json_encode(['course_fields_native_checks'=>$checks,'status'=>'passed']).PHP_EOL;
