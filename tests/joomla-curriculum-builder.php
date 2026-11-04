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


$service = $component->getCurriculumService();
$actor = $app->getIdentity();
$checks = 0;
function builderExpect(bool $ok, string $message): void { global $checks; if (!$ok) { throw new RuntimeException($message); } $checks++; }
function builderDenied(callable $work, string $key): void {
    try { $work(); } catch (\Throwable $e) { builderExpect($e->getMessage() === $key, 'Expected ' . $key . ', got ' . $e->getMessage()); return; }
    throw new RuntimeException('Expected rejection: ' . $key);
}
function builderRow(string $table, int $id): object { global $db; return $db->setQuery('SELECT * FROM #__jlms_' . $table . ' WHERE id=' . $id)->loadObject(); }
function builderSnapshot(int $course, int $version): array {
    global $db;
    return [$db->setQuery('SELECT * FROM #__jlms_courses WHERE id=' . $course)->loadAssoc(),
        $db->setQuery('SELECT * FROM #__jlms_course_versions WHERE id=' . $version)->loadAssoc(),
        $db->setQuery('SELECT * FROM #__jlms_modules WHERE course_version_id=' . $version . ' ORDER BY id')->loadAssocList(),
        $db->setQuery('SELECT * FROM #__jlms_lessons WHERE course_version_id=' . $version . ' ORDER BY id')->loadAssocList()];
}
$file = dirname($root) . '/curriculum-builder-fixture.json';
$mode = $argv[2] ?? '';
if (in_array($mode, ['checkpoint','retained','state'], true)) {
    $f = json_decode(file_get_contents($file), true, 64, JSON_THROW_ON_ERROR);
    $snapshot = builderSnapshot($f['course'], $f['version']);
    if ($mode === 'state') { echo json_encode($snapshot, JSON_THROW_ON_ERROR); return; }
    if ($mode === 'checkpoint') { $f['snapshot'] = $snapshot; file_put_contents($file, json_encode($f, JSON_THROW_ON_ERROR)); return; }
    builderExpect($f['snapshot'] === $snapshot, 'Builder ordering and moves retained after package upgrade');
    echo json_encode(['curriculum_builder_retained_checks'=>$checks,'status'=>'passed']) . PHP_EOL; return;
}
$source = (int) $db->setQuery("SELECT id FROM #__jlms_courses WHERE alias='quiz-runtime-fixture'")->loadResult();
$original = builderRow('courses', $source); $published = builderRow('course_versions', (int)$original->current_version_id);
$sourceBefore = builderSnapshot($source, (int)$published->id);
$learning = static fn(): array => [$db->setQuery('SELECT * FROM #__jlms_enrollments ORDER BY id')->loadAssocList(),$db->setQuery('SELECT * FROM #__jlms_attempts ORDER BY id')->loadAssocList(),$db->setQuery('SELECT * FROM #__jlms_lesson_progress ORDER BY enrollment_id,lesson_id')->loadAssocList()];
$learningBefore = $learning();
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
$course=$model->duplicate(['course_id'=>$source,'version_id'=>$published->id,'revision'=>$original->revision,'expected_version_revision'=>$published->revision,'title'=>'Curriculum builder fixture']);
$outline=$service->outline($actor,$course); $version=(int)$outline['version']->id;
$scope = static fn(): array => ['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>builderRow('course_versions',$version)->revision];
$m1=$service->saveModule($actor,$scope()+['id'=>0,'title'=>'Builder module one']);
$m2=$service->saveModule($actor,$scope()+['id'=>0,'title'=>'Builder module two']);
$l1=$service->saveLesson($actor,$scope()+['id'=>0,'module_id'=>$m1,'title'=>'Builder lesson one','kind'=>'text','body'=>'<p>Preserve this body</p>','required'=>'1']);
$l2=$service->saveLesson($actor,$scope()+['id'=>0,'module_id'=>$m1,'title'=>'Builder lesson two','kind'=>'text','body'=>'<p>Second body</p>','required'=>'0']);
$outline=$service->outline($actor,$course,$version); $ids=array_map(static fn($m): int => (int)$m->id,$outline['modules']);$order=array_reverse($ids);
$stale=$scope()+['kind'=>'module','order'=>$order];$revision=$service->saveOrder($actor,$stale);
builderExpect($revision===(int)$stale['expected_version_revision']+1,'One version bump for an atomic sibling permutation');
builderExpect(array_map(static fn($m): int => (int)$m->id,$service->outline($actor,$course,$version)['modules'])===$order,'Persisted module order matches the submitted exact permutation');
builderDenied(fn()=>$service->saveOrder($actor,$stale),'COM_JLMS_REVISION_CONFLICT');
$stable=builderSnapshot($course,$version);
builderDenied(fn()=>$service->saveOrder($actor,$scope()+['kind'=>'module','order_json'=>'{invalid']),'COM_JLMS_INVALID_INPUT');
builderDenied(fn()=>$service->saveOrder($actor,$scope()+['kind'=>'module','order_json'=>'null']),'COM_JLMS_INVALID_INPUT');
builderDenied(fn()=>$service->saveOrder($actor,$scope()+['kind'=>'module','order'=>[$m1,$m1]]),'COM_JLMS_INVALID_INPUT');
builderDenied(fn()=>$service->saveOrder($actor,$scope()+['kind'=>'module','order'=>[$m1]]),'COM_JLMS_REVISION_CONFLICT');
builderDenied(fn()=>$service->saveOrder($actor,$scope()+['kind'=>'module','order'=>array_merge($ids,[2147483647])]),'COM_JLMS_REVISION_CONFLICT');
builderExpect(builderSnapshot($course,$version)===$stable,'Rejected permutations change neither records nor revisions');
$lessonOrder=$scope()+['kind'=>'lesson','module_id'=>$m1,'order'=>[$l2,$l1]];
$service->saveOrder($actor,$lessonOrder);
builderExpect(array_map(static fn($l): int => (int)$l->id,array_values(array_filter($service->outline($actor,$course,$version)['lessons'],static fn($l): bool => (int)$l->module_id===$m1)))===[$l2,$l1],'Lesson order persists within its module');
$old=builderRow('lessons',$l1);$move=$scope()+['id'=>$l1,'revision'=>$old->revision,'module_id'=>$m2];$service->moveLesson($actor,$move);$new=builderRow('lessons',$l1);
builderExpect((int)$new->module_id===$m2 && (int)$new->revision===(int)$old->revision+1,'Move preserves identity and bumps lesson revision');
foreach (['logical_key','course_version_id','body','kind','required','source_revision','source_json','media_asset_id'] as $key) { builderExpect($new->$key===$old->$key,'Move preserves '.$key); }
builderDenied(fn()=>$service->moveLesson($actor,$move),'COM_JLMS_REVISION_CONFLICT');
$foreignModule=(int)$sourceBefore[2][0]['id'];
builderDenied(fn()=>$service->moveLesson($actor,$scope()+['id'=>$l1,'revision'=>$new->revision,'module_id'=>$foreignModule]),'COM_JLMS_RESOURCE_NOT_FOUND');
$quiz=array_values(array_filter($service->outline($actor,$course,$version)['lessons'],static fn($l): bool => $l->kind==='quiz'))[0];
$quizBefore=$db->setQuery('SELECT * FROM #__jlms_quizzes WHERE lesson_id='.(int)$quiz->id)->loadAssocList();
$service->moveLesson($actor,$scope()+['id'=>$quiz->id,'revision'=>$quiz->revision,'module_id'=>$m2]);
builderExpect($quizBefore===$db->setQuery('SELECT * FROM #__jlms_quizzes WHERE lesson_id='.(int)$quiz->id)->loadAssocList(),'Quiz move keeps definition and question revision references intact');
$guest=new \Joomla\CMS\User\User(0);
builderDenied(fn()=>$service->saveOrder($guest,$scope()+['kind'=>'module','order'=>$order]),'JERROR_ALERTNOAUTHOR');
builderDenied(fn()=>$service->moveLesson($guest,$scope()+['id'=>$l1,'revision'=>$new->revision,'module_id'=>$m1]),'JERROR_ALERTNOAUTHOR');
$other=(int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearnerhttp'")->loadResult();
builderExpect($other>0,'Disposable learner exists for checkout guard');
$db->updateObject('#__jlms_courses',(object)['id'=>$course,'checked_out'=>$other],'id');
try { builderDenied(fn()=>$service->saveOrder($actor,$scope()+['kind'=>'module','order'=>$order]),'JERROR_ALERTNOAUTHOR'); }
finally { $db->updateObject('#__jlms_courses',(object)['id'=>$course,'checked_out'=>null],'id',true); }
$db->updateObject('#__jlms_lessons',(object)['id'=>$l1,'checked_out'=>$other],'id');
try { builderDenied(fn()=>$service->moveLesson($actor,$scope()+['id'=>$l1,'revision'=>$new->revision,'module_id'=>$m1]),'JERROR_ALERTNOAUTHOR'); }
finally { $db->updateObject('#__jlms_lessons',(object)['id'=>$l1,'checked_out'=>null],'id',true); }
$db->updateObject('#__jlms_course_versions',(object)['id'=>$version,'checked_out'=>$other],'id');
try { builderDenied(fn()=>$service->moveLesson($actor,$scope()+['id'=>$l1,'revision'=>$new->revision,'module_id'=>$m1]),'JERROR_ALERTNOAUTHOR'); }
finally { $db->updateObject('#__jlms_course_versions',(object)['id'=>$version,'checked_out'=>null],'id',true); }
builderDenied(fn()=>$service->saveOrder($actor,['course_id'=>$source,'version_id'=>$published->id,'expected_version_revision'=>$published->revision,'kind'=>'module','order'=>array_column($sourceBefore[2],'id')]),'COM_JLMS_VERSION_IMMUTABLE');
builderExpect($sourceBefore===builderSnapshot($source,(int)$published->id),'Published source untouched by every draft operation');
builderExpect($learningBefore===$learning(),'No enrollment, attempt or progress mutation');
file_put_contents($file,json_encode(['course'=>$course,'version'=>$version,'m1'=>$m1,'m2'=>$m2,'l1'=>$l1,'l2'=>$l2,'source'=>$source,'source_version'=>(int)$published->id,'snapshot'=>builderSnapshot($course,$version)],JSON_THROW_ON_ERROR));
echo json_encode(['curriculum_builder_native_checks'=>$checks,'status'=>'passed']) . PHP_EOL;
