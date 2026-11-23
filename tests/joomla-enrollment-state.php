<?php

declare(strict_types=1);

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
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserById($userId));
$component = $app->bootComponent('com_jlms');
$factory = $component->getMVCFactory();

$actor = $app->getIdentity();
$service = $component->getEnrollmentStateService();
$learning = $component->getLearningService();
$checks = 0;
function stateExpect(bool $ok, string $message): void
{
    global$checks;
    if (!$ok) {
        throw new \RuntimeException($message);
    }
    $checks++;
}
function stateDenied(callable $call, int $code, string $message): void
{
    try {
        $call();
    } catch (\RuntimeException $e) {
        stateExpect($e->getCode() === $code, $message . ': ' . $e->getMessage());
        return;
    }throw new \RuntimeException($message);
}
function stateKey(): string
{
    $k = bin2hex(random_bytes(16));
    return substr($k, 0, 8) . '-' . substr($k, 8, 4) . '-4' . substr($k, 13, 3) . '-a' . substr($k, 17, 3) . '-' . substr($k, 20);
}
function stateCommand(array $i, string $reason = 'PRIVATE_STATE_REASON'): array
{
    return [(int)$i['course']->id,$i['enrollment_id'],$i['impact']['enrollment_revision'],$i['impact']['target_status'],$reason,stateKey(),$i['impact']['hash'],true];
}
$path = dirname($root) . '/enrollment-state-fixture.json';
if (($argv[2] ?? '') === 'worker') {
    echo "ready\n";
    fflush(STDOUT);
    fgets(STDIN);
    $command = json_decode(file_get_contents(dirname($root) . '/enrollment-state-race.json'), true, 16, JSON_THROW_ON_ERROR);
    if (($argv[3] ?? '') === 'different') {
        $command[5] = stateKey();
    }
    try {
        $id = $service->apply($actor, ...$command);
        echo 'ok:' . $id . "\n";
    } catch (\RuntimeException $e) {
        if ($e->getCode() !== 409) {
            throw $e;
        }
        echo "conflict\n";
    }exit;
}
$f = json_decode(file_get_contents(dirname($root) . '/dashboard-fixture.json'), true, 16, JSON_THROW_ON_ERROR);
$rf = json_decode(file_get_contents(dirname($root) . '/roster-fixture.json'), true, 16, JSON_THROW_ON_ERROR);
$course = (int)$f['course'];
$enrollment = (int)$f['active'];
$owner = new \Joomla\CMS\User\User($f['owner']);
$manager = new \Joomla\CMS\User\User($rf['manager']);
$count = static fn()=>(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollment_state_changes WHERE enrollment_id=' . $enrollment)->loadResult();

$seedPlayerSession = static function (int $enrollmentId, int $lessonId) use ($db): string {
    $sessionId = stateKey();
    $sourceRevision = (int)$db->setQuery('SELECT source_revision FROM #__jlms_lessons WHERE id=' . $lessonId)->loadResult();
    $exists = (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_lesson_progress WHERE enrollment_id=' . $enrollmentId . ' AND lesson_id=' . $lessonId)->loadResult();
    if (!$exists) {
        // The schema deliberately requires a persisted lesson-progress parent
        // before a player session can be issued; mirror real playback setup.
        $versionId = (int)$db->setQuery('SELECT course_version_id FROM #__jlms_enrollments WHERE id=' . $enrollmentId)->loadResult();
        $progress = (object)['enrollment_id' => $enrollmentId,'course_version_id' => $versionId,
            'lesson_id' => $lessonId,'source_revision' => $sourceRevision,'coverage_json' => '[]',
            'covered_ms' => 0,'resume_ms' => 0,'state' => 'started',
            'last_activity_at' => gmdate('Y-m-d H:i:s'),'revision' => 1];
        $db->insertObject('#__jlms_lesson_progress', $progress);
    }
    $row = (object)['id' => $sessionId,'enrollment_id' => $enrollmentId,'lesson_id' => $lessonId,
        'source_revision' => $sourceRevision,'sequence_no' => 0,'player_state_json' => '{}',
        'expires_at' => gmdate('Y-m-d H:i:s', time() + 7200)];
    $db->insertObject('#__jlms_progress_sessions', $row);
    return $sessionId;
};
$playerSessionExists = static fn(string $id): bool=>(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_progress_sessions WHERE id=' . $db->quote($id))->loadResult() === 1;

$stable = static fn(int $id)=>$db->setQuery('SELECT user_id,subject_key,course_id,course_version_id,edition_id,required_total,completed_total,started_at,completed_at,last_activity_at,last_lesson_id,starts_at,ends_at FROM #__jlms_enrollments WHERE id=' . $id)->loadAssoc();
if (($argv[2] ?? '') === 'retained') {
    $saved = json_decode(file_get_contents($path), true, 16, JSON_THROW_ON_ERROR);
    stateExpect($count() === $saved['history_count'], 'state history retained');
    stateExpect($stable($enrollment) === $saved['stable'], 'same enrollment and learning evidence retained');
    stateExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__action_logs WHERE extension='com_jlms.enrollmentstate' AND item_id=" . (int)$saved['first_event'])->loadResult() === 1, 'native state audit retained');
    echo json_encode(['enrollment_state_retention_checks' => $checks,'status' => 'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
    exit;
}
// SEC-P1 batch: a blocked Joomla operator retains no write or read privilege,
// even if the previous User instance and a valid preview remain in memory.
$secP1Preview = $service->editor($actor, $course, $enrollment, 'suspended');
$secP1Command = stateCommand($secP1Preview);
$secP1OriginalBlock = (int) $db->setQuery('SELECT block FROM #__users WHERE id=' . $userId)->loadResult();
$secP1BeforeHistory = $count();
$secP1BeforeEnrollment = $stable($enrollment);
try {
    $db->setQuery('UPDATE #__users SET block=1 WHERE id=' . $userId)->execute();
    stateDenied(fn() => $service->editor($actor, $course, $enrollment, 'suspended'), 403, 'blocked stale actor cannot read state editor');
    stateDenied(fn() => $service->apply($actor, ...$secP1Command), 403, 'blocked stale actor cannot apply state transition');
    stateExpect(
        $count() === $secP1BeforeHistory && $stable($enrollment) === $secP1BeforeEnrollment,
        'blocked state operations preserve history and enrollment'
    );
} finally {
    $db->setQuery('UPDATE #__users SET block=' . $secP1OriginalBlock . ' WHERE id=' . $userId)->execute();
}
stateExpect(
    $service->editor($actor, $course, $enrollment, 'suspended')['impact']['hash'] === $secP1Preview['impact']['hash'],
    'state editor returns after account unblock'
);

if (($argv[2] ?? '') === 'cancellation') {
// MAIL-F: only real, not-completed cancellations produce one audited notification.
    $cancelUser = new \Joomla\CMS\User\User();
    $cancelData = ['name' => 'CI Cancellable Learner','username' => 'cicancellable20261114','email' => 'cicancellable20261114@example.invalid',
    'password' => 'CI-Only-Long-Password-2026','password2' => 'CI-Only-Long-Password-2026',
    'groups' => [2,$f['group']],'block' => 0,'activation' => ''];
    if (!$cancelUser->bind($cancelData) || !$cancelUser->save()) {
        throw new \RuntimeException($cancelUser->getError());
    }
    $cancelUser = new \Joomla\CMS\User\User((int) $cancelUser->id);
    $cancelEnrollment = $learning->start($cancelUser, 'en-GB', $course, bin2hex(random_bytes(16)));
    $cancelBefore = $stable($cancelEnrollment);
    $cancelPlayer = $seedPlayerSession($cancelEnrollment, (int)$f['lesson']);
    stateExpect($playerSessionExists($cancelPlayer), 'cancelled fixture starts with a persistent player session');
    $cancelPreview = $service->editor($actor, $course, $cancelEnrollment, 'cancelled');
    stateExpect($cancelPreview['impact']['blocked'] === null && $cancelPreview['impact']['target_status'] === 'cancelled', 'valid cancellation preview');
    $cancelCommand = stateCommand($cancelPreview);
    $cancelRecord = $service->apply($actor, ...$cancelCommand);
    stateExpect($service->apply($actor, ...$cancelCommand) === $cancelRecord, 'replayed cancellation does not duplicate event');
    stateExpect((string) $db->setQuery('SELECT status FROM #__jlms_enrollments WHERE id=' . $cancelEnrollment)->loadResult() === 'cancelled', 'cancellation persisted');
    stateExpect($cancelBefore === $stable($cancelEnrollment), 'cancellation preserves prior learning and assignment data');
    stateExpect(!$playerSessionExists($cancelPlayer), 'cancellation invalidates player session without erasing learning history');
    stateDenied(fn()=>$learning->resumeTarget($cancelUser, 'en-GB', $cancelEnrollment), 403, 'cancelled enrollment denies classroom');
    stateExpect($service->editor($actor, $course, $cancelEnrollment, 'active')['impact']['blocked'] !== null, 'cancelled enrollment cannot be reactivated');
    stateExpect($service->editor($actor, $course, $cancelEnrollment, 'cancelled')['impact']['blocked'] !== null, 'cancelled enrollment cannot be cancelled twice');
    $cancelQ = $db->createQuery()->select('COUNT(*)')->from('#__jlms_mail_queue')
    ->where("template_id = 'com_jlms.enrollment.cancelled'")
    ->where('event_key = ' . $db->quote(hash('sha256', 'enrollment.state:' . $cancelRecord)));
    stateExpect((int) $db->setQuery($cancelQ)->loadResult() === 1, 'cancellation queues exactly one learner notification');
    $completionPreview = $service->editor($actor, $course, (int)$f['completed'], 'cancelled');
    stateExpect($completionPreview['impact']['blocked'] !== null, 'completed enrollment cannot be cancelled');

    echo json_encode(['enrollment_cancellation_checks' => $checks, 'status' => 'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
    exit;
}
$before = $stable($enrollment);
$groups = $db->setQuery('SELECT user_id,group_id FROM #__user_usergroup_map ORDER BY user_id,group_id')->loadAssocList();
$suspendedPlayer = $seedPlayerSession($enrollment, (int)$f['lesson']);
stateExpect($playerSessionExists($suspendedPlayer), 'active enrollment holds a player session before suspension');
$i = $service->editor($manager, $course, $enrollment, 'suspended');
$command = stateCommand($i);
$event = $service->apply($manager, ...$command);
stateExpect(!$playerSessionExists($suspendedPlayer), 'suspension invalidates persistent player session');
stateExpect($service->editor($actor, $course, $enrollment)['impact']['previous_status'] === 'suspended', 'authorized suspension persisted');
stateExpect($service->apply($manager, ...$command) === $event && $count() === 1, 'same transport retry creates one event');
stateDenied(fn()=>$learning->resumeTarget($owner, 'en-GB', $enrollment), 403, 'suspension denies classroom resume');
stateDenied(fn()=>$learning->complete($owner, 'en-GB', $enrollment, $f['lesson'], bin2hex(random_bytes(16))), 403, 'suspension denies new learning writes');
// Reactivation of an individual enrollment cannot pre-arm an archived or suspended course.
$courseBefore = $db->setQuery('SELECT state,delivery_enabled FROM #__jlms_courses WHERE id=' . (int)$course)->loadAssoc();
$historyBefore = $count();
try {
    foreach ([['state' => 2,'delivery_enabled' => 0],['state' => 0,'delivery_enabled' => 1],['state' => 1,'delivery_enabled' => 0],['state' => -2,'delivery_enabled' => 0]] as $blockedCourse) {
        $db->setQuery('UPDATE #__jlms_courses SET state=' . (int)$blockedCourse['state'] . ', delivery_enabled=' . (int)$blockedCourse['delivery_enabled'] . ' WHERE id=' . (int)$course)->execute();
        $blocked = $service->editor($actor, $course, $enrollment, 'active');
        stateExpect($blocked['impact']['blocked'] !== null, 'individual reactivation gated by course state and delivery');
        stateDenied(fn()=>$service->apply($actor, ...stateCommand($blocked)), 409, 'direct reactivation rejected for inactive course');
        stateExpect($count() === $historyBefore, 'blocked individual reactivation does not create history');
    }
} finally {
    $db->setQuery('UPDATE #__jlms_courses SET state=' . (int)$courseBefore['state'] . ', delivery_enabled=' . (int)$courseBefore['delivery_enabled'] . ' WHERE id=' . (int)$course)->execute();
}
$changed = $command;
$changed[4] = 'Changed reason';
stateDenied(fn()=>$service->apply($manager, ...$changed), 409, 'UUID reuse with changed payload rejected');
$next = $service->editor($actor, $course, $enrollment, 'active');
$reactivate = stateCommand($next);
$broken = new \Jlms\Component\Jlms\Administrator\Application\EnrollmentStateService($db, $learning, $component->getEnrollmentRosterService(), static function (): void {
    throw new \RuntimeException('COM_JLMS_ENROLLMENT_STATE_AUDIT_FAILED', 503);
});
stateDenied(fn()=>$broken->apply($actor, ...$reactivate), 503, 'audit outage is a failed operation');
stateExpect($count() === 1 && $service->editor($actor, $course, $enrollment)['impact']['previous_status'] === 'suspended', 'audit failure rolls back state and event');
$service->apply($actor, ...$reactivate);
stateExpect($learning->resumeTarget($owner, 'en-GB', $enrollment)['lesson_id'] === $f['lesson'], 'reactivation resumes same learning history');
stateExpect($service->apply($manager, ...$command) === $event && $service->editor($actor, $course, $enrollment)['impact']['previous_status'] === 'active', 'late replay does not reapply historical suspension');
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$manager->id, (int)$rf['group']);
try {
    stateDenied(fn()=>$service->apply($manager, ...$command), 403, 'revoked rights deny even historical replay');
} finally {
    \Joomla\CMS\User\UserHelper::addUserToGroup((int)$manager->id, (int)$rf['group']);
}
stateDenied(fn()=>$service->editor($owner, $course, $enrollment), 403, 'student cannot manage pedagogical state');
stateDenied(fn()=>$service->editor(new \Joomla\CMS\User\User(), $course, $enrollment), 401, 'anonymous state access denied');
stateDenied(fn()=>$service->editor($manager, (int)$rf['other_course'], $f['italian']), 403, 'course-specific management scope');
stateDenied(fn()=>$service->editor($actor, $course, $f['italian']), 404, 'foreign enrollment cannot cross course');
$i = $service->editor($actor, $course, $enrollment, 'suspended');
$base = stateCommand($i);
foreach ([[7,false,400],[3,'rejected',400],[4,'',422],[4,'<script>',422],[4,str_repeat('x', 501),422],[6,str_repeat('0', 64),409]] as [$index,$value,$code]) {
    $bad = $base;
    $bad[$index] = $value;
    stateDenied(fn()=>$service->apply($actor, ...$bad), $code, 'malformed or stale command rejected');
}
stateExpect($count() === 2, 'failed commands create no events');
$legacy = $service->editor($actor, $course, (int)$f['completed'], 'suspended');
$legacyBefore = $stable((int)$f['completed']);
$service->apply($actor, ...stateCommand($legacy));
$service->apply($actor, ...stateCommand($service->editor($actor, $course, (int)$f['completed'], 'active')));
stateExpect($stable((int)$f['completed']) === $legacyBefore, 'completed history and expired window preserved');
stateDenied(fn()=>$learning->resumeTarget($owner, 'en-GB', (int)$f['completed']), 403, 'reactivation does not extend expired window');
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$owner->id, (int)$f['group']);
try {
    $service->apply($actor, ...stateCommand($service->editor($actor, $course, $enrollment, 'suspended')));
    $service->apply($actor, ...stateCommand($service->editor($actor, $course, $enrollment, 'active')));
    stateDenied(fn()=>$learning->resumeTarget($owner, 'en-GB', $enrollment), 403, 'reactivation does not grant missing entitlement');
} finally {
    \Joomla\CMS\User\UserHelper::addUserToGroup((int)$owner->id, (int)$f['group']);
}
// Real parallel requests share the enrollment lock and the preview revision.
foreach ([false,true] as $different) {
    $race = stateCommand($service->editor($actor, $course, $enrollment, 'suspended'));
    file_put_contents(dirname($root) . '/enrollment-state-race.json', json_encode($race, JSON_THROW_ON_ERROR));
    $startCount = $count();
    $workers = [];
    foreach (['same',$different ? 'different' : 'same'] as $mode) {
        $pipes = [];
        $proc = proc_open([PHP_BINARY,__FILE__,$root,'worker',$mode], [0 => ['pipe','r'],1 => ['pipe','w'],2 => ['pipe','w']], $pipes);
        if (!is_resource($proc)) {
            throw new \RuntimeException('Worker failed');
        }
        $workers[] = [$proc,$pipes];
    }
    foreach ($workers as [$proc,$pipes]) {
        stream_set_timeout($pipes[1], 30);
        if (trim((string)fgets($pipes[1])) !== 'ready') {
            throw new \RuntimeException('Worker bootstrap failed');
        }
    }
    $db->transactionStart();
    $db->setQuery('SELECT id FROM #__jlms_enrollments WHERE id=' . $enrollment . ' FOR UPDATE')->loadResult();
    foreach ($workers as [$proc,$pipes]) {
        fwrite($pipes[0], "go\n");
        fclose($pipes[0]);
    }usleep(200000);
    $db->transactionCommit();
    $results = [];
    foreach ($workers as [$proc,$pipes]) {
        $out = trim(stream_get_contents($pipes[1]));
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($proc) !== 0) {
            throw new \RuntimeException($err . $out);
        }
        $results[] = $out;
    }
    stateExpect($count() === $startCount + 1, 'parallel requests write one state transition');
    stateExpect($different ? (count(array_filter($results, static fn($v)=>$v === 'conflict')) === 1) : ($results[0] === $results[1] && str_starts_with($results[0], 'ok:')), 'parallel CAS and retry outcomes');
    $service->apply($actor, ...stateCommand($service->editor($actor, $course, $enrollment, 'active')));
}
stateExpect($before === $stable($enrollment), 'all state changes preserve learning history and assignment');
stateExpect($groups === $db->setQuery('SELECT user_id,group_id FROM #__user_usergroup_map ORDER BY user_id,group_id')->loadAssocList(), 'state changes never assign native groups');
$log = $db->setQuery("SELECT message FROM #__action_logs WHERE extension='com_jlms.enrollmentstate' AND item_id=" . $event)->loadResult();
$message = json_decode($log, true, 16, JSON_THROW_ON_ERROR);
stateExpect($message['previous_status'] === 'active' && $message['target_status'] === 'suspended' && $message['reason_reference'] === $event && !str_contains($log, 'PRIVATE_STATE_REASON'), 'native audit uses minimal before/after and reason reference');
// A fresh learner/quiz isolates runtime suspension checks from historical fixtures.
$qf = json_decode(file_get_contents(dirname($root) . '/quiz-attempts-fixture.json'), true, 16, JSON_THROW_ON_ERROR);
$user = new \Joomla\CMS\User\User();
$data = ['name' => 'CI Enrollment State Quiz','username' => 'cienrollmentstatequiz','email' => 'cienrollmentstatequiz@example.invalid','password' => 'Ci-only-Learner-Password-2026','password2' => 'Ci-only-Learner-Password-2026','groups' => [2,$f['group']],'block' => 0,'activation' => ''];
if (!$user->bind($data) || !$user->save()) {
    throw new \RuntimeException($user->getError());
}
$user = new \Joomla\CMS\User\User((int)$user->id);
$qe = $learning->start($user, 'en-GB', $qf['course_id'], bin2hex(random_bytes(16)));
$runtime = $component->getQuizAttemptService();
$attempt = $runtime->start($user, 'en-GB', $qe, $qf['lesson_id'], stateKey());
$aid = $attempt['attempt']['id'];
$answers = json_encode([$attempt['questions'][0]['id'] => ['first']], JSON_THROW_ON_ERROR);
$runtime->mutate($user, 'en-GB', $qe, $qf['lesson_id'], $aid, 1, stateKey(), $answers, false);
$quizBefore = $db->setQuery('SELECT status,revision,rules_snapshot_json,deadline_at FROM #__jlms_attempts WHERE id=' . $aid)->loadAssoc();
$savedAnswers = $db->setQuery('SELECT aa.* FROM #__jlms_attempt_answers AS aa INNER JOIN #__jlms_attempt_questions AS q ON q.id=aa.attempt_question_id WHERE q.attempt_id=' . $aid)->loadAssocList();
// LCM-01: stale authenticated quiz POSTs, including an existing attempt,
// cannot bypass course lifecycle even if admission/delivery flags are stale.
$quizCourseBefore = $db->setQuery('SELECT state, admissions_open, delivery_enabled FROM #__jlms_courses WHERE id=' . (int)$qf['course_id'])->loadAssoc();
try {
    foreach ([0, 2, -2] as $inactiveQuizState) {
        $db->setQuery('UPDATE #__jlms_courses SET state=' . (int)$inactiveQuizState . ', admissions_open=1, delivery_enabled=1 WHERE id=' . (int)$qf['course_id'])->execute();
        stateDenied(fn()=>$runtime->mutate($user, 'en-GB', $qe, $qf['lesson_id'], $aid, 1, stateKey(), $answers, false), 404, 'LCM-01 inactive course denies quiz replay');
        stateDenied(fn()=>$runtime->mutate($user, 'en-GB', $qe, $qf['lesson_id'], $aid, 2, stateKey(), $answers, false), 404, 'LCM-01 inactive course denies quiz save');
        stateDenied(fn()=>$runtime->mutate($user, 'en-GB', $qe, $qf['lesson_id'], $aid, 2, stateKey(), $answers, true), 404, 'LCM-01 inactive course denies quiz submit');
        stateExpect(
            $quizBefore === $db->setQuery('SELECT status,revision,rules_snapshot_json,deadline_at FROM #__jlms_attempts WHERE id=' . $aid)->loadAssoc()
            && $savedAnswers === $db->setQuery('SELECT aa.* FROM #__jlms_attempt_answers AS aa INNER JOIN #__jlms_attempt_questions AS q ON q.id=aa.attempt_question_id WHERE q.attempt_id=' . $aid)->loadAssocList(),
            'LCM-01 lifecycle denial leaves quiz attempt and answers immutable'
        );
    }
} finally {
    $db->setQuery('UPDATE #__jlms_courses SET state=' . (int)$quizCourseBefore['state'] . ', admissions_open=' . (int)$quizCourseBefore['admissions_open'] . ', delivery_enabled=' . (int)$quizCourseBefore['delivery_enabled'] . ' WHERE id=' . (int)$qf['course_id'])->execute();
}
$qi = $service->editor($actor, $qf['course_id'], $qe, 'suspended');
stateExpect($qi['impact']['open_attempts'] === 1, 'preview reports an open quiz');
stateDenied(fn()=>$service->apply($actor, ...stateCommand($service->editor($actor, $qf['course_id'], $qe, 'cancelled'))), 409, 'cannot cancel enrollment during an open quiz');
$service->apply($actor, ...stateCommand($qi));
stateDenied(fn()=>$runtime->mutate($user, 'en-GB', $qe, $qf['lesson_id'], $aid, 2, stateKey(), $answers, false), 403, 'suspension denies quiz save');
stateDenied(fn()=>$runtime->mutate($user, 'en-GB', $qe, $qf['lesson_id'], $aid, 2, stateKey(), $answers, true), 403, 'suspension denies quiz submit');
$service->apply($actor, ...stateCommand($service->editor($actor, $qf['course_id'], $qe, 'active')));
stateExpect($quizBefore === $db->setQuery('SELECT status,revision,rules_snapshot_json,deadline_at FROM #__jlms_attempts WHERE id=' . $aid)->loadAssoc(), 'reactivation never resets quiz status revision snapshot or deadline');
stateExpect($savedAnswers === $db->setQuery('SELECT aa.* FROM #__jlms_attempt_answers AS aa INNER JOIN #__jlms_attempt_questions AS q ON q.id=aa.attempt_question_id WHERE q.attempt_id=' . $aid)->loadAssocList(), 'saved quiz responses retained');
// Stale preview after an authorized quiz save cannot suspend on an outdated impact.
$stale = stateCommand($service->editor($actor, $qf['course_id'], $qe, 'suspended'));
$runtime->mutate($user, 'en-GB', $qe, $qf['lesson_id'], $aid, 2, stateKey(), $answers, false);
stateDenied(fn()=>$service->apply($actor, ...$stale), 409, 'quiz progress invalidates preview');
$runtime->mutate($user, 'en-GB', $qe, $qf['lesson_id'], $aid, 3, stateKey(), $answers, true);
// MAIL-F: an approval request never becomes an enrollment and repeat POSTs are idempotent.
$requestUser = new \Joomla\CMS\User\User();
$requestData = ['name' => 'CI Pending Applicant','username' => 'cimailfpending','email' => 'cimailfpending@example.invalid',
    'password' => 'Ci-only-Learner-Password-2026','password2' => 'Ci-only-Learner-Password-2026',
    'groups' => [2,$f['group']],'block' => 0,'activation' => ''];
if (!$requestUser->bind($requestData) || !$requestUser->save()) {
    throw new \RuntimeException($requestUser->getError());
}
$requestUser = new \Joomla\CMS\User\User((int)$requestUser->id);
$modeBefore = (string)$db->setQuery('SELECT enrollment_mode FROM #__jlms_courses WHERE id=' . $course)->loadResult();
$db->setQuery("UPDATE #__jlms_courses SET enrollment_mode='approval' WHERE id=" . $course)->execute();
try {
    $requestId = $learning->requestApproval($requestUser, 'en-GB', $course, stateKey());
    stateExpect($requestId > 0, 'approval request persisted');
    stateExpect($requestId === $learning->requestApproval($requestUser, 'en-GB', $course, stateKey()), 'request replay reuses pending record');
    stateExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollment_requests WHERE id=' . $requestId . " AND status='pending'")->loadResult() === 1, 'request remains pending');
    stateExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE user_id=' . (int)$requestUser->id . ' AND course_id=' . $course)->loadResult() === 0, 'request grants no learning access');
    stateExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__jlms_mail_queue WHERE event_key='" . hash('sha256', 'enrollment.requested:' . $requestId) . "'")->loadResult() === 1, 'request notifies once');
    stateDenied(fn()=>$learning->start($requestUser, 'en-GB', $course, stateKey()), 409, 'approval is not bypassed by automatic start');
} finally {
    $db->setQuery("UPDATE #__jlms_courses SET enrollment_mode=" . $db->quote($modeBefore) . " WHERE id=" . $course)->execute();
}
// MAIL-F: decisions are restricted to the course and serialized with admission capacity.
$approval = $component->getEnrollmentApprovalService();
$db->setQuery("UPDATE #__jlms_courses SET enrollment_mode='approval' WHERE id=" . $course)->execute();
try {
    $pending = $approval->pending($actor, $course);
    stateExpect(count(array_filter($pending, fn($r)=>(int)$r->id === $requestId)) === 1, 'course manager sees pending request');
    // SEC-P1 batch: neither pending-request metadata nor decision mutations
    // remain accessible after an account block in another session.
    $approvalBlockBefore = (int)$db->setQuery('SELECT block FROM #__users WHERE id=' . $userId)->loadResult();
    $approvalQueueBefore = (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_mail_queue')->loadResult();
    try {
        $db->setQuery('UPDATE #__users SET block=1 WHERE id=' . $userId)->execute();
        stateDenied(fn()=>$approval->pending($actor, $course), 403, 'blocked stale operator cannot read approval requests');
        stateDenied(fn()=>$approval->decide($actor, $course, $requestId, 1, 'approved', 'Blocked request'), 403, 'blocked stale operator cannot approve enrollment');
        stateExpect(
            (string)$db->setQuery('SELECT status FROM #__jlms_enrollment_requests WHERE id=' . $requestId)->loadResult() === 'pending',
            'blocked approval leaves request pending'
        );
        stateExpect(
            (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_mail_queue')->loadResult() === $approvalQueueBefore,
            'blocked approval does not enqueue email'
        );
    } finally {
        $db->setQuery('UPDATE #__users SET block=' . $approvalBlockBefore . ' WHERE id=' . $userId)->execute();
    }
    stateExpect(
        count(array_filter($approval->pending($actor, $course), fn($r)=>(int)$r->id === $requestId)) === 1,
        'approval list restored after account unblock'
    );
    stateDenied(fn()=>$approval->decide($requestUser, $course, $requestId, 1, 'approved', ''), 403, 'learner cannot self-approve');
    $enrollmentApproved = $approval->decide($actor, $course, $requestId, 1, 'approved', 'CI-only approval reason');
    stateExpect($enrollmentApproved > 0, 'approved request creates enrollment');
    stateExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__jlms_enrollments WHERE id=" . $enrollmentApproved . " AND status='active' AND origin='approval'")->loadResult() === 1, 'approved admission active exactly once');
    stateExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__jlms_enrollment_requests WHERE id=" . $requestId . " AND status='approved' AND revision=2")->loadResult() === 1, 'approved state persisted');
    stateDenied(fn()=>$approval->decide($actor, $course, $requestId, 1, 'approved', ''), 409, 'repeat decision must conflict');
    stateExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__jlms_mail_queue WHERE event_key='" . hash('sha256', 'enrollment.decision:' . $requestId) . "'")->loadResult() === 1, 'single approval notification');

    $rejectedUser = new \Joomla\CMS\User\User();
    $rejectedData = ['name' => 'CI Rejected Applicant','username' => 'cimailfrejected','email' => 'cimailfrejected@example.invalid',
        'password' => 'Ci-only-Learner-Password-2026','password2' => 'Ci-only-Learner-Password-2026',
        'groups' => [2,$f['group']],'block' => 0,'activation' => ''];
    if (!$rejectedUser->bind($rejectedData) || !$rejectedUser->save()) {
        throw new \RuntimeException($rejectedUser->getError());
    }
    $rejectedUser = new \Joomla\CMS\User\User((int)$rejectedUser->id);
    $rejectRequestId = $learning->requestApproval($rejectedUser, 'en-GB', $course, stateKey());
    stateExpect($approval->decide($actor, $course, $rejectRequestId, 1, 'rejected', 'CI-only private reason') === 0, 'rejected request has no enrollment ID');
    stateExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__jlms_enrollments WHERE user_id=" . (int)$rejectedUser->id . " AND course_id=" . $course)->loadResult() === 0, 'rejection never grants classroom access');
    stateExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__jlms_enrollment_requests WHERE id=" . $rejectRequestId . " AND status='rejected'")->loadResult() === 1, 'rejection audit state saved');
    stateExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__jlms_mail_queue WHERE event_key='" . hash('sha256', 'enrollment.decision:' . $rejectRequestId) . "'")->loadResult() === 1, 'single rejection email queued');
    // The approval fixture is not part of the pre-existing roster retention baseline.
    // Remove only its CI-created enrollment so the subsequent upgrade checks can
    // compare the original roster snapshot without unrelated test pollution.
    $db->setQuery('DELETE FROM #__jlms_enrollments WHERE id=' . $enrollmentApproved)->execute();
    stateExpect(
        (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE id=' . $enrollmentApproved)->loadResult() === 0,
        'approval fixture cleanup preserves roster retention baseline'
    );
} finally {
    $db->setQuery("UPDATE #__jlms_courses SET enrollment_mode=" . $db->quote($modeBefore) . " WHERE id=" . $course)->execute();
}
$serialized = json_encode($service->editor($actor, $course, $enrollment), JSON_THROW_ON_ERROR);
stateExpect(!str_contains($serialized, 'subject_key') && !str_contains($serialized, 'rules_json'), 'editor excludes internal subject and grading data');
file_put_contents($path, json_encode(['course' => $course,'enrollment' => $enrollment,'owner' => $f['owner'],'lesson' => $f['lesson'],'first_event' => $event,'history_count' => $count(),'stable' => $stable($enrollment),'manager' => $rf['manager'],'manager_group' => $rf['group'],'http_enrollment' => $f['other_enrollment'],'cancelled' => $f['cancelled'],'foreign_enrollment' => $f['italian'],'other_course' => $rf['other_course']], JSON_THROW_ON_ERROR));
echo json_encode(['enrollment_state_checks' => $checks,'status' => 'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
