<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/joomla-dashboard.functions.php';


/* Run after Joomla's CLI installer and extension:install in an isolated CI site. */

$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new \RuntimeException('Disposable Joomla site required.');
}
$fixtureDb = new \PDO(
    getenv('JLMS_TEST_DSN'),
    getenv('JLMS_TEST_USER'),
    getenv('JLMS_TEST_PASSWORD'),
    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
);
$languagePlugin = $fixtureDb->query(("SELECT enabled FROM joomla_extensions WHERE type='plugin' AND "
    . "folder='system' AND element='languagefilter'"))->fetchColumn();
$fixtureDb->exec(("UPDATE joomla_extensions SET enabled=1 WHERE type='plugin' AND "
    . "folder='system' AND element='languagefilter'"));
register_shutdown_function(static function () use ($fixtureDb, $languagePlugin): void {
    $q = $fixtureDb->prepare(("UPDATE joomla_extensions SET enabled=? WHERE type='plugin' AND "
        . "folder='system' AND element='languagefilter'"));
    $q->execute([$languagePlugin]);
});

// Simulate the web request context required by AdministratorApplication in a CLI smoke test.
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/administrator/index.php?option=com_jlms';
$_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
$_SERVER['PHP_SELF'] = '/administrator/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/administrator/index.php';

require_once __DIR__ . '/helpers/joomla-dashboard.bootstrap-defines-01.php';
require $root . '/administrator/includes/defines.php';
require $root . '/administrator/includes/framework.php';

require_once __DIR__ . '/helpers/joomla-dashboard.bootstrap-defines-02.php';

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
$app->loadLanguage($container->get(\Joomla\CMS\Language\LanguageFactoryInterface::class)
    ->createLanguage('en-GB', false));
$app->createExtensionNamespaceMap();
$db = $container->get(DatabaseInterface::class);
$userId = (int) $db->setQuery("SELECT id FROM #__users WHERE username = 'cioperator'")->loadResult();
if (!$userId) {
    throw new \RuntimeException('Test Joomla operator missing.');
}
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserById($userId));
$component = $app->bootComponent('com_jlms');
$factory = $component->getMVCFactory();

$operator = $app->getIdentity();
$checks = 0;


$service = $component->getDashboardService();
$learning = $component->getLearningService();
$path = dirname($root) . '/dashboard-fixture.json';
$f = json_decode(file_get_contents(dirname($root) . '/learning-fixture.json'), true, 16, JSON_THROW_ON_ERROR);
$read = static function (\Joomla\CMS\User\User $user, string $filter = 'all') use ($db, $service): array {
    return $service->cards($user, 'en-GB', $db->setQuery($service->query($user, $filter))->loadObjectList());
};
if (($argv[2] ?? '') === 'retained') {
    $saved = json_decode(file_get_contents($path), true, 16, JSON_THROW_ON_ERROR);
    $user = new \Joomla\CMS\User\User($saved['owner']);
    $cards = $read($user);
    dashExpect(count($cards) === 27, 'owner history count retained');
    $card = array_values(array_filter($cards, static fn($c)=>$c['id'] === $saved['completed']))[0];
    dashExpect($card['learning_state'] === 'completed' && $card['access'] === 'expired'
        && $card['percent'] === 100, 'completion and expired access retained independently');
    dashExpect(
        $learning->resumeTarget($user, 'en-GB', $saved['active'])['lesson_id'] === $saved['lesson'],
        'resume history retained'
    );
    $issued = $component->getCertificateService()->listForStudent($user);
    dashExpect(
        count($issued) === 1 && $issued[0]['enrollment_id'] === $saved['completed'],
        'issued certificate identity and ownership survive package upgrade'
    );
    $certificateFile = $component->getCertificateService()->download($user, $saved['completed']);
    try {
        dashExpect(
            $certificateFile['mime'] === 'application/pdf' && $certificateFile['size'] > 100,
            'protected PDF remains available after package upgrade'
        );
    } finally {
        fclose($certificateFile['handle']);
    }
    echo json_encode(['dashboard_retention_checks' => $checks,'status' => 'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
    exit;
}
$newUser = static function (string $name) use ($f): \Joomla\CMS\User\User {
    $u = new \Joomla\CMS\User\User();
    $d = ['name' => 'CI ' . $name,'username' => $name,'email' => $name . '@example.invalid','password'
        => 'Ci-only-Dashboard-Password-2026','password2' => 'Ci-only-Dashboard-Password-2026','groups'
            => [2,$f['group_id']],'block' => 0,'activation' => ''];
    if (!$u->bind($d) || !$u->save()) {
        throw new \RuntimeException('Dashboard fixture user failed');
    }
    return new \Joomla\CMS\User\User((int)$u->id);
};
$owner = $newUser('cidashboard');
$other = $newUser('cidashboardother');
$empty = $newUser('cidashboardempty');
$active = $learning->start($owner, 'en-GB', $f['course_id'], bin2hex(random_bytes(16)));
$otherEnrollment = $learning->start($other, 'en-GB', $f['course_id'], bin2hex(random_bytes(16)));
$base = $db->setQuery('SELECT * FROM #__jlms_enrollments WHERE id=' . $active)->loadObject();
$editionBase = $db->setQuery('SELECT * FROM #__jlms_editions WHERE id=' . (int)$base->edition_id)->loadObject();
$seed = static function (string $code, array $fields = []) use ($db, $base, $editionBase): int {
    // Disposable history fixture only; not a manual-enrollment product API.
    $edition = clone$editionBase;
    unset($edition->id);
    $edition->code = 'dashboard-' . $code;
    $db->insertObject('#__jlms_editions', $edition, 'id');
    $row = clone$base;
    unset($row->id);
    $row->edition_id = (int)$edition->id;
    foreach ($fields as $key => $value) {
        $row->$key = $value;
    }
    $db->insertObject('#__jlms_enrollments', $row, 'id');
    return(int)$row->id;
};
$now = gmdate('Y-m-d H:i:s');
$completed = $seed('completed', ['completed_total' => 3,'completed_at' => $now,'ends_at' => '2000-01-01 00:00:00']);
$suspended = $seed('suspended', ['status' => 'suspended']);
$cancelled = $seed('cancelled', ['status' => 'cancelled']);
$pending = $seed('pending', ['status' => 'pending']);
$future = $seed('future', ['starts_at' => '2099-01-01 00:00:00']);
for ($i = 0; $i < 20; $i++) {
    $seed('page-' . $i);
}
$update = static function (string $table, int $id, array $fields) use ($db): void {
    $r = (object)(['id' => $id] + $fields);
    $db->updateObject('#__' . $table, $r, 'id', true);
};
$snapshot = static fn()=>$db
    ->setQuery(('SELECT revision,started_at,completed_total,last_activity_at,last_lesson_id FROM ' .
        '#__jlms_enrollments WHERE id=') .
            $active)
    ->loadAssoc();
$before = $snapshot();
dashExpect($read($empty) === [], 'empty owner has no accidental enrollments');
dashDenied(fn()=>$service->query(new \Joomla\CMS\User\User()), 401, 'guest history denied');
$app->loadIdentity(new \Joomla\CMS\User\User());
$guestModel = $factory->createModel('Dashboard', 'Site', ['ignore_request' => true]);
dashDenied(fn()=>$guestModel->getItems(), 401, 'native list wrapper preserves guest rejection');
dashDenied(fn()=>$guestModel->getTotal(), 401, 'native totals require authentication too');
$app->loadIdentity($operator);
dashDenied(fn()=>$learning->resumeTarget($other, 'en-GB', $active), 404, 'other owner cannot resolve resume target');
$rows = $db->setQuery($service->query($owner))->loadObjectList();
dashExpect(count($rows) === 26, 'query selects only owner history');
dashDenied(fn()=>$service->cards($other, 'en-GB', $rows), 404, 'projection rejects another owner rows');
$cards = $read($owner);
$byId = array_column($cards, null, 'id');
dashExpect($byId[$active]['learning_state'] === 'not_started' && $byId[$active]['access'] === 'available'
    && $byId[$active]['percent'] === 0, 'new enrollment status independent from access');
dashExpect($byId[$completed]['learning_state'] === 'completed' && $byId[$completed]['access'] === 'expired'
    && $byId[$completed]['percent'] === 100, 'completed history remains visible after window expiry');
dashExpect(
    $byId[$suspended]['access'] === 'suspended' && $byId[$cancelled]['access'] === 'cancelled'
    && $byId[$future]['access'] === 'not_open' && $byId[$pending]['access'] === 'pending',
    'distinct classroom block reasons'
);
dashExpect($byId[$active]['next_action'] === null, 'available course relies on its resume action');
$nextActions = [
    $completed => 'COM_JLMS_DASHBOARD_NEXT_EXPIRED',
    $suspended => 'COM_JLMS_DASHBOARD_NEXT_SUSPENDED',
    $cancelled => 'COM_JLMS_DASHBOARD_NEXT_CANCELLED',
    $future => 'COM_JLMS_DASHBOARD_NEXT_NOT_OPEN',
    $pending => 'COM_JLMS_DASHBOARD_NEXT_PENDING',
];
foreach ($nextActions as $id => $action) {
    dashExpect($byId[$id]['next_action'] === $action, 'blocked enrollment provides its specific next action');
}
$actionKeys = [
    'COM_JLMS_DASHBOARD_NEXT_LANGUAGE',
    'COM_JLMS_DASHBOARD_NEXT_UNAVAILABLE',
    'COM_JLMS_DASHBOARD_NEXT_ACCESS',
    'COM_JLMS_DASHBOARD_NEXT_SUSPENDED',
    'COM_JLMS_DASHBOARD_NEXT_EXPIRED',
    'COM_JLMS_DASHBOARD_NEXT_CANCELLED',
    'COM_JLMS_DASHBOARD_NEXT_PENDING',
    'COM_JLMS_DASHBOARD_NEXT_NOT_OPEN',
];
$languageTexts = [];
foreach (['en-GB','it-IT'] as $language) {
    $source = file_get_contents(dirname(__DIR__) . '/component/site/language/' . $language . '/com_jlms.ini');
    if ($source === false) {
        throw new \RuntimeException('Dashboard language file is missing: ' . $language);
    }
    foreach ($actionKeys as $action) {
        preg_match('/^' . preg_quote($action, '/') . '="([^"]+)"$/m', $source, $match);
        dashExpect(
            isset($match[1]) && trim($match[1]) !== '',
            'blocked next action is translated: ' . $language . ' ' . $action
        );
        $languageTexts[$language][$action] = $match[1];
    }
}
foreach ($actionKeys as $action) {
    dashExpect(
        $languageTexts['en-GB'][$action] !== $languageTexts['it-IT'][$action],
        'blocked next action has distinct English and Italian copy'
    );
}
dashExpect(count($read($owner, 'completed')) === 1 && count($read($owner, 'not_started')) === 25
    && $read($owner, 'in_progress') === [], 'progress filter does not conflate access status');
$target = $learning->resumeTarget($owner, 'en-GB', $active);
dashExpect(
    $target['lesson_id'] === $f['lessons'][0],
    'first required incomplete activity chosen when no last activity'
);
dashExpect($snapshot() === $before, 'dashboard and resume reads do not write educational history');
$learning->complete($owner, 'en-GB', $active, $f['lessons'][0], bin2hex(random_bytes(16)));
$learning->complete($owner, 'en-GB', $active, $f['lessons'][1], bin2hex(random_bytes(16)));
$update('jlms_enrollments', $active, ['last_lesson_id' => 0]);
$beforeFallback = $snapshot();
dashExpect(
    $learning->resumeTarget($owner, 'en-GB', $active)['lesson_id'] === $f['lessons'][2],
    'resume skips completed required lessons and selects the next incomplete required lesson'
);
dashExpect($snapshot() === $beforeFallback, 'required-incomplete fallback remains read-only');
$update('jlms_enrollments', $active, ['last_lesson_id' => $f['lessons'][1]]);
dashExpect(
    $learning->resumeTarget($owner, 'en-GB', $active)['lesson_id'] === $f['lessons'][1],
    'resume keeps last valid activity when one exists'
);
dashExpect(count($read($owner, 'in_progress')) === 1, 'learning progress filter reflects persisted activity');
$foreignLesson = (int)$db->setQuery('SELECT id FROM #__jlms_lessons WHERE course_version_id <> ' . (int)$base
    ->course_version_id . ' ORDER BY id LIMIT 1')->loadResult();
$update('jlms_enrollments', $active, ['last_lesson_id' => $foreignLesson]);
dashExpect($learning->resumeTarget($owner, 'en-GB', $active)['lesson_id'] === $f['lessons'][2], ('cross-version last '
    . 'activity falls back to first required incomplete lesson without '
    . 'disclosing its body'));
$update('jlms_enrollments', $active, ['last_lesson_id' => $f['lessons'][1]]);
dashDenied(fn()=>$learning->resumeTarget($owner, 'en-GB', $suspended), 403, 'suspension cannot be bypassed by resume');
dashDenied(
    fn()=>$learning->resumeTarget($owner, 'en-GB', $completed),
    403,
    'expired completed classroom cannot be reopened by resume'
);
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$owner->id, $f['group_id']);
try {
    $c = array_column($read($owner), null, 'id');
    dashExpect(
        $c[$active]['access'] === 'access' && $c[$active]['next_action'] === 'COM_JLMS_DASHBOARD_NEXT_ACCESS'
         && $c[$completed]['completed'],
        'fresh group revocation keeps owner history but removes access'
    );
    dashDenied(
        fn()=>$learning->resumeTarget($owner, 'en-GB', $active),
        403,
        'old actor object cannot bypass current revocation'
    );
} finally {
    \Joomla\CMS\User\UserHelper::addUserToGroup((int)$owner->id, $f['group_id']);
}
dashExpect(
    array_column($read($owner), null, 'id')[$active]['access'] === 'available',
    'restored current membership restores resume availability'
);
$update('users', (int)$owner->id, ['block' => 1]);
try {
    dashDenied(fn()=>$read($owner), 403, 'blocked account cannot read personal history');
    dashDenied(fn()=>$learning->resumeTarget($owner, 'en-GB', $active), 403, 'blocked account cannot resume');
} finally {
    $update('users', (int)$owner->id, ['block' => 0]);
}
// LCM-01: course lifecycle must deny resume but preserve history for every
// inactive state; teaching mode comes from the immutable enrolled version.
$versionId = (int)$base->course_version_id;
$historicalRules = (string)$db->setQuery('SELECT rules_json FROM #__jlms_course_versions WHERE id='
    . $versionId)->loadResult();
$consultationRules = json_decode($historicalRules, true, 32, JSON_THROW_ON_ERROR);
$consultationRules['mode'] = 'consultation';
$consultationRules['completion_policy'] = 'none';
$consultationRules['sequence_mode'] = 'free';
try {
    foreach ([0,2,-2] as $inactiveState) {
        $update('jlms_courses', $f['course_id'], ['state' => $inactiveState]);
        $c = array_column($read($owner), null, 'id');
        dashExpect(
            count($c) === 26 && $c[$active]['access'] === 'unavailable'
                && $c[$active]['next_action'] === 'COM_JLMS_DASHBOARD_NEXT_UNAVAILABLE'
                && $c[$active]['title'] !== ''
                && $c[$completed]['completed'] && $c[$completed]['percent'] === 100,
            'inactive course keeps owner title, completions and historical counters'
        );
        dashDenied(
            fn()=>$learning->resumeTarget($owner, 'en-GB', $active),
            404,
            'inactive course blocks direct resume'
        );
        $update('jlms_course_versions', $versionId, ['rules_json'
            => json_encode($consultationRules, JSON_THROW_ON_ERROR)]);
        $consultationCards = array_column($read($owner), null, 'id');
        dashExpect(
            $consultationCards[$active]['access'] === 'unavailable' && $consultationCards[$active]['percent'] === null
            && !$consultationCards[$active]['progress_applicable']
            && $consultationCards[$completed]['percent'] === null,
            'inactive consultation retains no progress percentage even for historical completion'
        );
        $update('jlms_course_versions', $versionId, ['rules_json' => $historicalRules]);
    }
} finally {
    $update('jlms_course_versions', $versionId, ['rules_json' => $historicalRules]);
    $update('jlms_courses', $f['course_id'], ['state' => 1]);
}
dashExpect(
    array_column($read($owner), null, 'id')[$active]['access'] === 'available',
    'republished course restores classroom availability'
);
// LCM-01: an issued certificate is historical evidence, never gated by
// the current publication state or by lesson access. A distinct owner still
// cannot list or download it. Use the default installed immutable PDF template.
$certificates = $component->getCertificateService();
$templateId = (int)$db
    ->setQuery("SELECT id FROM #__jlms_certificate_templates WHERE state=1 ORDER BY id LIMIT 1")
    ->loadResult();
dashExpect($templateId > 0, 'certificate fixture has the installed default PDF template');
// DATETIME(6) returns fractional seconds on some database drivers; issuance snapshots use SQL seconds.
$completedAt = substr((string)$db->setQuery('SELECT completed_at FROM #__jlms_enrollments WHERE id='
    . $completed)->loadResult(), 0, 19);
$serial = 'LCM01-' . strtoupper(bin2hex(random_bytes(8)));
$certificate = (object)['enrollment_id' => $completed,'course_version_id' => $versionId,
    'template_id' => $templateId,'current_document_id' => null,'issued_at' => $completedAt,'revision' => 1];
$db->insertObject('#__jlms_certificates', $certificate, 'id');
$templateSnapshot = (string)$db->setQuery('SELECT template_json FROM #__jlms_certificate_templates WHERE id='
    . $templateId)->loadResult();
$identitySnapshot = json_encode(['user_id' => (int)$owner->id,'name' => 'History Learner',
    'course_id' => (int)$f['course_id'],'course_title' => 'Frozen course title',
    'course_version_id' => $versionId,'completed_at' => $completedAt,'language' => 'en-GB'], JSON_THROW_ON_ERROR);
$document = (object)['certificate_id' => (int)$certificate->id,'document_no' => 1,
    'serial' => $serial,'identity_snapshot_json' => $identitySnapshot,
    'template_snapshot_json' => $templateSnapshot,'status' => 'pending_render','issued_at'
        => $completedAt,'revision' => 1];
$db->insertObject('#__jlms_certificate_documents', $document, 'id');
$update('jlms_certificates', (int)$certificate->id, ['current_document_id' => (int)$document->id]);
$certDbSnapshot = $db->setQuery(('SELECT id,serial,issued_at,identity_snapshot_json FROM '
    . '#__jlms_certificate_documents WHERE id=') . (int)$document->id)->loadAssoc();
dashExpect($certificates->listForStudent($other) === [], 'foreign learner has no access to another issued certificate');
dashDenied(fn()=>$certificates->download($other, $completed), 404, 'foreign certificate download denied');
dashDenied(
    fn()=>$certificates->download(new \Joomla\CMS\User\User(), $completed),
    401,
    'anonymous certificate download denied'
);
// SEC-P1: administrative certificate delivery must require the dedicated permission,
// not merely ownership of the enrollment or an authenticated Joomla identity.
dashDenied(fn()=>$certificates->adminDownload($owner, (int)$certificate
    ->id), 403, 'certificate owner cannot use administrator download');
dashDenied(fn()=>$certificates->adminDownload($other, (int)$certificate
    ->id), 403, 'foreign student cannot use administrator download');
dashDenied(fn()=>$certificates->adminDownload(new \Joomla\CMS\User\User(), (int)$certificate
    ->id), 403, 'guest cannot use administrator download');
$adminPdf = $certificates->adminDownload($operator, (int)$certificate->id);
try {
    dashExpect(
        $adminPdf['mime'] === 'application/pdf' && $adminPdf['size'] > 100,
        'authorized certificate manager can retrieve protected document'
    );
} finally {
    fclose($adminPdf['handle']);
}
 // EDGE-01: an existing session object must not bypass a later core account block.
 $preBlockOwner = $owner;
 $update('users', (int)$owner->id, ['block' => 1]);
try {
    dashDenied(
        fn()=>$certificates->download($preBlockOwner, $completed),
        403,
        'blocked Joomla account cannot use old direct certificate download URL'
    );
    dashDenied(
        fn()=>$certificates->listForStudent($preBlockOwner),
        403,
        'blocked Joomla account cannot list previously issued certificates'
    );
} finally {
    $update('users', (int)$owner->id, ['block' => 0]);
}
 dashExpect(
     count($certificates->listForStudent($preBlockOwner)) === 1,
     'reactivated Joomla account can list the same immutable certificate'
 );

 foreach ([0,2,-2] as $inactiveState) {
     $update('jlms_courses', $f['course_id'], ['state' => $inactiveState]);
     try {
         $issued = $certificates->listForStudent($owner);
         dashExpect(
             count($issued) === 1 && $issued[0]['enrollment_id'] === $completed
             && $issued[0]['title'] === 'Frozen course title' && $issued[0]['serial'] === $serial,
             'inactive course retains independently owned certificate listing'
         );
         $file = $certificates->download($owner, $completed);
         try {
             dashExpect(
                 $file['mime'] === 'application/pdf' && $file['size'] > 100
                 && str_starts_with((string)fread($file['handle'], 8), '%PDF-'),
                 'inactive course retains authenticated private PDF delivery'
             );
         } finally {
             fclose($file['handle']);
         }
         dashDenied(
             fn()=>$learning->resumeTarget($owner, 'en-GB', $active),
             404,
             'certificate availability does not re-enable lessons'
         );
         dashExpect(
             $db->setQuery(('SELECT id,serial,issued_at,identity_snapshot_json FROM '
                 . '#__jlms_certificate_documents WHERE id=') . (int)$document->id)->loadAssoc() === $certDbSnapshot,
             'archiving never changes certificate identity, serial, issue date or snapshot'
         );
     } finally {
         $update('jlms_courses', $f['course_id'], ['state' => 1]);
     }
 }
 dashExpect(
     (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_certificates WHERE enrollment_id=' . $completed)
         ->loadResult() === 1,
     'lifecycle changes do not reissue certificates'
 );
 $serialized = json_encode($read($owner), JSON_THROW_ON_ERROR);
 dashExpect(!str_contains($serialized, 'LEARNING_PRIVATE_BODY') && !str_contains($serialized, 'rules_json')
     && !str_contains(
         $serialized,
         'subject_key'
     ) && !str_contains(
         $serialized,
         'user_id'
     ), 'projection contains no private teaching, identity keys or internal rules');
 $app->loadIdentity($owner);
 $model = $factory->createModel('Dashboard', 'Site', ['ignore_request' => true]);
 $model->setState('filter.progress', 'all');
 $model->setState('list.limit', 20);
 $model->setState('list.start', 20);
 $pageItems = $model->getItems();
 $pageTotal = (int) $model->getTotal();
 dashExpect(
     count($pageItems) === 6 && $pageTotal === 26,
     'native pagination bounded to own history'
 );
 dashExpect((bool)$model->getProgressForm()->getField('progress'), 'native progress filter form available');
 $categoryFixture = json_decode(file_get_contents(dirname($root)
     . '/categories-fixture.json'), true, 16, JSON_THROW_ON_ERROR);
 $italian = $learning->start($owner, 'it-IT', $categoryFixture['italianCourse'], bin2hex(random_bytes(16)));
 $c = array_column($read($owner), null, 'id');
 dashExpect(
     count($c) === 27 && $c[$italian]['access'] === 'available',
     'history spans installed course languages independently of UI'
 );
 dashExpect(
     $learning->resumeTarget($owner, 'en-GB', $italian)['language'] === 'it-IT',
     'resume preserves original course language without mapping enrollment'
 );
 $db->setQuery("UPDATE #__languages SET published=0 WHERE lang_code='it-IT'")->execute();
 try {
     $c = array_column($read($owner), null, 'id');
     dashExpect(
         $c[$italian]['access'] === 'language'
         && $c[$italian]['next_action'] === 'COM_JLMS_DASHBOARD_NEXT_LANGUAGE',
         'unpublished course language removes current availability'
     );
     dashDenied(
         fn()=>$learning->resumeTarget($owner, 'en-GB', $italian),
         403,
         'resume rechecks current content language'
     );
 } finally {
     $db->setQuery("UPDATE #__languages SET published=1 WHERE lang_code='it-IT'")->execute();
 }
 $app->loadIdentity($operator);
 $menus = [];
 foreach (['en-GB','it-IT'] as $tag) {
     $alias = 'dashboard-menu-' . substr($tag, 0, 2);
     $table = new \Joomla\CMS\Table\Menu($db);
     $table->setLocation(1, 'last-child');
     $table->bind(['title' => $alias,'alias' => $alias,'menutype' => 'mainmenu','type' => 'component','link'
         => 'index.php?option=com_jlms&view=dashboard','component_id' => (int)$db
             ->setQuery("SELECT extension_id FROM #__extensions WHERE type='component' AND element='com_jlms'")
                 ->loadResult(),'published' => 1,'access' => 2,'language' => $tag,'home' => 0,'params'
                     => '{}','browserNav' => 0]);
     if (!$table->check() || !$table->store()) {
         throw new \RuntimeException('Native dashboard menu failed');
     }
     $menus[$tag] = (int)$table->id;
 }
 require __DIR__ . '/dashboard-catalog-latest-regression.php';
 file_put_contents(
     $path,
     json_encode(
         [
             'owner' => (int)$owner->id,
             'other' => (int)$other->id,
             'empty' => (int)$empty->id,
             'active' => $active,
             'other_enrollment' => $otherEnrollment,
             'completed' => $completed,
             'suspended' => $suspended,
             'cancelled' => $cancelled,
             'pending' => $pending,
             'future' => $future,
             'lesson' => $f['lessons'][1],
             'group' => $f['group_id'],
             'access_level_id' => $f['access_level_id'],
             'course' => $f['course_id'],
             'italian' => $italian,
             'menus' => $menus,
         ],
         JSON_THROW_ON_ERROR
     )
 );
 echo json_encode(['dashboard_checks' => $checks,'status' => 'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
