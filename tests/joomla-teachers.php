<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/joomla-teachers.functions.php';


/* Run after Joomla's CLI installer and extension:install in an isolated CI site. */

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

require_once __DIR__ . '/helpers/joomla-teachers.bootstrap-defines-01.php';
require $root . '/administrator/includes/defines.php';
require $root . '/administrator/includes/framework.php';

require_once __DIR__ . '/helpers/joomla-teachers.bootstrap-defines-02.php';

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
$catid = (int) $db->setQuery(("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND "
    . "alias='ci-courses'"))->loadResult();
use Jlms\Component\Jlms\Administrator\Application\CoursePresentation;


use Jlms\Component\Jlms\Administrator\Application\TeacherProfiles;
$checks = 0;

// The CLI bootstrap does not set a website locale automatically.
$app->loadLanguage(
    $container->get(\Joomla\CMS\Language\LanguageFactoryInterface::class)->createLanguage('en-GB', false)
);
// CORE-10: reuse of a Joomla ListModel must not carry a course list across
// teacher profiles or a user switch, even when paging parameters match.
$publicTeacherModel = $factory->createModel('Teacher', 'Site', ['ignore_request' => true]);
$teacherCacheMethod = new \ReflectionMethod($publicTeacherModel, 'getStoreId');
$app->getInput()->set('id', 1);
$teacherOneKey = $teacherCacheMethod->invoke($publicTeacherModel, 'teacher-course-list');
$app->getInput()->set('id', 2);
$teacherTwoKey = $teacherCacheMethod->invoke($publicTeacherModel, 'teacher-course-list');
teacherExpect($teacherOneKey !== $teacherTwoKey, 'CORE-10: public teacher lists isolate teacher IDs');
$app->getInput()->set('id', 1);
$app->loadIdentity(new \Joomla\CMS\User\User());
$guestTeacherKey = $teacherCacheMethod->invoke($publicTeacherModel, 'teacher-course-list');
teacherExpect($guestTeacherKey !== $teacherOneKey, 'CORE-10: public teacher lists isolate identity');
$app->loadIdentity($operator);
teacherExpect(
    $teacherCacheMethod->invoke($publicTeacherModel, 'teacher-course-list') === $teacherOneKey,
    'CORE-10: public teacher list identity scope is stable'
);
$app->getInput()->set('id', 0);

$teacherListTemplate = file_get_contents(dirname(__DIR__) . '/component/administrator/tmpl/teachers/default.php');
teacherExpect($teacherListTemplate !== false && str_contains(
    $teacherListTemplate,
    "HTMLHelper::_('jgrid.published'"
) && str_contains(
    $teacherListTemplate,
    "'teachers.'"
), 'Teacher state uses Joomla status icons without enabling inline state ' .
    'changes');
teacherExpect($teacherListTemplate !== false && str_contains($teacherListTemplate, "'btn-success'")
    && str_contains(
        $teacherListTemplate,
        "'btn-secondary'"
    ) && !str_contains(
        $teacherListTemplate,
        'badge bg-info text-dark'
    ), 'Teacher course count follows Joomla category count button contrast');
$fixture = dirname($root) . '/teachers-fixture.json';
if (($argv[2] ?? '') === 'retained') {
    $f = json_decode(file_get_contents($fixture), true, 32, JSON_THROW_ON_ERROR);
    teacherExpect($db->setQuery(('SELECT presentation_json,structure_hash,published_at FROM '
        . '#__jlms_course_versions WHERE id=') . (int)$f['version'])->loadAssoc() ===
             $f['snapshot'], 'Frozen teacher projection survives upgrade');
    $outline = $catalog->outline(new \Joomla\CMS\User\User(), 'en-GB', (int)$f['course']);
    teacherExpect(count($outline['teachers']) === 2 && $outline['teachers'][0]['title'] ===
         'Updated public teacher', 'Current teacher projection survives upgrade while the frozen projection ' .
        'is unchanged');
    teacherExpect(
        TeacherProfiles::selected($db, (int)$f['course']) === $f['ids'],
        'Assigned teacher relations survive upgrade'
    );
    echo json_encode(['teacher_retention_checks' => $checks,'status' => 'passed']) . PHP_EOL;
    exit;
}
$categoryModel = $app->bootComponent('com_categories')->getMVCFactory()
    ->createModel('Category', 'Administrator', ['ignore_request' => true]);
teacherExpect($categoryModel->save(['id' => 0,'parent_id' => 1,'extension' => 'com_jlms.teacher','title'
    => 'CI Teachers','alias' => 'ci-teachers','published' => 1,'language' => '*','access'
        => 1]), 'Native teacher category created');
$teacherCategory = (int)$categoryModel->getState('category.id');
$teacherModel = $factory->createModel('Teacher', 'Administrator', ['ignore_request' => true]);
$teacherBase = ['id' => 0,'revision' => 1,'title' => 'Original public teacher','alias'
    => 'original-public-teacher','catid' => $teacherCategory,'bio'
        => ('<p>Public <strong>biography</strong> '
            . '<script>alert(1)</script><iframe '
            . 'src="https://example.invalid"></iframe><a '
            . 'href="javascript:alert(2)" onclick="alert(3)">read</a></p>'),'image' => '','image_alt' =>
                 '','image_decorative' => '0','website' => 'https://example.invalid/teacher','language' =>
                 '*','access' => 1,'state' => 1,'user_id' => (int)$actor->id];
$groups = $db->setQuery('SELECT user_id,group_id FROM #__user_usergroup_map ORDER BY user_id,group_id')
    ->loadAssocList();
teacherExpect($teacherModel->save($teacherBase + ['params_json' => '{"injected":true}','created_by'
    => 999999]), 'Teacher saved through native model');
$public = (int)$teacherModel->getState('teacher.id');
$teacher = $teacherModel->getItem($public);
teacherExpect(
    $teacherModel->getTable('Teacher') instanceof \Joomla\CMS\Tag\TaggableTableInterface,
    'CORE-09: teacher uses Joomla native taggable table'
);
$nativeForm = $teacherModel->getForm([], false);
teacherExpect(
    $nativeForm !== false && $nativeForm->getField('catid') !== false
    && $nativeForm->getField('tags') !== false,
    'CORE-01/09/20: native teacher category and tag selectors available'
);
teacherExpect(
    $teacher->tags instanceof \Joomla\CMS\Helper\TagsHelper,
    'CORE-09: saved teacher populates native Joomla tag editor'
);
// A valid leaf beneath a trashed ancestor must never pass the teacher
// model or native table guard, even when the editor has core.create.
teacherExpect($categoryModel->save([
    'id' => 0, 'parent_id' => 1, 'extension' => 'com_jlms.teacher',
    'title' => 'CI teacher trashed parent', 'alias' => 'ci-teacher-trashed-parent',
    'published' => 1, 'language' => '*', 'access' => 1,
]), 'CORE-01: teacher nested parent fixture created');
$trashedTeacherParent = (int)$categoryModel->getState('category.id');
teacherExpect($categoryModel->save([
    'id' => 0, 'parent_id' => $trashedTeacherParent, 'extension' => 'com_jlms.teacher',
    'title' => 'CI teacher nested child', 'alias' => 'ci-teacher-nested-child',
    'published' => 1, 'language' => '*', 'access' => 1,
]), 'CORE-01: teacher nested child fixture created');
$trashedTeacherChild = (int)$categoryModel->getState('category.id');
$db->setQuery('UPDATE #__categories SET published=-2 WHERE id=' . $trashedTeacherParent)->execute();
teacherExpect(
    !$teacherModel->save(array_replace($teacherBase, [
        'catid' => $trashedTeacherChild, 'alias' => 'ci-invalid-ancestor-teacher',
    ])),
    'CORE-01/04: creating a teacher under a trashed ancestor must fail'
);
teacherExpect(
    (int)$db->setQuery("SELECT COUNT(*) FROM #__jlms_teachers WHERE alias='ci-invalid-ancestor-teacher'")
        ->loadResult() === 0,
    'CORE-01/04: denied teacher creation leaves no partial record'
);
$storedTeacherBio = (string)$db->setQuery('SELECT bio FROM #__jlms_teachers WHERE id=' . $public)->loadResult();
teacherExpect(
    str_contains($storedTeacherBio, '<strong>biography</strong>')
    && !preg_match('~<(?:script|iframe)\\b|\\bonclick\\s*=|javascript:~i', $storedTeacherBio),
    'public teacher biography retains safe formatting but strips scripts, arbitrary embeds and active URLs'
);
// CORE-10: teacher field language must match the effective teacher language,
// even when the profile itself is neutral and the viewer switches locales.
// The Joomla CLI fixture has no web language by default. Native com_fields
// needs an application language to resolve the field type/plugin labels.
$app->loadLanguage(
    $container->get(\Joomla\CMS\Language\LanguageFactoryInterface::class)->createLanguage('en-GB', false)
);
$teacherFieldModel = $app->bootComponent('com_fields')->getMVCFactory()
    ->createModel('Field', 'Administrator', ['ignore_request' => true]);
foreach (['en-GB' => 'teacher-field-en', 'it-IT' => 'teacher-field-it'] as $tag => $name) {
    teacherExpect($teacherFieldModel->save([
        'id' => 0, 'context' => 'com_jlms.teacher', 'title' => $name, 'name' => $name,
        'type' => 'text', 'state' => 1, 'access' => 1, 'language' => $tag,
        'group_id' => 0, 'default_value' => 'Visible ' . $name, 'description' => '',
        'params' => [], 'fieldparams' => ['filter' => 'string'],
    ]), 'Native Joomla teacher custom-field language fixture saved');
}
$app->getInput()->set('id', $public);
$teacherSiteModel = $factory->createModel('Teacher', 'Site', ['ignore_request' => true]);
$translatedProfile = $teacherSiteModel->getProfile();
$fieldNames = array_map(static fn (object $field): string => (string) $field->name, $translatedProfile->fields);
teacherExpect(
    in_array('teacher-field-en', $fieldNames, true)
    && !in_array('teacher-field-it', $fieldNames, true),
    'Native Joomla teacher model hides Italian-only fields in English'
);
// The disposable Joomla installation has no Italian language files. Prove
// both branches of the exact production filter without replacing the site's
// Language instance with an unavailable locale (getTag() would be null).
$fixtureFields = [
    (object) ['name' => 'teacher-field-en', 'language' => 'en-GB'],
    (object) ['name' => 'teacher-field-it', 'language' => 'it-IT'],
    (object) ['name' => 'teacher-field-neutral', 'language' => '*'],
];
foreach (['en-GB' => 'teacher-field-en', 'it-IT' => 'teacher-field-it'] as $tag => $expected) {
    $filtered = \Jlms\Component\Jlms\Site\Model\TeacherModel::filterPublicFields($fixtureFields, $tag);
    $names = array_map(static fn (object $field): string => (string) $field->name, $filtered);
    teacherExpect(
        in_array($expected, $names, true)
        && in_array('teacher-field-neutral', $names, true)
        && !in_array($tag === 'en-GB' ? 'teacher-field-it' : 'teacher-field-en', $names, true),
        'Public teacher custom fields are constrained to the effective locale'
    );
}
$app->getInput()->set('id', 0);
// CORE-11: a trusted content plugin may modify a public teacher biography
// but may not bypass the final allowlist.
$teacherEvents = [];
$app->getDispatcher()->addListener(
    'onContentPrepare',
    static function (\Joomla\CMS\Event\Content\ContentPrepareEvent $event) use (&$teacherEvents, $public): void {
        if ($event->getContext() !== 'com_jlms.teacher') {
            return;
        }
        $item = $event->getItem();
        $teacherEvents[] = [(int) $item->id, $event->getContext()];
        $item->text .= '<em>PUBLIC_TEACHER_PREPARED</em><iframe src="https://example.invalid"></iframe>';
    }
);
$preparedBio = \Jlms\Component\Jlms\Site\Helper\PublicContentPreparation::html(
    'com_jlms.teacher',
    $public,
    $storedTeacherBio
);
teacherExpect(
    $teacherEvents === [[$public, 'com_jlms.teacher']]
    && str_contains($preparedBio, '<strong>biography</strong>')
    && str_contains($preparedBio, '<em>PUBLIC_TEACHER_PREPARED</em>')
    && !str_contains($preparedBio, '<iframe'),
    'Native teacher content preparation preserves safe rich text and strips injected embeds'
);
// SEC-P1: the session identity loaded before a block must never authorize
// a teacher edit, including links and forms that call the shared helper.
teacherExpect(TeacherProfiles::canEdit($actor, $teacher), 'teacher profile author may edit before account block');
$db->setQuery('UPDATE #__users SET block=1 WHERE id=' . (int)$actor->id)->execute();
try {
    teacherExpect(
        !TeacherProfiles::canEdit($actor, $teacher),
        'blocked Joomla account cannot edit teacher through a stale session identity'
    );
} finally {
    $db->setQuery('UPDATE #__users SET block=0 WHERE id=' . (int)$actor->id)->execute();
}
teacherExpect(
    TeacherProfiles::canEdit($actor, $teacher),
    'teacher profile edit authorization restores after account unblocked'
);
teacherExpect((int)$teacher->revision === 1 && (int)$teacher->created_by === (int)$actor->id
    && !str_contains($teacher->params_json, 'injected'), 'Revision ownership and allowlisted params persisted');
teacherExpect(
    ($teacher->website ?? null) === 'https://example.invalid/teacher',
    'SECDEV-03: harmless public teacher website remains available'
);
$asset = new \Joomla\CMS\Table\Asset($db);
$asset->loadByName('com_jlms.teacher.' . $public);
$parent = (int)$db->setQuery('SELECT asset_id FROM #__categories WHERE id=' . $teacherCategory)->loadResult();
teacherExpect((int)$asset->parent_id === $parent, 'Teacher asset inherits native teacher category');
teacherExpect($groups === $db->setQuery('SELECT user_id,group_id FROM #__user_usergroup_map ORDER BY ' .
    'user_id,group_id')->loadAssocList(), 'Account link grants no groups');
teacherExpect($teacherModel->save(array_replace($teacherBase, ['title' => 'PRIVATE_TEACHER_NAME','alias'
    => 'private-teacher','access' => 2,'bio' => 'PRIVATE_TEACHER_BIO','user_id'
        => ''])), 'Restricted teacher saved without native account');
$private = (int)$teacherModel->getState('teacher.id');
$courseModel = $factory->createModel('Course', 'Administrator', ['ignore_request' => true]);
$courseBase = ['id' => 0,'revision' => 1,'title' => 'Teacher course fixture','alias'
    => 'teacher-course-fixture','catid' => $catid,'language' => '*','catalog_access' => 1,'content_access'
        => 2,'enrollment_mode' => 'auto','presentation_description'
            => '<p>Course presentation for the publication fixture.</p>', 'summary' => 'Teacher course'];
teacherExpect(!$courseModel->save($courseBase + ['teacher_ids'
    => [$public]]), 'Course must be saved before atomic assignment');
teacherExpect($courseModel->save($courseBase), 'Draft course saved');
$course = (int)$courseModel->getState('course.id');
$courseData = array_replace($courseBase, ['id' => $course,'teacher_ids' => [$public,$private]]);
teacherExpect($courseModel->save($courseData), 'Ordered teachers assigned to saved draft');
teacherExpect(
    TeacherProfiles::selected($db, $course) === [$public,$private],
    'Teacher order persisted in relation table'
);

$teacherNoticeCount = static function () use ($db, $course): int {
    return (int) $db->setQuery("SELECT COUNT(*) FROM #__jlms_mail_queue WHERE course_id=" . $course
        . " AND template_id='com_jlms.teacher.assigned'")->loadResult();
};
teacherExpect($teacherNoticeCount() === 1, ('A newly assigned linked Joomla account receives exactly one '
    . 'teacher notice; unlinked profile receives none'));
$teacherNotice = $db->setQuery(("SELECT recipient_user_id,recipient_email,template_data FROM "
    . "#__jlms_mail_queue WHERE course_id=")
    . $course . " AND template_id='com_jlms.teacher.assigned'")->loadObject();
$teacherMailData = json_decode((string) $teacherNotice->template_data, true);
teacherExpect(
    (int) $teacherNotice->recipient_user_id === (int) $actor->id
    && (string) $teacherNotice->recipient_email === (string) $actor->email
    && ($teacherMailData['TEACHER_NAME'] ?? '') === 'Original public teacher'
    && ($teacherMailData['COURSE_DESCRIPTION'] ?? '') === 'Course presentation for the publication fixture.'
    && !str_contains((string) $teacherNotice->template_data, 'PRIVATE_TEACHER_BIO'),
    ('Teacher notification supplies scoped course/profile context to the '
        . 'linked Joomla identity without disclosing unrelated details')
);

$directory = $factory->createModel('Teachers', 'Administrator', ['ignore_request' => true]);
$directory->setCurrentUser($actor);
 $teacherRows = $directory->getItems();
if (!is_array($teacherRows)) {
    throw new RuntimeException('Teacher directory listing failed: ' . (string) $directory->getError());
}
$publicRow = array_values(array_filter($teacherRows, static fn($row): bool=>(int)$row->id === $public));
teacherExpect(count($publicRow) === 1 && (int)$publicRow[0]->course_count === 1, ('Teacher list reports the number '
    . 'of assigned courses in the current editable course scope'));
$courseList = $factory->createModel('Courses', 'Administrator', ['ignore_request' => true]);
$courseList->setCurrentUser($actor);
$courseList->setState('list.limit', 100);
$courseList->setState('filter.teacher_id', (string)$public);
teacherExpect(array_map(static fn($row): int=>(int)$row->id, $courseList
    ->getItems()) === [$course], 'Native course list filters by assigned teacher');
$courseList->setState('filter.teacher_id', '1 OR 1=1');
teacherExpect($courseList->getItems() === [], 'Malformed teacher filter cannot broaden the course list');
$courseData['revision'] = 2;
unset($courseData['teacher_ids']);
teacherExpect($courseModel->save($courseData), 'Legacy client can save without teacher field');
teacherExpect(
    TeacherProfiles::selected($db, $course) === [$public,$private],
    'Omitted teacher field preserves assignment'
);
foreach ([[999999],[$public,$public],['1 OR 1=1'],[['bad']]] as $bad) {
    teacherExpect(!$courseModel->save(array_replace($courseData, ['revision' => 3,'teacher_ids'
        => $bad])), 'Invalid teacher assignment denied');
    teacherExpect(TeacherProfiles::selected($db, $course) === [$public,$private] && (int)$courseModel
        ->getItem($course)->revision === 3, 'Denied assignment preserves course and relations');
}
$edit = array_replace($teacherBase, ['id' => $public]);
foreach (
    [['website' => 'javascript:alert(1)'],['website' => 'https://user:pass@example.invalid/'],
    ['website' => 'https://example.invalid/?accessToken=TEST_CREDENTIAL'],
    ['website' => 'https://example.invalid/path#clientSecret=TEST_CREDENTIAL'],
    ['website' => 'https://example.invalid/?api_key=TEST_CREDENTIAL'],
    ['website' => ['https://example.invalid/']],['state' => '-1'],['image_alt' => ['array']],['access'
        => ['array']],['user_id' => 99999999],['bio' => str_repeat('a', 65536)]] as $bad
) {
    teacherExpect(!$teacherModel->save(array_replace($edit, $bad)), 'Invalid teacher profile rejected');
    teacherExpect((int)$teacherModel->getItem($public)->revision === 1, 'Invalid profile retains revision');
}
$teacherModel->setCurrentUser(new \Joomla\CMS\User\User());
teacherExpect(!$teacherModel->save($edit), 'Guest cannot manage teachers');
$teacherModel->setCurrentUser($actor);
$version = $authoring->createDraft($actor, $course, 3);
$rev = static fn(): int=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id=' . $version)
    ->loadResult();
$module = $authoring->saveModule($actor, ['course_id' => $course,'version_id'
    => $version,'expected_version_revision' => $rev(),'title' => 'Teacher chapter']);
$authoring->saveLesson($actor, ['course_id' => $course,'version_id' => $version,'expected_version_revision'
    => $rev(),'module_id' => $module,'title' => 'Teacher lesson','kind' => 'text','required' => '1','body'
        => '<p>TEACHER_PRIVATE_LESSON</p>']);
$withdrawn = (object)['id' => $public,'state' => 0];
$db->updateObject('#__jlms_teachers', $withdrawn, 'id');
try {
    $publication->transition($actor, $course, $version, (int)$courseModel->getItem($course)
        ->revision, $rev(), (int)$publication->transitions($actor, $course)[0]->id);
    throw new RuntimeException('Withdrawn teacher accepted for publication');
} catch (InvalidArgumentException) {
    $checks++;
}
$withdrawn->state = 1;
$db->updateObject('#__jlms_teachers', $withdrawn, 'id');
$publication->transition($actor, $course, $version, (int)$courseModel->getItem($course)
    ->revision, $rev(), (int)$publication->transitions($actor, $course)[0]->id);
$snapshot = $db->setQuery(('SELECT presentation_json,structure_hash,published_at FROM '
    . '#__jlms_course_versions WHERE id=') . $version)->loadAssoc();
$guest = new \Joomla\CMS\User\User();
$outline = $catalog->outline($guest, 'en-GB', $course);
teacherExpect(
    count($outline['teachers']) === 1 && $outline['teachers'][0]['id'] === $public,
    'Guest only sees authorised public teacher'
);
teacherExpect(!str_contains(json_encode($outline), 'PRIVATE_TEACHER_BIO')
    && !str_contains(json_encode($outline), 'PRIVATE_TEACHER_NAME')
        && !str_contains(
            json_encode($outline),
            'user_id'
        ) && !str_contains(
            json_encode($outline),
            'ci@example.invalid'
        ), 'Public projection excludes restricted profile and linked account data');
teacherExpect(
    count($catalog->outline($actor, 'en-GB', $course)['teachers']) === 2,
    'Authorised account can see both frozen teachers'
);
$edit['title'] = 'Updated public teacher';
$edit['bio'] = 'Updated public biography';
teacherExpect($teacherModel->save($edit), 'Public profile can be updated independently');
teacherExpect(
    $catalog->outline($guest, 'en-GB', $course)['teachers'][0]['title'] === 'Updated public teacher',
    'Public profile reflects the current teacher presentation'
);
teacherExpect(!$teacherModel->save($edit), 'Stale teacher revision rejected');
$edit['revision'] = 2;
$edit['state'] = 0;
teacherExpect($teacherModel->save($edit), 'Teacher can be withdrawn with state permission');
teacherExpect(
    $catalog->outline($guest, 'en-GB', $course)['teachers'] === [],
    'Withdrawal hides historical profile without changing snapshot'
);
$edit['revision'] = 3;
$edit['state'] = 1;
$edit['access'] = 2;
teacherExpect($teacherModel->save($edit), 'Teacher republished with stricter level');
teacherExpect(
    $catalog->outline($guest, 'en-GB', $course)['teachers'] === [],
    'Current teacher level rechecked for historical data'
);
$edit['revision'] = 4;
$edit['access'] = 1;
teacherExpect($teacherModel->save($edit), 'Teacher public availability restored');
$privateEdit = array_replace($teacherBase, ['id' => $private,'title' => 'PRIVATE_TEACHER_NAME','alias'
    => 'private-teacher','access' => 1,'bio' => 'PRIVATE_TEACHER_BIO','user_id' => '']);
teacherExpect($teacherModel->save($privateEdit), 'Restricted profile can become public for future versions');
teacherExpect(count($catalog->outline($guest, 'en-GB', $course)['teachers']) === 2, ('An explicit change to public '
    . 'profile visibility affects the current public listing'));
$learner = new \Joomla\CMS\User\User();
$learnerData = ['name' => 'CI teacher viewer','username' => 'citeacherviewer','email'
    => 'citeacherviewer@example.invalid','password' => 'Ci-only-Teacher-Viewer-2026','password2'
        => 'Ci-only-Teacher-Viewer-2026','groups' => [1,2],'block' => 0,'activation' => ''];
if (!$learner->bind($learnerData) || !$learner->save()) {
    throw new RuntimeException('Viewer fixture failed');
}
$learner = new \Joomla\CMS\User\User((int)$learner->id);
$learner->getAuthorisedViewLevels();
teacherExpect(
    count($catalog->outline($learner, 'en-GB', $course)['teachers']) === 2,
    'Registered viewer sees originally registered snapshot'
);
$directory = $factory->createModel('Teachers', 'Administrator', ['ignore_request' => true]);
$directory->setCurrentUser($actor);
$directory->setState('filter.search', 'Updated public');
$directory->setState('filter.published', '1');
$filtered = $directory->getItems();
teacherExpect(is_array($filtered) && count($filtered) === 1 && (int)$filtered[0]
    ->id === $public, 'Teacher administration list searches title and filters published profiles');
$directory = $factory->createModel('Teachers', 'Administrator', ['ignore_request' => true]);
$directory->setCurrentUser($learner);
try {
    $items = $directory->getItems();
    teacherExpect(
        $items === false && $directory->getError() !== null,
        'Native list model rejects unauthorised directory reads'
    );
} catch (RuntimeException $denied) {
    teacherExpect($denied->getCode() === 403, 'Authenticated viewer cannot read administrative teacher directory');
}
$teacherModel->setCurrentUser($learner);
teacherExpect(!$teacherModel->save(array_replace($edit, ['revision'
    => 5])), 'Authenticated viewer lacks teacher management permission');
$teacherModel->setCurrentUser($actor);
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$learner->id, 2);
teacherExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__user_usergroup_map WHERE user_id=' . (int)$learner
    ->id . ' AND group_id=2')->loadResult() ===
         0, 'Native group revocation actually persisted before checking cached identity');
teacherExpect(
    count($catalog->outline($learner, 'en-GB', $course)['teachers']) === 2,
    'The current public profiles remain visible without registered access'
);
$categoryLanguage = (object)['id' => $teacherCategory,'language' => 'en-GB'];
$db->updateObject('#__categories', $categoryLanguage, 'id');
teacherExpect(
    TeacherProfiles::visible($db, $guest, 'it-IT', $snapshot['presentation_json']) === [],
    'Current ancestor category language gates historical profile'
);
$categoryLanguage->language = '*';
$db->updateObject('#__categories', $categoryLanguage, 'id');
$update = (object)['id' => $teacherCategory,'published' => 0];
$db->updateObject('#__categories', $update, 'id');
teacherExpect(
    $catalog->outline($guest, 'en-GB', $course)['teachers'] === [],
    'Native category withdrawal gates teacher history'
);
$update->published = 1;
$db->updateObject('#__categories', $update, 'id');
teacherExpect($courseModel->save(array_replace($courseData, ['revision' => (int)$courseModel->getItem($course)
    ->revision,'teacher_ids' => [$public,$private]])), ('Teachers can be updated on a published course while '
        . 'preserving the teaching snapshot'));
teacherExpect($snapshot === $db->setQuery(('SELECT presentation_json,structure_hash,published_at FROM '
    . '#__jlms_course_versions WHERE id=') . $version)->
        loadAssoc(), 'All profile changes preserve course snapshot hash and timestamp');
// LCM-04: reversible teacher trash preserves Joomla users, course links and frozen snapshots.
$beforeCourseTeachers = TeacherProfiles::selected($db, $course);
$beforeUsers = $db->setQuery('SELECT id, block FROM #__users WHERE id=' . (int) $actor->id)->loadAssoc();
$beforeSnapshot = $db->setQuery(('SELECT presentation_json,structure_hash,published_at FROM '
    . '#__jlms_course_versions WHERE id=') . $version)->loadAssoc();
teacherExpect(
    $teacherModel->changeStates([$public, $private], -2),
    'bulk teacher trash succeeds without modifying associations'
);
teacherExpect(
    (int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_teachers WHERE id IN (' . $public . ',' . $private
        . ') AND state=-2')->loadResult() === 2,
    'both teachers are trashed'
);
teacherExpect(
    TeacherProfiles::available($db, $guest, 'en-GB', [$public, $private]) === [],
    'trashed teacher profiles are excluded from public visibility'
);
// CORE-04: one foreign checkout must reject a mixed batch atomically, even
// when its first record was otherwise editable by the current administrator.
$teacherIds = [$public, $private];
$teacherSnapshot = static function () use ($db, $teacherIds): array {
    return $db->setQuery('SELECT id,state,revision FROM #__jlms_teachers WHERE id IN ('
        . implode(',', array_map('intval', $teacherIds)) . ') ORDER BY id')->loadAssocList();
};
$beforeTeacherBulk = $teacherSnapshot();
$foreignCheckout = (object) [
    'id' => $private, 'checked_out' => (int) $learner->id,
];
$db->updateObject('#__jlms_teachers', $foreignCheckout, 'id');
try {
    teacherExpect(
        !$teacherModel->changeStates([$public, $private], 0),
        'CORE-04: foreign checkout in mixed teacher batch rejects entire restoration'
    );
    teacherExpect(
        $teacherSnapshot() === $beforeTeacherBulk,
        'CORE-04: teacher checkout conflict rolls back states and revisions of prior records'
    );
} finally {
    $releaseCheckout = (object) [
        'id' => $private, 'checked_out' => 0,
    ];
    $db->updateObject('#__jlms_teachers', $releaseCheckout, 'id');
}
// CORE-04: reject malformed lifecycle selections before any write. In
// particular, a duplicate ID or a 101-record batch must not be silently
// deduplicated/truncated into an apparently successful partial operation.
$invalidTeacherBatches = [
    [[], 0, 'empty selection'],
    [[$public, $public], 0, 'duplicate ID'],
    [[(string) $public], 0, 'string ID'],
    [[0], 0, 'zero ID'],
    [range(1000000, 1000100), 0, 'oversized batch'],
    [[$public], 2, 'unsupported transition'],
];
foreach ($invalidTeacherBatches as [$invalidIds, $invalidTarget, $reason]) {
    teacherExpect(
        !$teacherModel->changeStates($invalidIds, $invalidTarget),
        'CORE-04: teacher bulk rejects ' . $reason
    );
    teacherExpect(
        $teacherSnapshot() === $beforeTeacherBulk,
        'CORE-04: rejected ' . $reason . ' retains all teacher states and revisions'
    );
}
// Forging the same bulk operation with a viewer must not reuse a privileged
// prior session or change either teacher, even with the model instance reused.
$teacherModel->setCurrentUser($learner);
teacherExpect(
    !$teacherModel->changeStates([$public, $private], 0),
    'CORE-03/04: unauthorised logged-in viewer cannot bulk restore teacher profiles'
);
$teacherModel->setCurrentUser($actor);
teacherExpect(
    $teacherSnapshot() === $beforeTeacherBulk,
    'CORE-03/04: denied viewer batch leaves teacher state and revision unchanged'
);
teacherExpect(
    !$teacherModel->changeStates([$public, 999999999], 0),
    'mixed selection with absent teacher is rolled back atomically'
);
teacherExpect(
    (int) $db->setQuery('SELECT state FROM #__jlms_teachers WHERE id=' . $public)->loadResult() === -2,
    'rollback leaves originally trashed teacher unchanged'
);
teacherExpect(
    !$teacherModel->changeStates([$public], 1),
    'direct republication from trash is rejected'
);
teacherExpect(
    $teacherModel->changeStates([$public, $private], 0),
    'bulk teacher restoration succeeds to unpublished only'
);
teacherExpect(
    (int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_teachers WHERE id IN (' . $public . ',' . $private
        . ') AND state=0')->loadResult() === 2,
    'restoration does not republish teacher profiles'
);
teacherExpect(
    TeacherProfiles::selected($db, $course) === $beforeCourseTeachers,
    'trashed and restored teachers retain all course associations'
);
teacherExpect(
    $db->setQuery('SELECT id, block FROM #__users WHERE id=' . (int) $actor->id)->loadAssoc() === $beforeUsers,
    'teacher lifecycle never changes linked Joomla user status'
);
teacherExpect(
    $db->setQuery('SELECT presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id='
        . $version)->loadAssoc() === $beforeSnapshot,
    'teacher lifecycle preserves immutable course snapshot'
);
teacherExpect(
    $teacherModel->changeStates([$public, $private], 1),
    'teachers are explicitly republished after restoration'
);
// LCM-04: permanent removal is a deliberate, single-record action with a
// conservative dependency gate. Native users and course history are immutable.
$linkedBefore = $db->setQuery('SELECT id, block FROM #__users WHERE id=' . (int) $actor->id)->loadAssoc();
$versionBefore = $db->setQuery(('SELECT presentation_json,structure_hash,published_at FROM '
    . '#__jlms_course_versions WHERE id=') . $version)->loadAssoc();
teacherExpect(
    !$teacherModel->purgeUnreferenced($public),
    'published teacher profile cannot be deleted permanently'
);
teacherExpect($teacherModel->changeStates([$public], -2), 'assigned teacher can be trashed');
teacherExpect(
    !$teacherModel->purgeUnreferenced($public),
    'teacher with associated courses cannot be deleted permanently'
);
teacherExpect(
    (int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_teachers WHERE id=' . $public)->loadResult() === 1,
    'blocked deletion retains teacher profile'
);
teacherExpect(!$teacherModel->purgeUnreferenced(0), 'invalid teacher purge ID denied');
// Even after an association is removed, the immutable published version
// must prevent removing a previously published teacher identity.
$db->setQuery('DELETE FROM #__jlms_course_teachers WHERE course_id=' . $course . ' AND teacher_id=' . $public)
    ->execute();
teacherExpect(
    !$teacherModel->purgeUnreferenced($public),
    'frozen teacher snapshot independently blocks destructive deletion'
);
$db->setQuery('INSERT INTO #__jlms_course_teachers (course_id,teacher_id,ordering) VALUES ('
    . $course . ',' . $public . ',0)')->execute();
teacherExpect($teacherModel->changeStates([$public], 0)
    && $teacherModel->changeStates([$public], 1), 'published teacher fixture restored');
teacherExpect(
    TeacherProfiles::selected($db, $course) === $beforeCourseTeachers,
    'protected deletion does not disturb teacher ordering'
);
teacherExpect(
    $db->setQuery('SELECT id, block FROM #__users WHERE id=' . (int) $actor->id)->loadAssoc() === $linkedBefore,
    'deletion attempt never removes or blocks linked Joomla account'
);
teacherExpect(
    $db->setQuery('SELECT presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id='
        . $version)->loadAssoc() === $versionBefore,
    'frozen presentation remains byte-for-byte identical'
);

$linkedUserId = (int)$db->setQuery('SELECT id FROM #__users WHERE id <> ' . (int)$actor->id
    . ' ORDER BY id LIMIT 1')->loadResult();
teacherExpect($linkedUserId > 0, 'second Joomla user exists to test independent teacher account dependency');
$linkedUserBlock = (int)$db->setQuery('SELECT block FROM #__users WHERE id=' . $linkedUserId)->loadResult();
$emptyTeacher = array_replace($teacherBase, [
    'id' => 0, 'revision' => 1, 'title' => 'Disposable CI empty teacher',
    'alias' => 'disposable-ci-empty-teacher', 'user_id' => $linkedUserId, 'state' => 0,
]);
teacherExpect(
    $teacherModel->save($emptyTeacher),
    'new teacher linked to an independent Joomla account can be authored'
);
$emptyTeacherId = (int)$teacherModel->getState('teacher.id');
teacherExpect($emptyTeacherId > 0, 'empty teacher fixture created');
teacherExpect($teacherModel->changeStates([$emptyTeacherId], -2), 'empty teacher placed in trash');
// LCM-04: linking a native Joomla account forbids destructive purge even
// when there are no course assignments or frozen presentation references.
teacherExpect(
    !$teacherModel->purgeUnreferenced($emptyTeacherId),
    'unassigned trashed teacher linked to a Joomla account cannot be purged'
);
teacherExpect(
    (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_teachers WHERE id=' . $emptyTeacherId)->loadResult() === 1,
    'linked teacher profile survives denied purge'
);
$db->setQuery('UPDATE #__users SET block=1 WHERE id=' . $linkedUserId)->execute();
try {
    teacherExpect(
        !$teacherModel->purgeUnreferenced($emptyTeacherId),
        'blocked native Joomla account must not make linked teacher purgable'
    );
} finally {
    $db->setQuery('UPDATE #__users SET block=' . $linkedUserBlock . ' WHERE id=' . $linkedUserId)->execute();
}
teacherExpect(
    (int)$db->setQuery('SELECT user_id FROM #__jlms_teachers WHERE id=' . $emptyTeacherId)
        ->loadResult() === $linkedUserId,
    'denied purges retain teacher-to-Joomla-account reference'
);
// Explicitly unlink only the disposable teacher fixture to allow purging.
$db->setQuery('UPDATE #__jlms_teachers SET user_id=NULL WHERE id=' . $emptyTeacherId)->execute();

// Joomla multilingual associations survive outside the teacher FK graph.
$association = (object)[
    'id' => $emptyTeacherId, 'context' => 'com_jlms.teacher',
    'key' => md5('jd-learn-ci-teacher-' . $emptyTeacherId),
];
$db->insertObject('#__associations', $association);
teacherExpect(
    !$teacherModel->purgeUnreferenced($emptyTeacherId),
    'multilingual association blocks permanent teacher deletion'
);
teacherExpect(
    (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_teachers WHERE id=' . $emptyTeacherId)->loadResult() === 1,
    'blocked teacher purge preserves profile'
);
$db->setQuery("DELETE FROM #__associations WHERE context='com_jlms.teacher' AND id=" . $emptyTeacherId)->execute();
// LCM-04: declared third-party FK must block deletion even with CASCADE.
$externalTable = '#__jlms_ci_teacher_fk_guard';
$db->setQuery('CREATE TABLE ' . $db->quoteName($externalTable)
    . ' (teacher_id BIGINT UNSIGNED NOT NULL, note VARCHAR(40) NOT NULL,'
    . ' CONSTRAINT jlms_ci_teacher_fk FOREIGN KEY (teacher_id) REFERENCES '
    . $db->quoteName('#__jlms_teachers') . ' (id) ON DELETE CASCADE) ENGINE=InnoDB')->execute();
try {
    $db->setQuery('INSERT INTO ' . $db->quoteName($externalTable)
        . ' (teacher_id,note) VALUES (' . (int)$emptyTeacherId . ", 'must-survive')")->execute();
    teacherExpect(
        !$teacherModel->purgeUnreferenced($emptyTeacherId),
        'external FK blocks teacher purge even with ON DELETE CASCADE'
    );
    teacherExpect(
        (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_teachers WHERE id=' . (int)$emptyTeacherId)
            ->loadResult() === 1,
        'external reference denial preserves teacher'
    );
    teacherExpect(
        (int)$db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName($externalTable))->loadResult() === 1,
        'external reference denial preserves integration history'
    );
} finally {
    $db->setQuery('DROP TABLE IF EXISTS ' . $db->quoteName($externalTable))->execute();
}
teacherExpect(
    $teacherModel->purgeUnreferenced($emptyTeacherId),
    'empty unreferenced trashed teacher may be permanently deleted: ' . $teacherModel->getError()
);
teacherExpect(
    (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_teachers WHERE id=' . $emptyTeacherId)->loadResult() === 0,
    'empty teacher profile record removed'
);
teacherExpect(
    $db->setQuery('SELECT id, block FROM #__users WHERE id=' . (int) $actor->id)->loadAssoc() === $linkedBefore,
    'empty profile deletion never touches Joomla accounts'
);
// CORE-09: verify actual Joomla tag association persistence, replacement,
// rehydration and removal, not merely that the form exposes a tag field.
$teacherTypeMapping = json_decode((string) $db->setQuery(
    "SELECT field_mappings FROM #__content_types WHERE type_alias='com_jlms.teacher'"
)->loadResult(), true, 32, JSON_THROW_ON_ERROR);
teacherExpect(
    ($teacherTypeMapping['common']['core_content_item_id'] ?? '') === 'id'
    && ($teacherTypeMapping['common']['core_body'] ?? '') === 'bio'
    && ($teacherTypeMapping['common']['core_access'] ?? '') === 'access',
    'CORE-09: installer registers native teacher UCM mappings required by Joomla tag persistence'
);
$tagModel = $app->bootComponent('com_tags')->getMVCFactory()
    ->createModel('Tag', 'Administrator', ['ignore_request' => true]);
teacherExpect($tagModel->save([
    'id' => 0, 'parent_id' => 1, 'title' => 'CI teacher tag',
    'alias' => 'ci-teacher-tag', 'published' => 1, 'access' => 1,
    'language' => '*', 'description' => '',
]), 'CORE-09: create published native Joomla tag');
$tagId = (int) $tagModel->getState('tag.id');
teacherExpect($tagId > 1, 'CORE-09: native tag has a persisted identity');
$taggedTeacher = array_replace($teacherBase, [
    'id' => 0, 'revision' => 1, 'title' => 'CI tagged teacher',
    'alias' => 'ci-tagged-teacher', 'state' => 0, 'user_id' => '',
    'tags' => [$tagId],
]);
teacherExpect($teacherModel->save($taggedTeacher), 'CORE-09: teacher with native tag saved');
$taggedId = (int) $teacherModel->getState('teacher.id');
$tagAssociations = static function () use ($db, $taggedId): array {
    $q = $db->createQuery()->select('tag_id')->from('#__contentitem_tag_map')
        ->where('type_alias = ' . $db->quote('com_jlms.teacher'))
        ->where('content_item_id = ' . $taggedId);
    return array_map('intval', $db->setQuery($q)->loadColumn());
};
teacherExpect($tagAssociations() === [$tagId], 'CORE-09: Joomla persists tag association by teacher item ID');
// Forged tag assignments from an authenticated learner may not change the
// tag set, teacher revision or editorial authorization boundary.
$taggedBefore = (int) $db->setQuery('SELECT revision FROM #__jlms_teachers WHERE id=' . $taggedId)
    ->loadResult();
$teacherModel->setCurrentUser($learner);
teacherExpect(
    !$teacherModel->save(array_replace($taggedTeacher, [
        'id' => $taggedId, 'revision' => $taggedBefore, 'tags' => [],
    ])),
    'CORE-04/09: learner cannot forge a native Joomla teacher tag edit'
);
// CORE-04: a forged edit cannot smuggle administrative-only fields through
// a profile update. Check durable state as well as the return value, so an
// eventual post-save tag hook cannot silently mutate Joomla associations.
$protectedTeacherBefore = $db->setQuery(
    'SELECT revision,user_id,catid,state,access,language,params_json FROM #__jlms_teachers WHERE id='
    . $taggedId
)->loadAssoc();
$forgedTeacherFields = [
    ['user_id' => (int) $learner->id],
    ['catid' => $teacherCategory],
    ['state' => 1],
    ['access' => 2],
    ['language' => 'it-IT'],
    ['tags' => []],
];
foreach ($forgedTeacherFields as $forgedFields) {
    teacherExpect(
        !$teacherModel->save(array_replace($taggedTeacher, [
            'id' => $taggedId, 'revision' => $taggedBefore,
        ], $forgedFields)),
        'CORE-04: learner cannot forge teacher administrative fields'
    );
    teacherExpect(
        $db->setQuery(
            'SELECT revision,user_id,catid,state,access,language,params_json FROM #__jlms_teachers WHERE id='
            . $taggedId
        )->loadAssoc() === $protectedTeacherBefore && $tagAssociations() === [$tagId],
        'CORE-04: rejected teacher field edit preserves profile and native tag associations'
    );
}
$teacherModel->setCurrentUser($actor);
teacherExpect(
    $tagAssociations() === [$tagId]
    && (int) $db->setQuery('SELECT revision FROM #__jlms_teachers WHERE id=' . $taggedId)->loadResult()
        === $taggedBefore,
    'CORE-04/09: denied tag edit leaves associations and revision unchanged'
);
$taggedRecord = $teacherModel->getItem($taggedId);
teacherExpect(
    $taggedRecord->tags instanceof \Joomla\CMS\Helper\TagsHelper
    && $taggedRecord->tags->getTagIds($taggedId, 'com_jlms.teacher') === (string) $tagId,
    'CORE-09: editing teacher reloads the saved Joomla tag'
);
teacherExpect($teacherModel->save(array_replace($taggedTeacher, [
    'id' => $taggedId, 'revision' => 1, 'tags' => [],
])), 'CORE-09: native teacher tag can be removed');
teacherExpect($tagAssociations() === [], 'CORE-09: removed tag association leaves no stale Joomla mapping');

// CORE-16: teacher content history snapshots the persisted Joomla tag set and
// restores it alongside editorial fields without touching assigned course versions.
$publicRevision = (int) $db->setQuery('SELECT revision FROM #__jlms_teachers WHERE id=' . $public)->loadResult();
teacherExpect($teacherModel->save(array_replace($teacherBase, [
    'id' => $public,
    'revision' => $publicRevision,
    'title' => 'Original public teacher',
    'tags' => [$tagId],
])), 'CORE-16: assigned teacher snapshot captures native tags');
$teacherHistoryId = (int) $db->setQuery(
    $db->createQuery()->select('version_id')->from('#__history')
        ->where('item_id=' . $db->quote('com_jlms.teacher.' . $public))
        ->order('version_id DESC')
)->loadResult();
$teacherHistoryData = json_decode((string) $db->setQuery(
    $db->createQuery()->select('version_data')->from('#__history')->where('version_id=' . $teacherHistoryId)
)->loadResult(), true, 64, JSON_THROW_ON_ERROR);
teacherExpect(
    $teacherHistoryId > 0 && ($teacherHistoryData['tags'] ?? null) === [$tagId],
    'CORE-16: teacher history stores the actual persisted native tag IDs'
);
$publicRevision = (int) $db->setQuery('SELECT revision FROM #__jlms_teachers WHERE id=' . $public)->loadResult();
teacherExpect($teacherModel->save(array_replace($teacherBase, [
    'id' => $public,
    'revision' => $publicRevision,
    'title' => 'Changed public teacher',
    'tags' => [],
])), 'CORE-16: teacher edit records removal of native tags');
$latestTeacherHistory = json_decode((string) $db->setQuery(
    $db->createQuery()->select('version_data')->from('#__history')
        ->where('item_id=' . $db->quote('com_jlms.teacher.' . $public))
        ->order('version_id DESC')
)->loadResult(), true, 64, JSON_THROW_ON_ERROR);
teacherExpect(
    ($latestTeacherHistory['tags'] ?? null) === [],
    'CORE-16: teacher history distinguishes an empty tag set from missing data'
);
teacherExpect(
    (bool) $teacherModel->loadHistory($teacherHistoryId),
    'CORE-16: teacher history restore succeeds'
);
$restoredTeacher = $db->setQuery('SELECT title FROM #__jlms_teachers WHERE id=' . $public)->loadObject();
$restoredTeacherTags = array_map('intval', $db->setQuery(
    $db->createQuery()->select('tag_id')->from('#__contentitem_tag_map')
        ->where('type_alias=' . $db->quote('com_jlms.teacher'))->where('content_item_id=' . $public)
)->loadColumn());
teacherExpect(
    $restoredTeacher && $restoredTeacher->title === 'Original public teacher'
        && $restoredTeacherTags === [$tagId],
    'CORE-16: restore reinstates teacher fields and native Joomla tags'
);
teacherExpect(
    $db->setQuery('SELECT presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id='
        . $version)->loadAssoc() === $beforeSnapshot,
    'CORE-16: teacher history restore leaves the assigned course version immutable'
);
$publicRevision = (int) $db->setQuery('SELECT revision FROM #__jlms_teachers WHERE id=' . $public)->loadResult();
teacherExpect($teacherModel->save(array_replace($teacherBase, [
    'id' => $public, 'revision' => $publicRevision, 'title' => 'Updated public teacher', 'tags' => [],
])), 'CORE-16: fixture teacher tags can be removed after history restore');

file_put_contents($fixture, json_encode(['course' => $course,'version' => $version,'ids'
    => [$public,$private],'public' => $public,'private' => $private,'teacher_category'
        => $teacherCategory,'snapshot' => $snapshot], JSON_THROW_ON_ERROR));
echo json_encode(['teacher_checks' => $checks,'status' => 'passed']) . PHP_EOL;
