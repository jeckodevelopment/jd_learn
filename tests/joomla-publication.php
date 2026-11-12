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
$app->getSession()->set('user', $app->getIdentity());
$component = $app->bootComponent('com_jlms');
$factory = $component->getMVCFactory();


$authoring = $component->getCurriculumService();
$publication = $component->getPublicationService();
$catalog = $component->getCatalogService();
$actor = $app->getIdentity();
$catid = (int) $db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
$model = $factory->createModel('Course', 'Administrator', ['ignore_request' => true]);
$data = ['id'=>0,'title'=>'Publication fixture','alias'=>'publication-test','catid'=>$catid,'language'=>'*',
    'catalog_access'=>1,'content_access'=>2,'enrollment_mode'=>'auto','presentation_description'=>'<p>Course presentation for the publication fixture.</p>',
    'presentation_difficulty'=>'beginner','presentation_estimated_minutes'=>'45','summary'=>'Public summary','revision'=>1];
if (!$model->save($data)) { throw new \RuntimeException('Publication fixture failed: ' . $model->getError()); }
$course = (int) $model->getState('course.id');
$version = $authoring->createDraft($actor, $course, 1);
$checks = 0;
$writeFixture = static function (string $table, object $record, string $key = 'id', bool $nulls = false) use ($db): void {
    $db->updateObject($table, $record, $key, $nulls);
};
function pubExpect(bool $ok, string $message): void {
    global $checks;
    if (!$ok) { throw new \RuntimeException($message); }
    $checks++;
}
function pubDenied(callable $call, string $message): void {
    try { $call(); } catch (\DomainException | \InvalidArgumentException $expected) { pubExpect(true,$message); return; }
    throw new \RuntimeException($message);
}
function pubRevision(string $table, int $id): int {
    global $db;
    return (int) $db->setQuery('SELECT revision FROM #__jlms_' . $table . ' WHERE id=' . $id)->loadResult();
}
function pubScope(): array {
    global $course,$version;
    return ['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>pubRevision('course_versions',$version)];
}
function pubTransition(?int $expectedCourse = null): void {
    global $publication,$actor,$course,$version;
    $choices=$publication->transitions($actor,$course);
    if (!$choices) { throw new \RuntimeException('Native workflow transition missing'); }
    $publication->transition($actor,$course,$version,$expectedCourse ?? pubRevision('courses',$course),pubRevision('course_versions',$version),(int)$choices[0]->id);
}
$guest = new \Joomla\CMS\User\User();
pubExpect($catalog->publicTags($guest, 'en-GB', $catid) === [], 'public tag choices exclude courses that are still drafts');
$hidden = static function () use ($catalog,$guest,$course): bool {
    try { $catalog->course($guest,'en-GB',$course); } catch (\RuntimeException $error) { return $error->getCode()===404; }
    return false;
};
pubExpect($hidden(),'draft absent from public direct lookup');
pubDenied(fn()=>pubTransition(),'empty structure rejected');
pubExpect(!(int)$db->setQuery('SELECT COUNT(*) FROM #__workflow_associations WHERE extension=\'com_jlms.course\' AND item_id='.$course)->loadResult(),'failed publish has no workflow side effects');
$module=$authoring->saveModule($actor,pubScope()+['id'=>0,'title'=>'Published chapter']);
\Joomla\CMS\Plugin\PluginHelper::importPlugin('system','fields');
$fieldModel=$app->bootComponent('com_fields')->getMVCFactory()->createModel('Field','Administrator',['ignore_request'=>true]);
if (!$fieldModel->save(['id'=>0,'context'=>'com_jlms.lesson','title'=>'Lesson reference','label'=>'Lesson reference','name'=>'lesson-reference','type'=>'text',
    'state'=>1,'access'=>2,'language'=>'*','group_id'=>0,'default_value'=>'','description'=>'','params'=>['showlabel'=>1],'fieldparams'=>['filter'=>'string']])) {
    throw new \RuntimeException('Native lesson field fixture failed: ' . $fieldModel->getError());
}
$lessonField=(int)$fieldModel->getState('field.id');
$lesson=$authoring->saveLesson($actor,pubScope()+['id'=>0,'module_id'=>$module,'title'=>'Published lesson','kind'=>'text','required'=>'1',
    'body'=>'<p>SECRET_LESSON_BODY</p>','com_fields'=>['lesson-reference'=>'LESSON_FIELD_SNAPSHOT']]);
pubExpect((string)$db->setQuery('SELECT value FROM #__fields_values WHERE field_id='.$lessonField.' AND item_id='.$db->quote((string)$lesson))->loadResult()==='LESSON_FIELD_SNAPSHOT',
    'native custom field value saved with draft lesson');
$courseFieldModel=$app->bootComponent('com_fields')->getMVCFactory()->createModel('Field','Administrator',['ignore_request'=>true]);
if (!$courseFieldModel->save(['id'=>0,'context'=>'com_jlms.course','title'=>'Course reference','label'=>'Course reference','name'=>'course-reference','type'=>'text',
    'state'=>1,'access'=>2,'language'=>'*','group_id'=>0,'default_value'=>'','description'=>'','params'=>['showlabel'=>1],'fieldparams'=>['filter'=>'string']])) {
    throw new \RuntimeException('Native course field fixture failed: ' . $courseFieldModel->getError());
}
$courseField=(int)$courseFieldModel->getState('field.id');
if (!$courseFieldModel->setFieldValue($courseField,$course,'COURSE_FIELD_SNAPSHOT')) {
    throw new \RuntimeException('Native course field value fixture failed.');
}
pubExpect((string)$db->setQuery('SELECT value FROM #__fields_values WHERE field_id='.$courseField.' AND item_id='.$db->quote((string)$course))->loadResult()==='COURSE_FIELD_SNAPSHOT',
    'native custom field value saved for the course');
pubDenied(fn()=>pubTransition(1),'stale publication revision rejected');
$choices=$publication->transitions($actor,$course);
pubDenied(fn()=>$publication->transition($guest,$course,$version,pubRevision('courses',$course),pubRevision('course_versions',$version),(int)$choices[0]->id),'guest publication denied');
// A draft video is valid authoring input but not a publishable lesson yet.
$video=$authoring->saveLesson($actor,pubScope()+['id'=>0,'module_id'=>$module,'title'=>'Unsupported video','kind'=>'video','required'=>'0','body'=>'','provider_key'=>'youtube','source_reference'=>'dQw4w9WgXcQ']);
pubDenied(fn()=>pubTransition(),'unsupported video blocks entire publication');
$authoring->deleteItem($actor,'lesson',pubScope()+['id'=>$video,'revision'=>1,'confirmation'=>'1']);
// A required lesson must have a non-empty overlap with the published course window.
$lessonOpen=gmdate('Y-m-d H:i:s',time()+7200);$courseClose=gmdate('Y-m-d H:i:s',time()+3600);
$authoring->saveLesson($actor,pubScope()+['id'=>$lesson,'revision'=>1,'module_id'=>$module,'title'=>'Published lesson','kind'=>'text',
    'required'=>'1','body'=>'<p>SECRET_LESSON_BODY</p>','publish_up'=>$lessonOpen]);
$writeFixture('#__jlms_courses',(object)['id'=>$course,'publish_down'=>$courseClose],'id');
$unreachableRejected=false;
try { $publication->transition($actor,$course,$version,pubRevision('courses',$course),pubRevision('course_versions',$version),(int)$publication->transitions($actor,$course)[0]->id); }
catch (\DomainException $error) { $unreachableRejected=$error->getMessage()==='COM_JLMS_LESSON_UNREACHABLE'; }
pubExpect($unreachableRejected,'publication rejects a required lesson outside the course window');
$writeFixture('#__jlms_courses',(object)['id'=>$course,'publish_down'=>null],'id',true);
$authoring->saveLesson($actor,pubScope()+['id'=>$lesson,'revision'=>2,'module_id'=>$module,'title'=>'Published lesson','kind'=>'text',
    'required'=>'1','body'=>'<p>SECRET_LESSON_BODY</p>','publish_up'=>'','publish_down'=>'']);
$writeFixture('#__jlms_lessons',(object)['id'=>$lesson,'release_after_days'=>5],'id');
$dripWithoutEntryRejected=false;
try { $publication->transition($actor,$course,$version,pubRevision('courses',$course),pubRevision('course_versions',$version),(int)$publication->transitions($actor,$course)[0]->id); }
catch (\DomainException $error) { $dripWithoutEntryRejected=$error->getMessage()==='COM_JLMS_REQUIRED_INITIAL_LESSON_REQUIRED'; }
pubExpect($dripWithoutEntryRejected,'publication rejects a programme with no required day-zero entry lesson');
$writeFixture('#__jlms_lessons',(object)['id'=>$lesson,'release_after_days'=>null],'id',true);
// Native plugin veto must roll back frozen version, pointer and edition as one unit.
$veto = static function ($event): void { if ($event->getArgument('extension')==='com_jlms.course') { $event->setStopTransition(); } };
$app->getDispatcher()->addListener('onWorkflowBeforeTransition',$veto);
pubDenied(fn()=>pubTransition(),'workflow veto aborts publication');
$app->getDispatcher()->removeListener('onWorkflowBeforeTransition',$veto);
pubExpect($db->setQuery('SELECT status FROM #__jlms_course_versions WHERE id='.$version)->loadResult()==='draft'
    && !(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_editions WHERE course_id='.$course)->loadResult(),'veto rolls back teaching snapshot and default edition');
// The core Workflow helper does not propagate a false model result; the service must.
$afterVeto = static function ($event): void { if ($event->getContext()==='com_jlms.course') { $event->addResult(false); } };
$app->getDispatcher()->addListener('onContentChangeState',$afterVeto);
pubDenied(fn()=>pubTransition(),'failed native state callback rolls back publication');
$app->getDispatcher()->removeListener('onContentChangeState',$afterVeto);
pubExpect($hidden(),'failed state callback leaves public course hidden');
pubTransition();
$frozen=$db->setQuery('SELECT * FROM #__jlms_course_versions WHERE id='.$version)->loadObject();
pubExpect($frozen->status==='published' && strlen($frozen->structure_hash)===64 && $frozen->published_at!==null,'publication freezes hash and timestamp');
$courseSnapshot=json_decode((string)$frozen->fields_snapshot_json,true,32,JSON_THROW_ON_ERROR);
pubExpect(($courseSnapshot['fields']['course-reference']['values'][0] ?? '')==='COURSE_FIELD_SNAPSHOT',
    'publication freezes native course custom fields into the assigned teaching version');
$lessonSnapshot=json_decode((string)$db->setQuery('SELECT fields_snapshot_json FROM #__jlms_lessons WHERE id='.$lesson)->loadResult(),true,32,JSON_THROW_ON_ERROR);
pubExpect(($lessonSnapshot['fields']['lesson-reference']['values'][0] ?? '')==='LESSON_FIELD_SNAPSHOT','publication freezes lesson custom field value');
pubExpect((bool)$courseFieldModel->setFieldValue($courseField,$course,'COURSE_FIELD_CURRENT_VALUE'),
    'course editorial field can change after publishing');
$unchangedCourseSnapshot=json_decode((string)$db->setQuery('SELECT fields_snapshot_json FROM #__jlms_course_versions WHERE id='.$version)->loadResult(),true,32,JSON_THROW_ON_ERROR);
pubExpect(($unchangedCourseSnapshot['fields']['course-reference']['values'][0] ?? '')==='COURSE_FIELD_SNAPSHOT',
    'later editorial field changes do not rewrite the frozen course snapshot');
pubExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_editions WHERE course_id='.$course)->loadResult()===1,'one default edition created');
$assoc=(new \Joomla\CMS\Workflow\Workflow('com_jlms.course',$app,$db))->getAssociation($course);
pubExpect((bool)$assoc,'native workflow association exists');
pubExpect((int)$catalog->course($guest,'en-GB',$course)->id===$course,'published promotional course available without content level');
$learnerEnrollment=$component->getLearningService()->start($actor,'en-GB',$course,str_repeat('r',40));
$classroom=$component->getLearningService()->classroom($actor,'en-GB',$learnerEnrollment,$lesson);
pubExpect(($classroom['lesson']->custom_fields[0]['values'][0] ?? '')==='LESSON_FIELD_SNAPSHOT',
    'assigned classroom renders frozen lesson custom field value');
pubExpect(!str_contains(json_encode($catalog->outline($guest,'en-GB',$course)), 'SECRET_LESSON_BODY'),'public outline contains no protected body');
pubDenied(fn()=>$authoring->saveModule($actor,pubScope()+['id'=>0,'title'=>'Change history']),'published authoring is immutable');
$ids=[$course];pubExpect(!$model->publish($ids,0),'direct state change bypass rejected');
// Current category/access/language/date must be rechecked without a stale shared cache.
foreach ([['catalog_access',2],['language','it-IT'],['publish_up','2099-01-01 00:00:00'],['publish_down','2000-01-01 00:00:00']] as [$field,$value]) {
    $writeFixture('#__jlms_courses',(object)['id'=>$course,$field=>$value],'id',true);
    pubExpect($hidden(),'current '.$field.' hides direct lookup');
    $writeFixture('#__jlms_courses',(object)['id'=>$course,$field=>match($field){'catalog_access'=>1,'language'=>'*',default=>null}],'id',true);
}
$writeFixture('#__categories',(object)['id'=>$catid,'published'=>0],'id');
pubExpect($hidden(),'unpublished category hides course');
$writeFixture('#__categories',(object)['id'=>$catid,'published'=>1],'id');
// Test a restricted ancestor through a real nested category.
$child=new \Joomla\CMS\Table\Category($db);
$child->setLocation($catid,'last-child');
$child->bind(['title'=>'Child category','description'=>'','alias'=>'publication-child','extension'=>'com_jlms.course','published'=>1,'access'=>1,'language'=>'*','params'=>'{}','metadata'=>'{}']);
if (!$child->check() || !$child->store()) { throw new \RuntimeException('Child category fixture failed'); }
$writeFixture('#__jlms_courses',(object)['id'=>$course,'catid'=>(int)$child->id],'id');
$writeFixture('#__categories',(object)['id'=>$catid,'access'=>2],'id');
pubExpect($hidden(),'restricted ancestor hides direct course URL');
$writeFixture('#__categories',(object)['id'=>$catid,'access'=>1],'id');
pubExpect((bool)$catalog->course($guest,'en-GB',$course),'restored ancestor visibility is current');
$filtered=$db->setQuery($catalog->query($guest,'en-GB',$catid))->loadObjectList();
pubExpect(in_array($course,array_map(static fn($c)=>(int)$c->id,$filtered),true),'category filter includes descendants');
pubTransition();
pubExpect($hidden(),'native unpublish hides course');
pubExpect($db->setQuery('SELECT structure_hash FROM #__jlms_course_versions WHERE id='.$version)->loadResult()===$frozen->structure_hash,'unpublish preserves frozen teaching history');
pubTransition();
pubExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_editions WHERE course_id='.$course)->loadResult()===1,'republish creates no duplicate edition');
$nextVersion=$authoring->createDraft($actor,$course,pubRevision('courses',$course));
$publishChoice=null;
foreach ($publication->transitions($actor,$course,$nextVersion) as $candidate) {
    $options=new \Joomla\Registry\Registry($candidate->options);
    $action=(string)$options->get('jlms_action',(string)$options->get('publishing')==='1'?'publish':'unpublish');
    if ($action==='publish') { $publishChoice=$candidate; break; }
}
pubExpect((bool)$publishChoice,'new teaching version has a publish transition');
$nextImpact=$publication->updateImpact($actor,$course,$nextVersion,(int)$publishChoice->id);
pubExpect(is_array($nextImpact) && isset($nextImpact['fingerprint']),'new version publication impact can be reviewed');
$publication->transition($actor,$course,$nextVersion,pubRevision('courses',$course),pubRevision('course_versions',$nextVersion),
    (int)$publishChoice->id,(string)$nextImpact['fingerprint']);
$nextSnapshot=json_decode((string)$db->setQuery('SELECT fields_snapshot_json FROM #__jlms_course_versions WHERE id='.$nextVersion)->loadResult(),true,32,JSON_THROW_ON_ERROR);
$oldSnapshot=json_decode((string)$db->setQuery('SELECT fields_snapshot_json FROM #__jlms_course_versions WHERE id='.$version)->loadResult(),true,32,JSON_THROW_ON_ERROR);
pubExpect(($nextSnapshot['fields']['course-reference']['values'][0] ?? '')==='COURSE_FIELD_CURRENT_VALUE'
    && ($oldSnapshot['fields']['course-reference']['values'][0] ?? '')==='COURSE_FIELD_SNAPSHOT',
    'new teaching version captures current course fields while preserving the prior snapshot');
pubExpect((int)$db->setQuery('SELECT ed.course_version_id FROM #__jlms_enrollments AS e INNER JOIN #__jlms_editions AS ed ON ed.id=e.edition_id WHERE e.id='.(int)$learnerEnrollment)->loadResult()===$version,
    'existing learner remains assigned to the original teaching version after update publication');
echo json_encode(['publication_checks'=>$checks,'status'=>'passed','course_id'=>$course],JSON_THROW_ON_ERROR).PHP_EOL;
