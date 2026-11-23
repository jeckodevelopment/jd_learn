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


$authoring = $component->getCurriculumService();
$publication = $component->getPublicationService();
$catalog = $component->getCatalogService();
$actor = $app->getIdentity();
$catid = (int) $db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
use Jlms\Component\Jlms\Administrator\Application\CoursePresentation;



$checks = 0;
function listExpect(bool $ok,string $message):void { global $checks;
    if (!$ok)throw new RuntimeException($message);
    $checks++;
}
$fixturePath = dirname($root) . '/courses-ui-fixture.json';
if (($argv[2] ?? '') === 'retained') {
    $f = json_decode(file_get_contents($fixturePath), true, 32, JSON_THROW_ON_ERROR);
    $rows = $db->setQuery('SELECT id,state,current_version_id,revision FROM #__jlms_courses WHERE id IN (' . implode(',', array_map('intval', $f['courses'])) . ') ORDER BY id')->loadAssocList();
    listExpect($rows === $f['rows'], 'List and editor navigation preserve course state, version and revision after upgrade');
    echo json_encode(['courses_ui_retention_checks' => $checks,'status' => 'passed']) . PHP_EOL;
    exit;
}
$categoryModel = $app->bootComponent('com_categories')->getMVCFactory()->createModel('Category', 'Administrator', ['ignore_request' => true]);
$parent = 1;
$categories = [];
foreach (['parent','child'] as $name) {
    listExpect($categoryModel->save(['id' => 0,'parent_id' => $parent,'extension' => 'com_jlms.course','title' => 'List UI ' . $name,'alias' => 'list-ui-' . $name,'published' => 1,'language' => '*','access' => 1]), 'Native list category fixture');
    $categories[$name] = (int)$categoryModel->getState('category.id');
    $parent = $categories[$name];
}
$teacherCategoryModel = $app->bootComponent('com_categories')->getMVCFactory()->createModel('Category', 'Administrator', ['ignore_request' => true]);
listExpect($teacherCategoryModel->save(['id' => 0,'parent_id' => 1,'extension' => 'com_jlms.teacher','title' => 'List UI teachers','alias' => 'list-ui-teachers','published' => 1,'language' => '*','access' => 1]), 'Native teacher category fixture');
$teacherCategory = (int)$teacherCategoryModel->getState('category.id');
$teacherModel = $factory->createModel('Teacher', 'Administrator', ['ignore_request' => true]);
listExpect($teacherModel->save(['id' => 0,'revision' => 1,'title' => 'List UI assigned teacher','alias' => 'list-ui-assigned-teacher','catid' => $teacherCategory,'bio' => '','image' => '','image_alt' => '','image_decorative' => '0','website' => '','language' => '*','access' => 1,'state' => 1,'user_id' => '']), 'Native assigned teacher fixture');
$teacherId = (int)$teacherModel->getState('teacher.id');
$editor = (int)$db->setQuery("SELECT id FROM #__users WHERE username='cieditor'")->loadResult();
$ids = [];
$versions = [];
foreach (['Zebra','Alpha','Bravo'] as $name) {
    $model = $factory->createModel('Course', 'Administrator', ['ignore_request' => true]);
    listExpect($model->save(['id' => 0,'revision' => 1,'title' => 'List UI ' . $name,'alias' => 'list-ui-' . strtolower($name),'catid' => $categories[$name === 'Alpha' ? 'child' : 'parent'],'language' => $name === 'Alpha' ? 'en-GB' : '*','catalog_access' => 1,'content_access' => $name === 'Bravo' ? 1 : 2,'enrollment_mode' => 'auto','summary' => 'List fixture','presentation_description' => '<p>List UI public presentation</p>']), 'List course fixture');
    $id = (int)$model->getState('course.id');
    $ids[$name] = $id;
    if (in_array($name, ['Alpha','Bravo'], true))listExpect($model->save(['id' => $id,'revision' => 1,'teacher_ids' => [$teacherId]]), 'Teacher assigned to selected list fixture course');
    $draft = $authoring->createDraft($actor, $id, (int)$model->getItem($id)->revision);
    $versions[$name] = $draft;
    $module = $authoring->saveModule($actor, ['course_id' => $id,'version_id' => $draft,'expected_version_revision' => 1,'title' => 'List UI module']);
    $revision = (int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id=' . $draft)->loadResult();
    $authoring->saveLesson($actor, ['course_id' => $id,'version_id' => $draft,'expected_version_revision' => $revision,'module_id' => $module,'title' => 'List UI lesson','kind' => 'text','body' => '<p>LIST_UI_PRIVATE_LESSON_BODY</p>','required' => '1']);
    if ($name === 'Alpha') {
        $choices = $publication->transitions($actor, $id, $draft);
        $publication->transition($actor, $id, $draft, (int)$model->getItem($id)->revision, (int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id=' . $draft)->loadResult(), (int)$choices[0]->id);
    }
}
// Finder tests use an explicitly public, published *disposable* fixture. The
// ordinary course-editor fixture is not guaranteed to have reached publication.
$finderCourseOriginal = $db->setQuery('SELECT id, state, current_version_id, publish_up, publish_down, content_access FROM #__jlms_courses WHERE id=' . (int) $ids['Alpha'])->loadObject();
$finderVersionOriginal = $db->setQuery('SELECT id, status FROM #__jlms_course_versions WHERE id=' . (int) $versions['Alpha'])->loadObject();
$finderPublished = (object) [
    'id' => $ids['Alpha'], 'state' => 1,
    'current_version_id' => $versions['Alpha'],
    'publish_up' => null, 'publish_down' => null, 'content_access' => 2,
];
$db->updateObject('#__jlms_courses', $finderPublished, 'id', true);
$finderPublishedVersion = (object) ['id' => $versions['Alpha'], 'status' => 'published'];
$db->updateObject('#__jlms_course_versions', $finderPublishedVersion, 'id');
// CAT-04: query the native Finder adapter against the installed Joomla DB.
$finderPath = JPATH_PLUGINS . '/finder/jlms/src/Extension/Jlms.php';
require_once $finderPath;
$finderClass = new \ReflectionClass(\Jlms\Plugin\Finder\Jlms\Extension\Jlms::class);
$finder = $finderClass->newInstanceWithoutConstructor();
$finder->setDatabase($db);
$finderQueryMethod = $finderClass->getMethod('getListQuery');
$finderIds = static function () use ($finder, $finderQueryMethod, $db): array {
    $query = $finderQueryMethod->invoke($finder);
    return array_map('intval', $db->setQuery($query)->loadColumn());
};
listExpect(!in_array($ids['Alpha'], $finderIds(), true),
    'Finder must not index public-catalog courses with protected lesson access');
$accessChange = (object)['id' => $ids['Alpha'], 'content_access' => 1];
$db->updateObject('#__jlms_courses', $accessChange, 'id');
listExpect(in_array($ids['Alpha'], $finderIds(), true),
    'Finder must index the same course when both catalog and content access are public');

 // LCM-05: the installed Finder adapter must apply publication and SEO
 // eligibility on persisted Joomla rows, not just consume a dispatched event.
$finderSubject = (int)$ids['Alpha'];
$finderVisible = static fn():bool => in_array($finderSubject, $finderIds(), true);
$finderState = (object)['id' => $finderSubject,'state' => 2];
$db->updateObject('#__jlms_courses', $finderState, 'id');
listExpect(!$finderVisible(), 'Finder index query excludes an archived public course');
$finderState->state = -2;
$db->updateObject('#__jlms_courses', $finderState, 'id');
listExpect(!$finderVisible(), 'Finder index query excludes a trashed public course');
$finderState->state = 0;
$db->updateObject('#__jlms_courses', $finderState, 'id');
listExpect(!$finderVisible(), 'Finder index query excludes a restored but unpublished course');
$finderState->state = 1;
$db->updateObject('#__jlms_courses', $finderState, 'id');
listExpect($finderVisible(), 'Finder index query restores eligibility only after explicit publication');
$finderWindow = (object)['id' => $finderSubject,'publish_up' => '2099-05-01 00:00:00'];
$db->updateObject('#__jlms_courses', $finderWindow, 'id');
listExpect(!$finderVisible(), 'Finder excludes timed publication that cannot be held in a static index');
$finderWindow->publish_up = null;
$db->updateObject('#__jlms_courses', $finderWindow, 'id', true);
listExpect($finderVisible(), 'Removing the scheduled publication restores Finder eligibility');
$finderCategory = (object)['id' => (int)$categories['child'],'language' => 'en-GB'];
$db->updateObject('#__categories', $finderCategory, 'id');
$finderLanguage = (object)['id' => $finderSubject,'language' => 'it-IT'];
$db->updateObject('#__jlms_courses', $finderLanguage, 'id');
listExpect(!$finderVisible(), 'Finder enforces category/course language consistency');
$finderLanguage->language = 'en-GB';
$db->updateObject('#__jlms_courses', $finderLanguage, 'id');
listExpect($finderVisible(), 'Matching category/course language restores Finder eligibility');
$finderCategory->language = '*';
$db->updateObject('#__categories', $finderCategory, 'id');

$accessChange = (object)['id' => $ids['Alpha'], 'content_access' => 2];
$db->updateObject('#__jlms_courses', $accessChange, 'id');
listExpect(!in_array($ids['Alpha'], $finderIds(), true),
    'Finder must exclude a previously public course as soon as content access is restricted');
$db->updateObject('#__jlms_courses', $finderCourseOriginal, 'id', true);
$db->updateObject('#__jlms_course_versions', $finderVersionOriginal, 'id');


$change = (object)['id' => $ids['Bravo'],'created_by' => $editor];
$db->updateObject('#__jlms_courses', $change, 'id');
$change = (object)['id' => $ids['Zebra'],'checked_out' => $editor,'checked_out_time' => gmdate('Y-m-d H:i:s')];
$db->updateObject('#__jlms_courses', $change, 'id');
$list = $factory->createModel('Courses', 'Administrator', ['ignore_request' => true]);
$list->setState('list.limit', 100);
$list->setState('list.start', 0);
$list->setState('filter.search', 'List UI');
$list->setState('list.ordering', 'a.title');
$list->setState('list.direction', 'ASC');
$get = static fn():array=>array_map(static fn($row):int=>(int)$row->id, $list->getItems());
listExpect($get() === [$ids['Alpha'],$ids['Bravo'],$ids['Zebra']] && (int)$list->getTotal() === 3, 'Native list sorting, search and total agree');
$list->setState('filter.published', '1');
listExpect($get() === [$ids['Alpha']] && (int)$list->getTotal() === 1, 'Published filter shares the total query');
$list->setState('filter.published', '0');
listExpect($get() === [$ids['Bravo'],$ids['Zebra']] && (int)$list->getTotal() === 2, 'Filter changes invalidate cached items and totals');
$list->setState('filter.published', '2');
listExpect($get() === [] && (int)$list->getTotal() === 0, 'Archived filter accepts state 2 and excludes active/draft fixtures');
$list->setState('filter.published', '-2');
listExpect($get() === [], 'Trashed filter excludes ordinary active and draft fixtures');
$list->setState('filter.published', '');
$list->setState('filter.category_id', (string)$categories['parent']);
listExpect(count($get()) === 3, 'Category filter includes native descendants');
$list->setState('filter.category_id', (string)$categories['child']);
listExpect($get() === [$ids['Alpha']], 'Child category filter is precise');
$list->setState('filter.category_id', '');
$list->setState('filter.author_id', (string)$editor);
listExpect($get() === [$ids['Bravo']], 'Native author filter');
$list->setState('filter.author_id', '');
$list->setState('filter.teacher_id', (string)$teacherId);
listExpect($get() === [$ids['Alpha'],$ids['Bravo']], 'Native assigned-teacher filter');
$list->setState('filter.teacher_id', 'malformed');
listExpect($get() === [], 'Malformed teacher filter cannot broaden the list');
$list->setState('filter.teacher_id', '');
$list->setState('filter.author_id', '');
$list->setState('filter.access', '1');
listExpect($get() === [$ids['Bravo']], 'Content access level filter');
$list->setState('filter.access', '');
$list->setState('filter.language', 'en-GB');
listExpect($get() === [$ids['Alpha']], 'Language filter');
$list->setState('filter.language', '');
$list->setState('filter.checked_out', '1');
listExpect($get() === [$ids['Zebra']], 'Checkout filter');
$list->setState('filter.checked_out', '0');
listExpect($get() === [$ids['Alpha'],$ids['Bravo']], 'Unlocked filter');
$list->setState('filter.checked_out', '');
$list->setState('filter.search', 'id:' . $ids['Bravo']);
listExpect($get() === [$ids['Bravo']], 'Native id search');
$list->setState('filter.search', "' OR 1=1 --");
listExpect($get() === [], 'Search is bound SQL input');
$list->setState('filter.search', 'List UI');
$list->setState('filter.published', ['1']);
listExpect($get() === [], 'Malformed filters fail closed');
$list->setState('filter.published', '');
$list->setState('list.ordering', 'a.title; DROP TABLE #__jlms_courses');
$list->setState('list.direction', 'DESC; DELETE');
listExpect(count($get()) === 3, 'SQL ordering is allowlisted');
// Verify the archived state against an actual record without changing retained fixtures.
$db->setQuery('UPDATE #__jlms_courses SET state=2 WHERE id=' . (int)$ids['Bravo'])->execute();
try {
    $archivedList = $factory->createModel('Courses', 'Administrator', ['ignore_request' => true]);
    $archivedList->setState('list.limit', 100);
    $archivedList->setState('list.start', 0);
    $archivedList->setState('filter.search', 'List UI');
    $archivedList->setState('filter.published', '2');
    $archiveIds = array_map(static fn($row):int=>(int)$row->id, $archivedList->getItems());
    listExpect($archiveIds === [$ids['Bravo']] && (int)$archivedList->getTotal() === 1, 'Archived state filter returns exactly archived records');
} finally {
    $db->setQuery('UPDATE #__jlms_courses SET state=0 WHERE id=' . (int)$ids['Bravo'])->execute();
}
// LCM-02: native reversible course trash/restore with a preserved programme.
$courseModel = $factory->createModel('Course', 'Administrator', ['ignore_request' => true]);
$originalVersion = $db->setQuery('SELECT current_version_id FROM #__jlms_courses WHERE id=' . (int)$ids['Alpha'])->loadResult();
$originalRevision = (int)$db->setQuery('SELECT revision FROM #__jlms_courses WHERE id=' . (int)$ids['Alpha'])->loadResult();
$originalGates = $db->setQuery('SELECT admissions_open, delivery_enabled FROM #__jlms_courses WHERE id=' . (int)$ids['Alpha'])->loadAssoc();
// Deliberately pre-arm the course to ensure trash closes both gates atomically.
$db->setQuery('UPDATE #__jlms_courses SET admissions_open=1, delivery_enabled=1 WHERE id=' . (int)$ids['Alpha'])->execute();
$learningBefore = [
 $db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . (int)$ids['Alpha'])->loadResult(),
 $db->setQuery('SELECT COUNT(*) FROM #__jlms_course_versions WHERE course_id=' . (int)$ids['Alpha'])->loadResult(),
];
$finderStates = [];
$finderWitness = static function ($event) use (&$finderStates, $db, $ids): void {
    if ($event->getContext() !== 'com_jlms.course' || (int)$event->getItem()->id !== (int)$ids['Alpha']) {
        return;
    }
    $finderStates[] = $db->setQuery('SELECT state,admissions_open,delivery_enabled FROM #__jlms_courses WHERE id=' . (int)$ids['Alpha'])->loadAssoc();
};
$app->getDispatcher()->addListener('onFinderAfterSave', $finderWitness);
listExpect($courseModel->changeStates([$ids['Alpha']], -2), 'Published course can be moved to trash');
listExpect((int)$db->setQuery('SELECT state FROM #__jlms_courses WHERE id=' . (int)$ids['Alpha'])->loadResult() === -2, 'Course is trashed without deleting the record');
listExpect($db->setQuery('SELECT admissions_open, delivery_enabled FROM #__jlms_courses WHERE id=' . (int)$ids['Alpha'])->loadAssoc() == ['admissions_open' => 0,'delivery_enabled' => 0], 'Trash closes both gates for a previously open course');
listExpect(count($finderStates) === 1 && (int)$finderStates[0]['state'] === -2 && (int)$finderStates[0]['admissions_open'] === 0 && (int)$finderStates[0]['delivery_enabled'] === 0, 'Finder refresh after trash observes committed state and closed gates');
listExpect(!$courseModel->purgeEmpty($ids['Alpha']), 'Purge rejects trashed course with frozen teaching version');
listExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_courses WHERE id=' . (int)$ids['Alpha'])->loadResult() === 1, 'Denied purge leaves trashed course intact');
$trashedList = $factory->createModel('Courses', 'Administrator', ['ignore_request' => true]);
$trashedList->setState('list.limit', 100);
$trashedList->setState('list.start', 0);
$trashedList->setState('filter.search', 'List UI');
$trashedList->setState('filter.published', '-2');
listExpect(array_map(static fn($r):int=>(int)$r->id, $trashedList->getItems()) === [$ids['Alpha']], 'Trashed course appears in the dedicated state filter');
$ordinaryList = $factory->createModel('Courses', 'Administrator', ['ignore_request' => true]);
$ordinaryList->setState('list.limit', 100);
$ordinaryList->setState('list.start', 0);
$ordinaryList->setState('filter.search', 'List UI');
listExpect(!in_array($ids['Alpha'], array_map(static fn($r):int=>(int)$r->id, $ordinaryList->getItems()), true), 'Ordinary list excludes trashed course');
listExpect($courseModel->changeStates([$ids['Alpha']], 0), 'Restore from trash uses a separate action');
listExpect((int)$db->setQuery('SELECT state FROM #__jlms_courses WHERE id=' . (int)$ids['Alpha'])->loadResult() === 0, 'Restored course is unpublished, never republished');
listExpect($db->setQuery('SELECT admissions_open, delivery_enabled FROM #__jlms_courses WHERE id=' . (int)$ids['Alpha'])->loadAssoc() == ['admissions_open' => 0,'delivery_enabled' => 0], 'Restore never opens gates closed by trash');
listExpect(count($finderStates) === 2 && (int)$finderStates[1]['state'] === 0 && (int)$finderStates[1]['admissions_open'] === 0 && (int)$finderStates[1]['delivery_enabled'] === 0, 'Finder refresh after restore observes committed unpublished and gated state');
$app->getDispatcher()->removeListener('onFinderAfterSave', $finderWitness);
listExpect($db->setQuery('SELECT current_version_id FROM #__jlms_courses WHERE id=' . (int)$ids['Alpha'])->loadResult() === $originalVersion, 'Restored course keeps its frozen version pointer');
listExpect([
 $db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . (int)$ids['Alpha'])->loadResult(),
 $db->setQuery('SELECT COUNT(*) FROM #__jlms_course_versions WHERE course_id=' . (int)$ids['Alpha'])->loadResult(),
] === $learningBefore, 'Bulk transitions preserve enrollment/version history');
listExpect(!$courseModel->changeStates([$ids['Alpha']], 0), 'Restoring a course not in the trash is rejected');
listExpect(!$courseModel->changeStates([$ids['Alpha'],$ids['Alpha']], -2), 'Duplicate bulk IDs rejected without mutation');
// Test fixture cleanup: production lifecycle deliberately forbids implicit publication.
$db->setQuery('UPDATE #__jlms_courses SET state=1, admissions_open=' . (int)$originalGates['admissions_open'] . ', delivery_enabled=' . (int)$originalGates['delivery_enabled'] . ', revision=' . (int)$originalRevision . ' WHERE id=' . (int)$ids['Alpha'])->execute();
// Guarded purge: an unused, unversioned course may be deleted from trash.
$emptyModel = $factory->createModel('Course', 'Administrator', ['ignore_request' => true]);
listExpect($emptyModel->save([
 'id' => 0,'revision' => 1,'title' => 'List UI purge empty','alias' => 'list-ui-purge-empty',
 'catid' => $categories['parent'],'language' => '*','catalog_access' => 1,
 'content_access' => 1,'enrollment_mode' => 'auto','summary' => 'Temporary purge fixture',
]), 'Create unversioned course for safe purge');
$emptyId = (int)$emptyModel->getState('course.id');
listExpect($emptyId > 0, 'Unused purge fixture has an ID');
listExpect(!$emptyModel->purgeEmpty($emptyId), 'Purge rejects a course not yet in the trash');
listExpect($emptyModel->changeStates([$emptyId], -2), 'Move unused course into trash');
$impact = $emptyModel->purgeImpact($emptyId);
listExpect($impact['eligible'] && array_sum($impact['references']) === 0, 'Unused trashed course has an eligible impact preview');
listExpect((int)$impact['id'] === $emptyId && (int)$impact['revision'] > 0, 'Purge preview binds to current course revision');
listExpect(!$emptyModel->purgeEmpty($emptyId, (int)$impact['revision'] + 1), 'Purge rejects stale revision after preview');
 // LCM-02: native multilingual associations are references even without a
 // course-version FK. They must block both preview and transactional purge.
 $associationKey = 'jlms-lcm02-purge-' . (string)$emptyId;
 $associationRow = (object)['id' => $emptyId,'context' => 'com_jlms.item','key' => $associationKey];
 $db->insertObject('#__associations', $associationRow);
try {
    $associatedImpact = $emptyModel->purgeImpact($emptyId);
    listExpect(!$associatedImpact['eligible'] && (int)$associatedImpact['references']['#__associations'] === 1, 'Native course association prevents purge preview');
    listExpect(!$emptyModel->purgeEmpty($emptyId, (int)$associatedImpact['revision']), 'Native course association prevents destructive purge');
    listExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_courses WHERE id=' . (int)$emptyId)->loadResult() === 1, 'Rejected associated-course purge retains course');
    listExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__associations WHERE id=" . (int)$emptyId . " AND context='com_jlms.item'")->loadResult() === 1, 'Rejected purge retains native association');
} finally {
    $cleanup = $db->createQuery()->delete('#__associations')->where('id = :id')->where('context = :context')
    ->bind(':id', $emptyId, \Joomla\Database\ParameterType::INTEGER)
    ->bind(':context', $associationRow->context);
    $db->setQuery($cleanup)->execute();
}

// LCM-02: the same physically empty course must still be protected by
// fresh per-record core.delete authorization, not merely by its preview.
$emptyModel->setCurrentUser(new \Joomla\CMS\User\User(0));
try {
    $guestDenied = false;
    try {
        $guestDenied = !$emptyModel->purgeEmpty($emptyId, (int)$impact['revision']);
    } catch (\RuntimeException $denied) {
        $guestDenied = $denied->getMessage() === 'JERROR_ALERTNOAUTHOR';
    }
    listExpect($guestDenied, 'Guest cannot purge a physically empty course');
    listExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_courses WHERE id=' . (int)$emptyId)->loadResult() === 1, 'Denied guest purge preserves course');
} finally {
    $emptyModel->setCurrentUser($operator);
}
$purged = $emptyModel->purgeEmpty($emptyId, (int)$impact['revision']);
listExpect($purged, 'Purge succeeds only on trashed course with no teaching dependencies: ' . (string)$emptyModel->getError());
listExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__history WHERE item_id='com_jlms.course." . (int)$emptyId . "'")->loadResult() === 0, 'Confirmed purge removes only this course editorial revisions');
listExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_courses WHERE id=' . (int)$emptyId)->loadResult() === 0, 'Purge removed only the unreferenced course row');
$restricted = $factory->createModel('Courses', 'Administrator', ['ignore_request' => true]);
$restricted->setCurrentUser(new \Joomla\CMS\User\User($editor));
$restricted->setState('list.limit', 100);
$restricted->setState('list.start', 0);
$restricted->setState('filter.search', 'id:' . $ids['Alpha']);
listExpect(count($restricted->getItems()) === 0 && (int)$restricted->getTotal() === 0, 'Filters and totals cannot expose an uneditable course');
$rows = $db->setQuery('SELECT id,state,current_version_id,revision FROM #__jlms_courses WHERE id IN (' . implode(',', $ids) . ') ORDER BY id')->loadAssocList();
file_put_contents(dirname($root) . '/courses-ui-fixture.json', json_encode(['courses' => $ids,'versions' => $versions,'categories' => $categories,'teacher_id' => $teacherId,'editor' => $editor,'rows' => $rows], JSON_THROW_ON_ERROR));
echo json_encode(['courses_ui_checks' => $checks,'status' => 'passed']) . PHP_EOL;
