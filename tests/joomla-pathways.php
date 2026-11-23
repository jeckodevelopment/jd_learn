<?php

declare(strict_types=1);

/** Native pathway authoring, visibility and aggregate-progress integration test. */
$root = realpath($argv[1] ?? '');
$retained = ($argv[2] ?? '') === 'retained';
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new RuntimeException('Disposable Joomla site required.');
}
$_SERVER += ['HTTP_HOST' => 'localhost','SERVER_NAME' => 'localhost','SERVER_PORT' => '80','REQUEST_METHOD' => 'GET',
    'REQUEST_URI' => '/administrator/index.php?option=com_jlms','SCRIPT_NAME' => '/administrator/index.php',
    'PHP_SELF' => '/administrator/index.php','SCRIPT_FILENAME' => $root . '/administrator/index.php'];
define('_JEXEC', 1);
define('JPATH_BASE', $root . '/administrator');
require $root . '/administrator/includes/defines.php';
require $root . '/administrator/includes/framework.php';
define('JPATH_COMPONENT', JPATH_ADMINISTRATOR . '/components/com_jlms');
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_COMPONENT);
define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/com_jlms');

use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\UserFactoryInterface;

$container = Factory::getContainer();
$container->alias('session.web', 'session.web.administrator')->alias('session', 'session.web.administrator')
    ->alias(Session::class, 'session.web.administrator')->alias(SessionInterface::class, 'session.web.administrator');
$app = $container->get(AdministratorApplication::class);
Factory::$application = $app;
$app->createExtensionNamespaceMap();
$db = $container->get(DatabaseInterface::class);
$operatorId = (int)$db->setQuery("SELECT id FROM #__users WHERE username='cioperator'")->loadResult();
if (!$operatorId) {
    throw new RuntimeException('CI operator missing.');
}
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserById($operatorId));
$app->getSession()->set('user', $app->getIdentity());
$component = $app->bootComponent('com_jlms');
$service = $component->getPathwayService();
$fixture = dirname($root) . '/pathways-fixture.json';
$check = static function(bool $ok,string $message): void { if (!$ok) throw new RuntimeException($message);

};

$lifecycleSnapshotFile = dirname($root) . '/pathway-lifecycle-snapshot.json';
$readLifecycleSnapshot = static function(int $pathId,int $courseId) use ($db): array {
    return [
        'pathway' => $db->setQuery('SELECT id,title,description,state,access,language,catid FROM #__jlms_pathways WHERE id=' . (int)$pathId)->loadAssoc(),
        'course_links' => $db->setQuery('SELECT course_id,ordering FROM #__jlms_pathway_courses WHERE pathway_id=' . (int)$pathId . ' ORDER BY ordering')->loadAssocList(),
    ];
};


if (($argv[2] ?? '') === 'acl-state') {
    $acl = json_decode((string)file_get_contents(dirname($root) . '/pathway-acl-fixture.json'), true, 16, JSON_THROW_ON_ERROR);
    $states = [];
    foreach (['allowed','denied'] as $key) {
        $states[$key] = (int)$db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . (int)$acl[$key])->loadResult();
    }
    echo json_encode($states, JSON_THROW_ON_ERROR) . PHP_EOL;
    return;
}
if ($retained) {
    $data = json_decode((string)file_get_contents($fixture), true, 16, JSON_THROW_ON_ERROR);
    $path = $db->setQuery('SELECT id FROM #__jlms_pathways WHERE id=' . (int)$data['pathway'])->loadResult();
    $check((int)$path === (int)$data['pathway'], 'pathway record retained through package update');

    $snapshot = json_decode((string)file_get_contents($lifecycleSnapshotFile), true, 16, JSON_THROW_ON_ERROR);
    $check($readLifecycleSnapshot((int)$data['pathway'], (int)$data['course']) === $snapshot,
        'LCM-05 persisted pathway state, access metadata and ordered associations survive package upgrade');

    $rows = $db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . (int)$path . ' ORDER BY ordering')->loadColumn();
    $check(array_map('intval', $rows) === [(int)$data['course'],(int)$data['hidden_course']], 'ordered course associations retained through package update');
    $student = new Joomla\CMS\User\User((int)$data['student']);
    $detail = $service->detail($student, 'en-GB', (int)$path);
    $check(count($detail['courses']) === 2 && $detail['courses'][0]['id'] === (int)$data['course']
        && $detail['courses'][1]['id'] === null && $detail['courses'][1]['title'] === '',
        'public pathway service preserves visible course and anonymous hidden placeholder after update');
    echo json_encode(['pathway_retention_checks' => 4,'status' => 'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
    return;
}

$categoryModel = $app->bootComponent('com_categories')->getMVCFactory()->createModel('Category', 'Administrator', ['ignore_request' => true]);
$categoryModel->setState('category.extension', 'com_jlms.pathway');
$categoryModel->setState('category.component', 'com_jlms');
$categoryModel->setState('category.section', 'pathway');
$category = ['id' => 0,'parent_id' => 1,'extension' => 'com_jlms.pathway','title' => 'CI pathway category','alias' => 'ci-pathway-category',
    'published' => 1,'language' => '*','access' => 1,'description' => ''];
if (!$categoryModel->save($category)) {
    throw new RuntimeException('Native pathway category creation failed: ' . $categoryModel->getError());
}
$categoryId = (int)$categoryModel->getState('category.id');
$courseId = (int)$db->setQuery("SELECT id FROM #__jlms_courses WHERE alias='publication-test'")->loadResult();
if (!$courseId) {
    throw new RuntimeException('Published catalog course fixture missing.');
}
$pathModel = $component->getMVCFactory()->createModel('Pathway', 'Administrator', ['ignore_request' => true]);
// SECDEV-03: hostile editorial HTML and protected table columns must not escape
// the native pathway editor's HTML filtering and explicit write allowlist.
$payload = ['id' => 0,'revision' => 1,'title' => 'CI learning pathway','alias' => 'ci-learning-pathway',
    'description' => '<p>Pathway summary</p><script>alert(1)</script><iframe src="https://example.invalid"></iframe><a href="javascript:alert(2)" onclick="alert(3)">read</a>',
    'catid' => $categoryId,'access' => 1,'language' => '*','state' => 1,
    'courses' => [['course_id' => $courseId,'ordering' => 10]],
    'params_json' => '{"injected":true}','created_by' => 999999,'checked_out' => 999999];
$check((bool)$pathModel->save($payload), 'native pathway model saves pathway and course order: ' . $pathModel->getError());
$pathwayId = (int)$db->setQuery("SELECT id FROM #__jlms_pathways WHERE alias='ci-learning-pathway'")->loadResult();
$securityRow = $db->setQuery('SELECT description,params_json,created_by,checked_out FROM #__jlms_pathways WHERE id=' . (int)$pathwayId)->loadObject();
$check($securityRow && str_contains((string)$securityRow->description, '<p>Pathway summary</p>')
    && !preg_match('~<(?:script|iframe)\\b|\\bonclick\\s*=|javascript:~i', (string)$securityRow->description),
    'SECDEV-03: pathway description filters executable markup and URL handlers');
$check(!str_contains((string)$securityRow->params_json, 'injected')
    && (int)$securityRow->created_by === (int)$app->getIdentity()->id
    && (int)$securityRow->checked_out !== 999999,
    'SECDEV-03: pathway ignores raw params, author identity and checkout fields');
// SECDEV-03: malformed types must fail without changing persisted authoring
// fields, checkout, revision or the existing course association.
$beforeInvalid = $db->setQuery('SELECT revision,params_json,description FROM #__jlms_pathways WHERE id=' . (int)$pathwayId)->loadAssoc();
foreach ([
    ['cover_image' => ['images/cover.png']],
    ['cover_alt' => ['hostile']],
    ['cover_alt' => new \stdClass()],
    ['cover_alt' => "\xC3\x28"],
] as $invalidArtwork) {
    $check(!$pathModel->save(['id' => $pathwayId,'revision' => 1] + $invalidArtwork),
        'SECDEV-03: pathway refuses malformed artwork input');
    $check($db->setQuery('SELECT revision,params_json,description FROM #__jlms_pathways WHERE id=' . (int)$pathwayId)->loadAssoc() === $beforeInvalid,
        'SECDEV-03: invalid artwork leaves persisted pathway metadata and revision unchanged');
}
$stored = $db->setQuery('SELECT course_id,ordering FROM #__jlms_pathway_courses WHERE pathway_id=' . (int)$pathwayId)->loadObject();
$check($pathwayId > 0 && (int)$stored->course_id === $courseId && (int)$stored->ordering === 0, 'native authoring normalizes and persists ordering');
// SECDEV-03: submitted association IDs are literal integer identifiers,
// never SQL syntax; rejected updates may not modify the existing links.
$beforeAssociationInjection = $db->setQuery(
    'SELECT course_id,ordering FROM #__jlms_pathway_courses WHERE pathway_id=' . (int)$pathwayId . ' ORDER BY ordering'
)->loadAssocList();
foreach (["1 OR 1=1 --", "1; DROP TABLE courses; --", "1 UNION SELECT 1"] as $sqlLikeCourseId) {
    $check(!$pathModel->save([
        'id' => $pathwayId, 'revision' => 1,
        'courses' => [['course_id' => $sqlLikeCourseId,'ordering' => 0]],
    ]), 'SECDEV-03: reject SQL-like course association identifier');
    $check($db->setQuery(
        'SELECT course_id,ordering FROM #__jlms_pathway_courses WHERE pathway_id=' . (int)$pathwayId . ' ORDER BY ordering'
    )->loadAssocList() === $beforeAssociationInjection,
        'SECDEV-03: rejected association input preserves persisted links');
}

$operator = $app->getIdentity();
$student = $component->getLearningService()->start($operator, 'en-GB', $courseId, str_repeat('p', 40));
$detail = $service->detail($operator, 'en-GB', $pathwayId);
$check($detail['progress'] === 0 && $detail['required_courses'] === 1, 'enrolled course with defined rules permits a zero percent pathway aggregate');
$hiddenCourseId = (int)$db->setQuery("SELECT id FROM #__jlms_courses WHERE alias='test-course'")->loadResult();
if (!$hiddenCourseId) {
    throw new RuntimeException('Draft course fixture missing.');
}
$check((bool)$pathModel->save(['id' => $pathwayId,'revision' => 1,'title' => 'CI learning pathway','alias' => 'ci-learning-pathway',
    'description' => '<p>Pathway summary</p>','catid' => $categoryId,'access' => 1,'language' => '*','state' => 1,
    'courses' => [['course_id' => $courseId,'ordering' => 10],['course_id' => $hiddenCourseId,'ordering' => 20]]]), 'second course association saves');
$detail = $service->detail($operator, 'en-GB', $pathwayId);
$check(count($detail['courses']) === 2 && $detail['courses'][0]['id'] === $courseId, 'visible course is first in its authored order');
$check($detail['courses'][1]['id'] === null && $detail['courses'][1]['title'] === '', 'draft course becomes an unidentified placeholder');

// LCM-05: a published course with disabled delivery remains catalog-visible but
// must not be presented as a usable classroom inside a learning pathway.
$initialDelivery = (int)$db->setQuery('SELECT delivery_enabled FROM #__jlms_courses WHERE id=' . (int)$courseId)->loadResult();
$initialEnrollments = (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . (int)$courseId)->loadResult();
try {
    $db->setQuery('UPDATE #__jlms_courses SET delivery_enabled=0 WHERE id=' . (int)$courseId)->execute();
    $suspendedPathway = $service->detail($operator, 'en-GB', $pathwayId);
    $check((int)$suspendedPathway['courses'][0]['id'] === $courseId
        && $suspendedPathway['courses'][0]['access'] === 'unavailable'
        && $suspendedPathway['courses'][0]['delivery_suspended'] === true
        && $suspendedPathway['courses'][0]['learning_state'] !== 'unavailable',
        'published but delivery-suspended course retains catalog and learning status without promising access');
    $check((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . (int)$courseId)->loadResult() === $initialEnrollments,
        'delivery suspension is read-only for pathway enrollment history');
} finally {
    $db->setQuery('UPDATE #__jlms_courses SET delivery_enabled=' . (int)$initialDelivery . ' WHERE id=' . (int)$courseId)->execute();
}
$deliveryRestored = $service->detail($operator, 'en-GB', $pathwayId);
$check($deliveryRestored['courses'][0]['access'] === 'available', 'restored delivery returns pathway course to its previous availability');
$check($deliveryRestored['courses'][0]['delivery_suspended'] === false, 're-enabled delivery clears suspension display state');
$check(empty($service->detail(new Joomla\CMS\User\User(), 'en-GB', $pathwayId)['courses'][0]['delivery_suspended']), 'guest without content rights cannot inspect course delivery suspension');

$check($detail['progress'] === null && $detail['required_courses'] === 2, 'unavailable course without evidence suppresses ungrounded aggregate');
$guest = new Joomla\CMS\User\User();
$guestDetail = $service->detail($guest, 'en-GB', $pathwayId);
$check(count($guestDetail['courses']) === 2 && $guestDetail['courses'][0]['access'] === 'restricted', 'catalog-visible but content-restricted course is labeled restricted');
$check($guestDetail['courses'][1]['id'] === null && $guestDetail['courses'][1]['title'] === '', 'guest cannot identify the draft course');
$check($guestDetail['progress'] === null && $guestDetail['has_unavailable_courses'] === true, 'guest has no personal percentage while unavailable course contributes zero internally');
$before = (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE user_id=' . (int)$operator->id . ' AND course_id=' . (int)$courseId)->loadResult();
$again = $service->detail($guest, 'en-GB', $pathwayId);
$after = (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE user_id=' . (int)$operator->id . ' AND course_id=' . (int)$courseId)->loadResult();
$check($before === $after, 'reading a pathway never changes enrollment or access');
$rejected = ['id' => 0,'revision' => 1,'title' => 'Rejected pathway','alias' => 'rejected-ci-pathway','description' => '','catid' => $categoryId,
    'access' => 1,'language' => '*','state' => 1,'courses' => [['course_id' => $courseId,'ordering' => 0],['course_id' => $courseId,'ordering' => 1]]];
$check(!$pathModel->save($rejected), 'duplicate course association is rejected');
$check(!(int)$db->setQuery("SELECT COUNT(*) FROM #__assets WHERE name LIKE 'com_jlms.pathway.tmp.%'")->loadResult(),
    'failed pathway save removes its prepared Joomla asset');
$emptyPayload = ['id' => 0,'revision' => 1,'title' => 'Empty CI pathway','alias' => 'empty-ci-pathway','description' => '','catid' => $categoryId,
    'access' => 1,'language' => '*','state' => 1,'courses' => []];
$check((bool)$pathModel->save($emptyPayload), 'empty pathway can be authored: ' . $pathModel->getError());
$emptyId = (int)$db->setQuery("SELECT id FROM #__jlms_pathways WHERE alias='empty-ci-pathway'")->loadResult();
// LCM-03: one course may belong to multiple independent pathways. Changes to
// the first pathway must not alter the second pathway or their shared course.
$siblingPayload = ['id' => 0,'revision' => 1,'title' => 'Shared-course CI pathway',
    'alias' => 'shared-course-ci-pathway','description' => '','catid' => $categoryId,
    'access' => 1,'language' => '*','state' => 1,
    'courses' => [['course_id' => $courseId,'ordering' => 0]]];
$check((bool)$pathModel->save($siblingPayload),
    'a second pathway can reference an existing course without transferring ownership');
$siblingId = (int)$db->setQuery("SELECT id FROM #__jlms_pathways WHERE alias='shared-course-ci-pathway'")->loadResult();
$check($siblingId > 0 && $siblingId !== $pathwayId && $siblingId !== $emptyId,
    'independent shared-course pathway has its own record');
$assertSiblingUnchanged = static function (string $phase) use ($db,$check,$siblingId,$courseId): void {
    $row = $db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . (int)$siblingId)->loadResult();
    $links = $db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . (int)$siblingId)->loadColumn();
    $check((int)$row === 1 && array_map('intval', $links) === [$courseId],
        'LCM-03 shared-course pathway stays published and linked after ' . $phase);
};
// LCM-05: granular native ACL applies to each selected pathway, not just
// component-level management. Reuse the real delegated Joomla editor group.
// Dedicated pathway survives the other lifecycle fixtures and remains available
// for the later HTTP test (which runs after this CLI integration fixture).
$check($pathModel->save(array_replace($emptyPayload, [
    'title' => 'ACL allowed CI pathway','alias' => 'acl-allowed-ci-pathway'
])), 'LCM-05 dedicated ACL pathway created');
$aclAllowedId = (int)$db->setQuery("SELECT id FROM #__jlms_pathways WHERE alias='acl-allowed-ci-pathway'")->loadResult();
$check($aclAllowedId > 0, 'dedicated ACL pathway persisted');

$delegateGroupModel = $app->bootComponent('com_users')->getMVCFactory()
    ->createModel('Group', 'Administrator', ['ignore_request' => true]);
$check($delegateGroupModel->save(['id' => 0,'parent_id' => 2,'title' => 'CI pathway lifecycle delegate']),
    'dedicated pathway ACL group created');
$delegateGroup = (int)$delegateGroupModel->getState('group.id');
$backendGroup = $delegateGroup;
$aclAsset = new \Joomla\CMS\Table\Asset($db);
$grantAsset = static function(string $name,array $actions,int $group,bool $allowed) use ($aclAsset,$check): void {
    $check($aclAsset->loadByName($name), 'LCM-05 Joomla asset exists: ' . $name);
    $rules = json_decode((string)$aclAsset->rules, true) ?: [];
    foreach ($actions as $action) {
        $rules[$action][(string)$group] = $allowed;
    }
    $aclAsset->rules = json_encode($rules, JSON_THROW_ON_ERROR);
    $check($aclAsset->store(), 'LCM-05 ACL fixture rule saved for ' . $name);
};
$grantAsset('root.1', ['core.login.admin'], $backendGroup, true);
$grantAsset('com_jlms', ['core.manage','jlms.pathways.manage'], $backendGroup, true);
$grantAsset('com_jlms.pathway.category.' . $categoryId, ['core.edit','core.edit.state'], $delegateGroup, true);
$grantAsset('com_jlms.pathway.' . $siblingId, ['core.edit.state'], $delegateGroup, false);
$delegateUser = new \Joomla\CMS\User\User();
$delegateData = [
    'name' => 'CI pathway manager', 'username' => 'cipathwaymanager',
    'email' => 'cipathwaymanager@example.invalid',
    'password' => 'CI-Pathway-Only-Password-2026','password2' => 'CI-Pathway-Only-Password-2026',
    'groups' => [2,$delegateGroup],'block' => 0,'activation' => '',
];
$check($delegateUser->bind($delegateData) && $delegateUser->save(), 'dedicated pathway delegate created');
$delegate = new \Joomla\CMS\User\User((int)$delegateUser->id);
$delegate->clearAccessRights();
$check($delegate->authorise('core.edit.state', 'com_jlms.pathway.' . $aclAllowedId)
    && !$delegate->authorise('core.edit.state', 'com_jlms.pathway.' . $siblingId),
    'delegated Joomla ACL grants first pathway and explicitly denies second');
$pathModel->setCurrentUser($delegate);
$check(!$pathModel->changeStates([$aclAllowedId,$siblingId], 2),
    'delegated mixed selection is rejected atomically on record-level ACL deny');
$check((int)$db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . (int)$aclAllowedId)->loadResult() === 1,
    'record-specific ACL denial rolls back a prior permitted pathway');
$check($pathModel->changeStates([$aclAllowedId], 2),
    'same delegate can archive the individually authorized pathway');
$check($pathModel->changeStates([$aclAllowedId], 0) && $pathModel->changeStates([$aclAllowedId], 1),
    'delegate restores and explicitly republishes only the permitted pathway');
$check((int)$db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . (int)$siblingId)->loadResult() === 1,
    'record-level denial preserves sibling state');
$pathModel->setCurrentUser($operator);
file_put_contents(dirname($root) . '/pathway-acl-fixture.json', json_encode([
    'allowed' => $aclAllowedId,'denied' => $siblingId,'user' => (int)$delegate->id,'username' => 'cipathwaymanager',
], JSON_THROW_ON_ERROR));
$assertSiblingUnchanged('authoring');

$emptyDetail = $service->detail($operator, 'en-GB', $emptyId);
$check($emptyDetail['progress'] === null && $emptyDetail['required_courses'] === 0, 'empty pathway has no completion percentage');
// LCM-03: archive hides the public pathway without mutating its course links,
// student enrollments or historical progress; restoring requires explicit publication.
$pathwayCourses = [['course_id' => $courseId,'ordering' => 10],['course_id' => $hiddenCourseId,'ordering' => 20]];
$pathwayBase = ['id' => $pathwayId,'title' => 'CI learning pathway','alias' => 'ci-learning-pathway',
    'description' => '<p>Pathway summary</p>','catid' => $categoryId,'access' => 1,'language' => '*',
    'courses' => $pathwayCourses];
$beforeRelations = $db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn();
$enrollmentsBeforeArchive = (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . $courseId)->loadResult();
// LCM-05: a pathway lifecycle change must not mutate its constituent courses.
$courseStatesBefore = $db->setQuery('SELECT id,state FROM #__jlms_courses WHERE id IN (' . $courseId . ',' . $hiddenCourseId . ') ORDER BY id')->loadAssocList();
$unsafePathway = array_replace($pathwayBase, [
    'description' => '<p>Safe <strong>pathway</strong> <script>alert(1)</script><iframe src="https://example.invalid"></iframe><a href="javascript:alert(2)" onclick="alert(3)">read</a></p>',
    'revision' => 2, 'state' => 2,
]);
$check((bool)$pathModel->save($unsafePathway),
    'archiving a published pathway succeeds: ' . $pathModel->getError());
$savedPathwayHtml = (string)$db->setQuery('SELECT description FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult();
$check(str_contains($savedPathwayHtml, '<strong>pathway</strong>')
    && !preg_match('~<(?:script|iframe)\\b|\\bonclick\\s*=|javascript:~i', $savedPathwayHtml),
    'public pathway HTML retains safe formatting and removes scripts, embeds and active URLs');
$check((int)$db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult() === 2,
    'archived pathway state is stored');
$archiveDenied = false;
try {
    $service->detail($operator, 'en-GB', $pathwayId);
} catch (\RuntimeException $error) {
    $archiveDenied = $error->getCode() === 404;
}
$check($archiveDenied, 'archived pathway cannot be read through the public detail endpoint');
$assertSiblingUnchanged('archive');
$archiveGuestDenied = false;
try {
    $service->detail($guest, 'en-GB', $pathwayId);
} catch (\RuntimeException $error) {
    $archiveGuestDenied = $error->getCode() === 404;
}
$check($archiveGuestDenied, 'archived pathway direct detail also denies anonymous visitors');
$check($db->setQuery('SELECT id,state FROM #__jlms_courses WHERE id IN (' . $courseId . ',' . $hiddenCourseId . ') ORDER BY id')->loadAssocList() === $courseStatesBefore,
    'archiving a pathway never changes the states of its constituent courses');
$check($db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn() === $beforeRelations,
    'archiving preserves pathway course associations');
$check((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . $courseId)->loadResult() === $enrollmentsBeforeArchive,
    'archiving a pathway does not change course enrollments');
$check((bool)$pathModel->save($pathwayBase + ['revision' => 3,'state' => 0]),
    'restoring archived pathway to unpublished succeeds: ' . $pathModel->getError());
$draftDenied = false;
try {
    $service->detail($operator, 'en-GB', $pathwayId);
} catch (\RuntimeException $error) {
    $draftDenied = $error->getCode() === 404;
}
$check($draftDenied, 'restoration does not automatically republish a pathway');
$check((bool)$pathModel->save($pathwayBase + ['revision' => 4,'state' => 1]),
    'explicit republication of restored pathway succeeds: ' . $pathModel->getError());
$restored = $service->detail($operator, 'en-GB', $pathwayId);
$check(count($restored['courses']) === 2 && $restored['courses'][0]['id'] === $courseId,
    'explicit republication restores the same public pathway course ordering');
// LCM-03: reversible trash is isolated from course enrolments and the public catalogue.
$check((bool)$pathModel->save($pathwayBase + ['revision' => 5,'state' => -2]),
    'moving a pathway to trash succeeds: ' . $pathModel->getError());
$check((int)$db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult() === -2,
    'trash is represented by the native state');
$trashDenied = false;
try {
    $service->detail($operator, 'en-GB', $pathwayId);
} catch (\RuntimeException $error) {
    $trashDenied = $error->getCode() === 404;
}
$check($trashDenied, 'trashed pathway is inaccessible via direct public URL');
$assertSiblingUnchanged('trash');
$trashGuestDenied = false;
try {
    $service->detail($guest, 'en-GB', $pathwayId);
} catch (\RuntimeException $error) {
    $trashGuestDenied = $error->getCode() === 404;
}
$check($trashGuestDenied, 'trashed pathway direct detail also denies anonymous visitors');
$check($db->setQuery('SELECT id,state FROM #__jlms_courses WHERE id IN (' . $courseId . ',' . $hiddenCourseId . ') ORDER BY id')->loadAssocList() === $courseStatesBefore,
    'trashing a pathway never changes the states of its constituent courses');
$check($db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn() === $beforeRelations,
    'trash retains every course association in order');
$check((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . $courseId)->loadResult() === $enrollmentsBeforeArchive,
    'trash does not alter course enrolments');
$check(!$pathModel->save($pathwayBase + ['revision' => 6,'state' => 1]),
    'trashed pathway cannot be published directly without draft restoration');
$check((int)$db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult() === -2,
    'rejected direct publishing leaves trashed record intact');
$check((bool)$pathModel->save($pathwayBase + ['revision' => 6,'state' => 0]),
    'restoring from trash to unpublished succeeds');
$check((int)$db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult() === 0,
    'restoration does not automatically publish');
$check((bool)$pathModel->save($pathwayBase + ['revision' => 7,'state' => 1]),
    'explicit republication after trash restoration succeeds');
$check(count($service->detail($operator, 'en-GB', $pathwayId)['courses']) === 2,
    'republished pathway preserves all associations');
// LCM-03: multi-row operations must commit together and preserve relationships.
$beforeBulkCourses = $db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn();
$beforeBulkEnrollments = (int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . $courseId)->loadResult();
$check($pathModel->changeStates([$pathwayId, $emptyId], -2), 'bulk trash of two pathways succeeds: ' . $pathModel->getError());
$check((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_pathways WHERE id IN (' . $pathwayId . ',' . $emptyId . ') AND state=-2')->loadResult() === 2,
    'both selected pathways are trashed');
$check(!$pathModel->changeStates([$pathwayId, 999999999], 0),
    'bulk restore including missing record must roll back every transition');
$check((int) $db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult() === -2,
    'failed bulk selection did not restore first pathway');
$check(!$pathModel->changeStates([$pathwayId, $pathwayId], 0), 'duplicate identifiers denied');
$check(!$pathModel->changeStates([$pathwayId], 1), 'bulk action cannot publish directly from trash');
// LCM-03: native checkout of one record must roll back the complete bulk action.
$otherEditor = (int)$db->setQuery('SELECT id FROM #__users WHERE id <> ' . (int)$operator->id . ' ORDER BY id LIMIT 1')->loadResult();
$check($otherEditor > 0, 'a second Joomla identity exists for checkout isolation');
$db->setQuery('UPDATE #__jlms_pathways SET checked_out=' . $otherEditor . ' WHERE id=' . $emptyId)->execute();
$lockedRevision = (int)$db->setQuery('SELECT revision FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult();
$check(!$pathModel->changeStates([$pathwayId, $emptyId], 0),
    'bulk restore rejects a pathway checked out by another editor');
$check((int)$db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult() === -2
    && (int)$db->setQuery('SELECT revision FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult() === $lockedRevision,
    'checkout-denied bulk transition rolls back previous rows and revisions');
$check(!$pathModel->purgeEmpty($emptyId), 'purge rejects a checked-out empty pathway');
$check((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_pathways WHERE id=' . $emptyId)->loadResult() === 1,
    'checkout-denied purge preserves its pathway');
$db->setQuery('UPDATE #__jlms_pathways SET checked_out=' . (int)$operator->id . ' WHERE id=' . $emptyId)->execute();
$check($pathModel->changeStates([$pathwayId, $emptyId], 0),
    'current checkout owner can restore both pathways');
$db->setQuery('UPDATE #__jlms_pathways SET checked_out=NULL WHERE id=' . $emptyId)->execute();
$check((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_pathways WHERE id IN (' . $pathwayId . ',' . $emptyId . ') AND state=0')->loadResult() === 2,
    'restoration leaves both pathways unpublished');
$check($db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn() === $beforeBulkCourses,
    'bulk actions preserve pathway course links');
$check((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . $courseId)->loadResult() === $beforeBulkEnrollments,
    'bulk actions preserve historical enrollments');
$check($pathModel->changeStates([$pathwayId, $emptyId], 1), 'explicit mass publication after restore succeeds');
// Single-record protected permanent delete: populated pathways always denied.
$check($pathModel->changeStates([$pathwayId, $emptyId], -2), 'prepare both pathways for protected purge');
$check(!$pathModel->purgeEmpty($pathwayId), 'pathway with associated courses cannot be deleted');
$check((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId)->loadResult() === 2,
    'blocked permanent deletion leaves pathway courses intact');
// LCM-05: direct model mutations must enforce server-side permissions even
// when a caller bypasses the administration toolbar and submits valid IDs.
$pathModel->setCurrentUser(new Joomla\CMS\User\User());
$check(!$pathModel->changeStates([$pathwayId, $emptyId], 0),
    'anonymous actor cannot restore trashed pathways through the model');
$check(!$pathModel->purgeEmpty($emptyId),
    'anonymous actor cannot permanently delete a trashed empty pathway');
$check((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_pathways WHERE id IN (' . $pathwayId . ',' . $emptyId . ') AND state=-2')->loadResult() === 2,
    'denied mutation leaves both trashed pathway records unchanged');
$check($db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn() === $beforeBulkCourses,
    'unauthorised lifecycle requests preserve course associations');
$pathModel->setCurrentUser($operator);
$check(!$pathModel->purgeEmpty(0), 'invalid permanent deletion identifier rejected');
$check(!$pathModel->purgeEmpty(999999999), 'nonexistent pathway deletion rejected');
// A native Joomla multilingual association is not protected by a component FK.
$association = (object)[
    'id' => $emptyId, 'context' => 'com_jlms.pathway',
    'key' => md5('jd-learn-ci-pathway-' . $emptyId),
];
$db->insertObject('#__associations', $association);
$check(!$pathModel->purgeEmpty($emptyId), 'multilingual pathway reference blocks permanent deletion');
$check((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_pathways WHERE id=' . $emptyId)->loadResult() === 1,
    'blocked pathway purge preserves the referenced record');
$db->setQuery("DELETE FROM #__associations WHERE context='com_jlms.pathway' AND id=" . $emptyId)->execute();
// LCM-03: a third-party table with ON DELETE CASCADE must not lose its
// history when an empty pathway is permanently removed.
$externalTable = '#__jlms_ci_pathway_fk_guard';
$db->setQuery('CREATE TABLE ' . $db->quoteName($externalTable)
    . ' (pathway_id BIGINT UNSIGNED NOT NULL, note VARCHAR(40) NOT NULL,'
    . ' CONSTRAINT jlms_ci_pathway_fk FOREIGN KEY (pathway_id) REFERENCES '
    . $db->quoteName('#__jlms_pathways') . ' (id) ON DELETE CASCADE) ENGINE=InnoDB')->execute();
try {
    $db->setQuery('INSERT INTO ' . $db->quoteName($externalTable)
        . ' (pathway_id,note) VALUES (' . (int)$emptyId . ", 'must-survive')")->execute();
    $check(!$pathModel->purgeEmpty($emptyId),
        'FK-backed external historical reference blocks purge even with ON DELETE CASCADE');
    $check((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_pathways WHERE id=' . (int)$emptyId)->loadResult() === 1,
        'external-reference denial retains pathway');
    $check((int)$db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName($externalTable))->loadResult() === 1,
        'external-reference denial retains third-party historical row');
} finally {
    $db->setQuery('DROP TABLE IF EXISTS ' . $db->quoteName($externalTable))->execute();
}
$check($pathModel->purgeEmpty($emptyId), 'only an empty trashed pathway can be deleted');
$assertSiblingUnchanged('empty-pathway purge');
$check((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_pathways WHERE id=' . $emptyId)->loadResult() === 0,
    'empty trashed pathway is removed');
$check((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . $courseId)->loadResult() === $beforeBulkEnrollments,
    'protected purge leaves course enrollments untouched');
$check($db->setQuery('SELECT id,state FROM #__jlms_courses WHERE id IN (' . $courseId . ',' . $hiddenCourseId . ') ORDER BY id')->loadAssocList() === $courseStatesBefore,
    'permanent deletion of an empty pathway leaves constituent course states untouched');
$check($pathModel->changeStates([$pathwayId], 0) && $pathModel->changeStates([$pathwayId], 1),
    'the populated pathway is restored for retention testing');
// LCM-03: direct editorial saves must respect native Joomla checkout, not just bulk lifecycle tasks.
$editorBefore = $db->setQuery('SELECT title,state,revision,checked_out FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadAssoc();
$editorCoursesBefore = $db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn();
$db->setQuery('UPDATE #__jlms_pathways SET checked_out=' . $otherEditor . ' WHERE id=' . $pathwayId)->execute();
$lockedEditorial = array_replace($pathwayBase, [
    'revision' => (int)$editorBefore['revision'], 'state' => 1,
    'title' => 'Unauthorized editorial rewrite', 'courses' => [],
]);
$check(!$pathModel->save($lockedEditorial), 'direct pathway save rejects checkout held by another editor');
$editorAfterDenied = $db->setQuery('SELECT title,state,revision,checked_out FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadAssoc();
$check($editorAfterDenied['title'] === $editorBefore['title']
    && $editorAfterDenied['state'] === $editorBefore['state']
    && $editorAfterDenied['revision'] === $editorBefore['revision']
    && (int)$editorAfterDenied['checked_out'] === $otherEditor,
    'denied editorial save leaves pathway fields, revision and checkout owner unchanged');
$check($db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn() === $editorCoursesBefore,
    'denied editorial save preserves ordered course associations');
$db->setQuery('UPDATE #__jlms_pathways SET checked_out=' . (int)$operator->id . ' WHERE id=' . $pathwayId)->execute();
$check($pathModel->save(array_replace($pathwayBase, [
    'revision' => (int)$editorBefore['revision'], 'state' => 1,
])),
    'pathway checkout owner can save with valid ACL and current revision');
$db->setQuery('UPDATE #__jlms_pathways SET checked_out=NULL WHERE id=' . $pathwayId)->execute();
$check($db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn() === $editorCoursesBefore,
    'authorized editor save preserves pathway course order');
// LCM-03: an editorial patch without the courses key must not clear the
// programme. Supplying courses=[] explicitly must still clear it.
$currentRevision = (int)$db->setQuery('SELECT revision FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult();
$partial = $pathwayBase + ['revision' => $currentRevision,'state' => 1];
unset($partial['courses']);
$check($pathModel->save($partial), 'partial editorial save without courses succeeds: ' . $pathModel->getError());
$readLinks = static fn (): array => array_map('intval', $db->setQuery(
    'SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering'
)->loadColumn());
$check($readLinks() === array_map('intval', $beforeBulkCourses),
    'partial editorial save preserves all existing pathway course links');
$currentRevision = (int)$db->setQuery('SELECT revision FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult();
$check($pathModel->save(array_replace($pathwayBase, ['revision' => $currentRevision,'state' => 1,'courses' => []])),
    'explicitly empty courses list clears pathway programme');
$check($readLinks() === [], 'explicit empty courses list removes course links');
$currentRevision = (int)$db->setQuery('SELECT revision FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult();
$check($pathModel->save($pathwayBase + ['revision' => $currentRevision,'state' => 1]),
    'restore original programme for retention testing');
$check($readLinks() === array_map('intval', $beforeBulkCourses),
    'restored links retain original course order');
file_put_contents($lifecycleSnapshotFile, json_encode($readLifecycleSnapshot($pathwayId, $courseId), JSON_THROW_ON_ERROR));
file_put_contents($fixture, json_encode(['pathway' => $pathwayId,'course' => $courseId,'hidden_course' => $hiddenCourseId,'student' => (int)$operator->id], JSON_THROW_ON_ERROR));
echo json_encode(['pathway_checks' => 14,'status' => 'passed','pathway_id' => $pathwayId,'student_enrollment' => $student], JSON_THROW_ON_ERROR) . PHP_EOL;
