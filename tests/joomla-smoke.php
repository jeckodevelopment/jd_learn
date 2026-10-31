<?php

declare(strict_types=1);


/** Run after Joomla's CLI installer and extension:install in an isolated CI site. */
$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new \RuntimeException('Disposable Joomla site required.');
}
\define('_JEXEC', 1);
\define('JPATH_BASE', $root);
require $root . '/includes/defines.php';
require $root . '/includes/framework.php';

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
$app->createExtensionNamespaceMap();
$db = $container->get(DatabaseInterface::class);
$userId = (int) $db->setQuery("SELECT id FROM #__users WHERE username = 'cioperator'")->loadResult();
if (!$userId) {
    throw new \RuntimeException('Test Joomla operator missing.');
}
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserById($userId));
$component = $app->bootComponent('com_jlms');
$factory = $component->getMVCFactory();
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
$db->setQuery("UPDATE #__extensions SET enabled=1 WHERE type='plugin' AND folder='jlmsvideo'")->execute();
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
echo json_encode(['joomla'=>JVERSION,'status'=>'passed','native_course_id'=>$id,'providers'=>$keys,'draft_revision_conflict'=>'denied','mass_assignment'=>'denied'],JSON_THROW_ON_ERROR) . PHP_EOL;
