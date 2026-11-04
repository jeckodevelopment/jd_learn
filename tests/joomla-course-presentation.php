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
function presentationExpect(bool $ok, string $message): void {
    global $checks;
    if (!$ok) { throw new RuntimeException($message); }
    $checks++;
}
$fixture = dirname($root) . '/course-presentation-fixture.json';
if (($argv[2] ?? '') === 'retained') {
    $f = json_decode(file_get_contents($fixture), true, 32, JSON_THROW_ON_ERROR);
    $q = $db->createQuery()->select('presentation_json,structure_hash,published_at')->from('#__jlms_course_versions')
        ->where('id = :id')->bind(':id', $f['version'], \Joomla\Database\ParameterType::INTEGER);
    $v = $db->setQuery($q)->loadAssoc();
    presentationExpect($v === $f['snapshot'], 'Frozen presentation and hash survive upgrade');
    $details = $catalog->outline(new \Joomla\CMS\User\User(), 'en-GB', $f['course'])['presentation'];
    presentationExpect($details === $f['details'], 'Authorised public presentation survives upgrade');
    $q = $db->createQuery()->select('params_json')->from('#__jlms_courses')->where('id = :id')->bind(':id', $f['course'], \Joomla\Database\ParameterType::INTEGER);
    presentationExpect(json_decode($db->setQuery($q)->loadResult(), true)['unrelated_setting'] === 'retain-me', 'Unrelated settings survive upgrade');
    echo json_encode(['presentation_retention_checks' => $checks, 'status' => 'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
    exit;
}
$legacyUpgrade = json_decode(file_get_contents(dirname($root) . '/legacy-time-fixture.json'), true, 32, JSON_THROW_ON_ERROR);
$legacyVersion = (int) $legacyUpgrade['version_id'];
$preserved = $db->setQuery('SELECT presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id=' . $legacyVersion)->loadAssoc();
presentationExpect($preserved === $legacyUpgrade['presentation_before_upgrade'], 'Actual upgrade from 0.21 preserves stored presentation bytes, hash and timestamp');
$legacyDetails = $catalog->outline(new \Joomla\CMS\User\User(), 'en-GB', (int) $legacyUpgrade['course_id'])['presentation'];
presentationExpect($legacyDetails['description'] === '<p>Pre-upgrade 0.21 presentation</p>' && $legacyDetails['image'] === '', 'Existing published presentation renders with optional image absent after upgrade');
$base = ['id' => 0, 'title' => 'Presentation fixture', 'alias' => 'presentation-fixture', 'catid' => $catid,
    'language' => '*', 'catalog_access' => 1, 'content_access' => 2, 'enrollment_mode' => 'auto', 'summary' => 'Public summary', 'revision' => 1];
// Public fixture owned by this disposable site; never copy private lesson media.
$coverPath = 'images/jlms-ci-cover.png';
$coverBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=', true);
file_put_contents($root . '/' . $coverPath, $coverBytes);
$input = $base + ['presentation_image' => $coverPath . '#joomlaImage://local-images/jlms-ci-cover.png?width=1&height=1',
    'presentation_image_alt' => 'Course cover "example" <plain text>', 'presentation_image_decorative' => '0',
    'presentation_seo_title' => 'Published course SEO title', 'presentation_seo_description' => 'SEO description & public presentation',
    'presentation_description' => '<p>Extended <strong>presentation</strong></p><script>alert(1)</script><iframe src="https://example.invalid"></iframe><img src="https://example.invalid/pixel"><a href="javascript:alert(2)" onclick="alert(3)">Safe link text</a>',
    'presentation_objectives' => 'Learn <em>as plain text</em>', 'presentation_audience' => "Learners\nNewcomers", 'presentation_prerequisite_text' => 'Read the introduction',
    'presentation_difficulty' => 'beginner', 'presentation_estimated_minutes' => '45', 'params_json' => '{"injected":true}', 'current_version_id' => 999999, 'state' => 1];
$model = $factory->createModel('Course', 'Administrator', ['ignore_request' => true]);
presentationExpect($model->save($input), 'Native model saves typed presentation');
$course = (int) $model->getState('course.id');
$item = $model->getItem($course);
$data = json_decode($item->params_json, true, 32, JSON_THROW_ON_ERROR);
presentationExpect(!isset($data['injected']) && (int) $item->state === 0 && $item->current_version_id === null, 'Raw JSON and publication fields cannot be mass-assigned');
$details = CoursePresentation::details($item->params_json);
presentationExpect($details['estimated_minutes'] === 45 && $details['difficulty'] === 'beginner', 'Typed duration and allowlisted difficulty persisted');
presentationExpect($details['audience'] === "Learners\nNewcomers" && $item->presentation_objectives === $input['presentation_objectives'], 'Plain fields round-trip without HTML execution');
presentationExpect(str_contains($details['description'], '<strong>presentation</strong>'), 'Safe HTML formatting retained');
presentationExpect(!preg_match('/<script|<iframe|<img|javascript:|onclick=/i', $details['description']), 'Active markup, tracking images and unsafe attributes removed even for super user');
presentationExpect($details['image'] === $coverPath && $details['image_decorative'] === false, 'Native media adapter suffix normalised to public relative path');
presentationExpect($details['image_alt'] === $input['presentation_image_alt'] && $details['seo_title'] === $input['presentation_seo_title'], 'Alternative text and SEO values persist');
$decorative = CoursePresentation::merge(['presentation_image_decorative' => '1'], $item->params_json, static fn (string $html): string => $html);
presentationExpect(CoursePresentation::details($decorative)['image_alt'] === '' && CoursePresentation::details($decorative)['image_decorative'], 'Explicit decorative image has empty alternative text');

$data['unrelated_setting'] = 'retain-me';
$settingsFixture = (object) ['id' => $course, 'params_json' => json_encode($data, JSON_THROW_ON_ERROR)];
$db->updateObject('#__jlms_courses', $settingsFixture, 'id');
$update = array_replace($base, ['id' => $course, 'title' => 'Presentation fixture updated', 'revision' => 1]);
presentationExpect($model->save($update), 'Legacy client can save without new fields');
$item = $model->getItem($course);
presentationExpect(CoursePresentation::details($item->params_json) === $details && json_decode($item->params_json, true)['unrelated_setting'] === 'retain-me', 'Omitted fields and unrelated settings preserved');
$update['revision'] = 2;
presentationExpect($model->save($update + ['presentation_audience' => 'Updated audience']), 'Partial presentation update accepted');
$item = $model->getItem($course);
presentationExpect($item->presentation_audience === 'Updated audience' && $item->presentation_estimated_minutes === 45, 'Partial update retains other fields');
$before = $item->params_json;
$invalid = [
    ['presentation_estimated_minutes' => '0'], ['presentation_estimated_minutes' => '-1'], ['presentation_estimated_minutes' => '1.5'],
    ['presentation_estimated_minutes' => '1e3'], ['presentation_estimated_minutes' => (string) PHP_INT_MAX . '0'],
    ['presentation_estimated_minutes' => ['45']], ['presentation_difficulty' => 'expert'], ['presentation_difficulty' => ['beginner']],
    ['presentation_description' => ['<p>array</p>']], ['presentation_description' => str_repeat('a', 1048577)],
    ['presentation_image_alt' => ''], ['presentation_image_alt' => str_repeat('a', 501)],
    ['presentation_image' => '../configuration.php'], ['presentation_image' => '/images/jlms-ci-cover.png'],
    ['presentation_image' => 'https://example.invalid/cover.png'], ['presentation_image' => '//example.invalid/cover.png'],
    ['presentation_image' => 'data:image/png;base64,AAAA'], ['presentation_image' => 'images/%2e%2e/configuration.png'],
    ['presentation_image' => 'images/../configuration.png'], ['presentation_image' => 'images/cover.svg'],
    ['presentation_image' => 'images/missing.png'], ['presentation_image' => ['images/cover.png']],
    ['presentation_image_decorative' => 'yes'], ['presentation_image_decorative' => ['1']],
    ['presentation_seo_title' => str_repeat('a', 256)], ['presentation_seo_description' => str_repeat('a', 501)],
    ['presentation_seo_title' => '<script>title</script>'], ['presentation_seo_description' => "line\nheader"],
    ['presentation_objectives' => str_repeat('a', 65536)], ['presentation_audience' => "\xC3\x28"],
];
foreach ($invalid as $values) {
    presentationExpect(!$model->save(array_replace($update, ['revision' => 3], $values)), 'Invalid presentation rejected');
    $fresh = $model->getItem($course);
    presentationExpect((int) $fresh->revision === 3 && $fresh->params_json === $before, 'Invalid input preserves persisted fields and revision');
}
$guest = new \Joomla\CMS\User\User();
$model->setCurrentUser($guest);
presentationExpect(!$model->save(array_replace($update, ['revision' => 3, 'presentation_description' => '<p>Unauthorised change</p>'])), 'Guest cannot edit presentation');
$model->setCurrentUser($actor);
presentationExpect(!$model->save($update + ['presentation_description' => '<p>Stale change</p>']), 'Stale revision cannot overwrite presentation');
// Public image validation must not treat a symlink as access to private storage.
$outside = dirname($root) . '/private-presentation.png';
file_put_contents($outside, $coverBytes);
$link = $root . '/images/jlms-ci-symlink.png';
symlink($outside, $link);
presentationExpect(!$model->save(array_replace($update, ['revision' => 3, 'presentation_image' => 'images/jlms-ci-symlink.png'])), 'Symlink escaping the public media root rejected');
unlink($link); unlink($outside);
$fake = $root . '/images/jlms-ci-fake.png';
file_put_contents($fake, '<html>not an image</html>');
presentationExpect(!$model->save(array_replace($update, ['revision' => 3, 'presentation_image' => 'images/jlms-ci-fake.png'])), 'Extension alone cannot qualify an image');
unlink($fake);
$version = $authoring->createDraft($actor, $course, 3);
$q = $db->createQuery()->select('revision')->from('#__jlms_course_versions')->where('id = :id')->bind(':id', $version, \Joomla\Database\ParameterType::INTEGER);
$revision = static fn (): int => (int) $db->setQuery($q)->loadResult();
$module = $authoring->saveModule($actor, ['course_id' => $course, 'version_id' => $version, 'expected_version_revision' => $revision(), 'title' => 'Module']);
$authoring->saveLesson($actor, ['course_id' => $course, 'version_id' => $version, 'expected_version_revision' => $revision(), 'module_id' => $module, 'title' => 'Private lesson', 'kind' => 'text', 'required' => '1', 'body' => '<p>PRESENTATION_PRIVATE_LESSON_BODY</p>']);
// Presentation edited after draft creation must be frozen from the latest saved course data.
$item = $model->getItem($course);
presentationExpect($model->save(array_replace($update, ['revision' => (int) $item->revision, 'presentation_objectives' => 'Latest objectives before publication'])), 'Presentation remains editable while programme is a draft');
$item = $model->getItem($course);
unlink($root . '/' . $coverPath);
try { CoursePresentation::snapshot($item); throw new RuntimeException('Missing cover accepted at publication'); }
catch (InvalidArgumentException) { $checks++; }
file_put_contents($root . '/' . $coverPath, $coverBytes);
// New publication requires a meaningful description, including on drafts created by older clients.
$originalParams = $item->params_json;
$beforeVersionCheck = $db->setQuery('SELECT status,revision,presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id=' . $version)->loadAssoc();
$beforeDescriptionCheck = $db->setQuery('SELECT state,current_version_id,revision FROM #__jlms_courses WHERE id=' . $course)->loadAssoc();
foreach ([null, '', "<p>&nbsp; \t<br></p>", '<p>&#8203;</p>'] as $description) {
    $params = json_decode($originalParams, true, 32, JSON_THROW_ON_ERROR);
    if ($description === null) { unset($params['presentation']); }
    else { $params['presentation']['description'] = $description; }
    $missingDescription = (object) ['id' => $course, 'params_json' => json_encode($params, JSON_THROW_ON_ERROR)];
    $db->updateObject('#__jlms_courses', $missingDescription, 'id');
    try {
        $choices = $publication->transitions($actor, $course);
        $publication->transition($actor, $course, $version, (int) $item->revision, $revision(), (int) $choices[0]->id);
        throw new RuntimeException('Publication accepted a missing promotional description');
    } catch (DomainException $error) {
        presentationExpect($error->getMessage() === 'COM_JLMS_COURSE_DESCRIPTION_REQUIRED', 'Missing description has a specific error');
    }
    presentationExpect($db->setQuery('SELECT state,current_version_id,revision FROM #__jlms_courses WHERE id=' . $course)->loadAssoc() === $beforeDescriptionCheck, 'Denied publication leaves course and revision unchanged');
    presentationExpect($db->setQuery('SELECT status,revision,presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id=' . $version)->loadAssoc() === $beforeVersionCheck, 'Denied publication leaves the draft version unchanged');
    presentationExpect((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_editions WHERE course_version_id=' . $version)->loadResult() === 0, 'Denied publication creates no edition');
}
$restoredDescription = (object) ['id' => $course, 'params_json' => $originalParams];
$db->updateObject('#__jlms_courses', $restoredDescription, 'id');
$choices = $publication->transitions($actor, $course);
$publication->transition($actor, $course, $version, (int) $item->revision, $revision(), (int) $choices[0]->id);
$outline = $catalog->outline($guest, 'en-GB', $course);
presentationExpect($outline['presentation']['objectives'] === 'Latest objectives before publication', 'Publication freezes the latest presentation, not the initial draft copy');
presentationExpect(!str_contains(json_encode($outline), 'PRESENTATION_PRIVATE_LESSON_BODY') && !str_contains(json_encode($outline), 'retain-me'), 'Public projection excludes private lesson content and unrelated params');
$baseline = $db->setQuery('SELECT presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id=' . $version)->loadAssoc();
presentationExpect($model->save(array_replace($update, ['revision' => (int) $model->getItem($course)->revision, 'presentation_objectives' => 'Published editorial correction'])), 'Published editorial metadata can be updated through the normal editor');
presentationExpect($catalog->outline($guest, 'en-GB', $course)['presentation']['objectives'] === 'Published editorial correction', 'Current public course shows the editorial correction');
presentationExpect($db->setQuery('SELECT presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id=' . $version)->loadAssoc() === $baseline, 'Denied mutation leaves frozen history unchanged');
foreach ([0, 1] as $expectedState) {
    $choices = $publication->transitions($actor, $course);
    $publication->transition($actor, $course, $version, (int) $model->getItem($course)->revision, $revision(), (int) $choices[0]->id);
    presentationExpect($db->setQuery('SELECT presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id=' . $version)->loadAssoc() === $baseline, 'Withdrawal/republication retains presentation and hash');
}
$oldPresentation = CoursePresentation::fromSnapshot('{"_schema":1,"summary":"Legacy","details":{"_schema":1,"description":"<p>Existing 0.21 presentation</p>"}}');
presentationExpect($oldPresentation['image'] === '' && !$oldPresentation['image_decorative'] && $oldPresentation['seo_title'] === '', '0.21 snapshots gain only empty optional defaults without database mutation');
$legacy = CoursePresentation::fromSnapshot('{"_schema":1,"summary":"Legacy public summary"}');
presentationExpect($legacy === [], 'Legacy published snapshots do not require backfilling');
file_put_contents($fixture, json_encode(['course' => $course, 'version' => $version, 'details' => $outline['presentation'], 'snapshot' => $baseline], JSON_THROW_ON_ERROR));
echo json_encode(['course_presentation_checks' => $checks, 'status' => 'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
