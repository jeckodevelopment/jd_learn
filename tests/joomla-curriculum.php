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
$app->createExtensionNamespaceMap();
$db = $container->get(DatabaseInterface::class);
$userId = (int) $db->setQuery("SELECT id FROM #__users WHERE username = 'cioperator'")->loadResult();
if (!$userId) {
    throw new \RuntimeException('Test Joomla operator missing.');
}
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserById($userId));
$component = $app->bootComponent('com_jlms');
$factory = $component->getMVCFactory();

$service = $component->getCurriculumService();
$actor = $app->getIdentity();
$catid = (int) $db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
$model = $factory->createModel('Course', 'Administrator', ['ignore_request' => true]);
$data = ['id'=>0,'title'=>'Curriculum fixture','alias'=>'curriculum-test','catid'=>$catid,'language'=>'*',
    'catalog_access'=>1,'content_access'=>2,'enrollment_mode'=>'auto','summary'=>'Fixture','revision'=>1];
if (!$model->save($data)) {
    throw new \RuntimeException('Curriculum fixture course failed: ' . $model->getError());
}
$course = (int) $model->getState('course.id');
$version = $service->createDraft($actor, $course, 1);
$checks = 0;
function curriculumExpect(bool $ok, string $message): void {
    global $checks;
    if (!$ok) { throw new \RuntimeException($message); }
    $checks++;
}
function curriculumDenied(callable $call, string $message): void {
    try { $call(); } catch (\DomainException | \InvalidArgumentException $expected) { curriculumExpect(true, $message); return; }
    throw new \RuntimeException($message);
}
function versionRevision(): int {
    global $db, $version;
    return (int) $db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id=' . $version)->loadResult();
}
function scopeInput(): array {
    global $course, $version;
    return ['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>versionRevision()];
}
function recordRevision(string $table, int $id): int {
    global $db;
    return (int) $db->setQuery('SELECT revision FROM #__jlms_' . $table . ' WHERE id=' . $id)->loadResult();
}
curriculumExpect($service->createDraft($actor,$course,1)===$version,'repeat draft start must reuse existing draft');
$stale = scopeInput();
$module = $service->saveModule($actor, scopeInput()+['id'=>0,'title'=>'First module','status'=>'published']);
$second = $service->saveModule($actor, scopeInput()+['id'=>0,'title'=>'Second module']);
curriculumDenied(fn () => $service->saveModule($actor,$stale+['id'=>$module,'revision'=>1,'title'=>'Stale overwrite']),'stale aggregate revision rejected');
$anonymous = new \Joomla\CMS\User\User();
curriculumDenied(fn () => $service->outline($anonymous,$course),'anonymous cannot read draft');
curriculumDenied(fn () => $service->saveModule($anonymous,scopeInput()+['id'=>0,'title'=>'No access']),'anonymous cannot mutate draft');
$firstLesson = $service->saveLesson($actor, scopeInput()+['id'=>0,'module_id'=>$module,'title'=>'Text introduction',
    'kind'=>'text','required'=>'1','body'=>'<p>First lesson</p>','preview'=>1,'completion_rule'=>'forged']);
$row = $db->setQuery('SELECT * FROM #__jlms_lessons WHERE id=' . $firstLesson)->loadObject();
curriculumExpect($row->completion_rule==='manual' && (int)$row->preview===0,'server derives rules and ignores preview mass assignment');
curriculumExpect($row->body===\Joomla\CMS\Component\ComponentHelper::filterText('<p>First lesson</p>'),'body uses the native Joomla content filter');
$video = $service->saveLesson($actor,scopeInput()+['id'=>0,'module_id'=>$module,'title'=>'Video draft','kind'=>'video',
    'required'=>'0','body'=>'','provider_key'=>'youtube','source_reference'=>'https://youtu.be/dQw4w9WgXcQ']);
$row = $db->setQuery('SELECT * FROM #__jlms_lessons WHERE id=' . $video)->loadObject();
$source = json_decode($row->source_json,true,32,JSON_THROW_ON_ERROR);
curriculumExpect($source['reference']==='dQw4w9WgXcQ' && $row->completion_rule==='video_coverage' && (int)$row->required===0,'video normalised and optional flag retained');
$before = versionRevision();
curriculumDenied(fn () => $service->saveLesson($actor,scopeInput()+['id'=>0,'module_id'=>$module,'title'=>'Invalid video','kind'=>'video',
    'required'=>'1','body'=>'','provider_key'=>'youtube','source_reference'=>'https://127.0.0.1/private']),'unsafe source rejected');
curriculumExpect(versionRevision()===$before,'failed validation does not mutate aggregate');
$service->reorder($actor,'module',scopeInput()+['id'=>$second,'direction'=>'up']);
$outline = $service->outline($actor,$course,$version);
curriculumExpect((int)$outline['modules'][0]->id===$second,'module ordering persisted');
$service->reorder($actor,'lesson',scopeInput()+['id'=>$video,'direction'=>'up']);
$outline = $service->outline($actor,$course,$version);
curriculumExpect((int)$outline['lessons'][0]->id===$video,'lesson ordering persisted');
$document = $service->saveLesson($actor,scopeInput()+['id'=>0,'module_id'=>$module,'title'=>'Document lesson','kind'=>'document','required'=>'1','body'=>'<p>Read the attached document</p>']);
$documentRow = $db->setQuery('SELECT * FROM #__jlms_lessons WHERE id=' . $document)->loadObject();
curriculumExpect($documentRow->kind==='document' && $documentRow->completion_rule==='manual' && $documentRow->provider_key===null,'document lessons use manual completion and no video source');
curriculumDenied(fn () => $service->deleteItem($actor,'module',scopeInput()+['id'=>$module,'revision'=>recordRevision('modules',$module),'confirmation'=>'1']),'non-empty module cannot be removed');
$service->saveModule($actor,scopeInput()+['id'=>$second,'revision'=>recordRevision('modules',$second),'title'=>'Updated module']);
$secondRevision=recordRevision('modules',$second);
curriculumDenied(fn () => $service->saveModule($actor,scopeInput()+['id'=>$second,'revision'=>$secondRevision-1,'title'=>'Old module revision']),'stale item revision rejected');
// Cross-parent IDs must fail even for an actor who can manage both courses.
$data['alias']='curriculum-foreign';$data['title']='Foreign course';
if (!$model->save($data)) { throw new \RuntimeException('Foreign fixture failed'); }
$foreignCourse = (int)$model->getState('course.id');
$foreignVersion = $service->createDraft($actor,$foreignCourse,1);
$foreignModule = $service->saveModule($actor,['course_id'=>$foreignCourse,'version_id'=>$foreignVersion,
    'expected_version_revision'=>1,'id'=>0,'title'=>'Foreign module']);
curriculumDenied(fn () => $service->saveLesson($actor,scopeInput()+['id'=>0,'module_id'=>$foreignModule,
    'title'=>'Wrong parent','kind'=>'text','required'=>'1','body'=>'not allowed']),'foreign module rejected');
curriculumDenied(fn () => $service->outline($actor,$course,$foreignVersion),'foreign teaching version is not disclosed through another course');
$db->setQuery("UPDATE #__jlms_course_versions SET status='published' WHERE id=".$version)->execute();
curriculumDenied(fn () => $service->saveModule($actor,scopeInput()+['id'=>$module,'revision'=>recordRevision('modules',$module),'title'=>'Change published history']),'published teaching history immutable');
$db->setQuery("UPDATE #__jlms_course_versions SET status='draft' WHERE id=".$version)->execute();
$service->deleteItem($actor,'lesson',scopeInput()+['id'=>$firstLesson,'revision'=>recordRevision('lessons',$firstLesson),'confirmation'=>'1']);
curriculumExpect(!(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_lessons WHERE id='.$firstLesson)->loadResult(),'draft lesson deleted');
$service->deleteItem($actor,'module',scopeInput()+['id'=>$second,'revision'=>recordRevision('modules',$second),'confirmation'=>'1']);
curriculumExpect(!(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_modules WHERE id='.$second)->loadResult(),'empty draft module deleted');
$app->getInput()->set('id',$course);
$app->getInput()->set('version_id',null);
$curriculumModel=$factory->createModel('Curriculum','Administrator',['ignore_request'=>true]);
curriculumExpect((int)$curriculumModel->getOutline()['version']->id===$version,'latest draft resolves when version_id is omitted');
curriculumExpect((bool)$curriculumModel->getForm([],false) && (bool)$curriculumModel->getLessonForm(),'native curriculum forms load');
$outline=$service->outline($actor,$course,$version);
curriculumExpect(count($outline['modules'])===1 && count($outline['lessons'])===1,'final outline remains coherent');
// Rendering is tested over HTTP, where Joomla initialises its document and toolbar.
echo json_encode(['curriculum_checks'=>$checks,'status'=>'passed','course_id'=>$course,'version_id'=>$version],JSON_THROW_ON_ERROR).PHP_EOL;
