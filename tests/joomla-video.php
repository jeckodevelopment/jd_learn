<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/joomla-video.functions.php';


/* Run after Joomla's CLI installer and extension:install in an isolated CI site. */

$root = realpath($argv[1] ?? "");
if (!$root || getenv("JLMS_TEST_DISPOSABLE") !== "1") {
    throw new \RuntimeException("Disposable Joomla site required.");
}
// Set fixture plugin state before Joomla caches its enabled-plugin list.
// The retained-data mode must not alter state, so it can detect upgrade regressions.
if (($argv[2] ?? "") !== "retained") {
    $fixtureDb = new \PDO(getenv("JLMS_TEST_DSN"), getenv("JLMS_TEST_USER"), getenv("JLMS_TEST_PASSWORD"), [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    ]);
    $fixtureDb->exec("UPDATE joomla_extensions SET enabled=1 WHERE type='plugin' AND folder='jlmsvideo'");
    $fixtureDb->exec(
        "UPDATE joomla_extensions SET " .
            "params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.mail_to_user',0) WHERE " .
            "type='plugin' AND folder='user' AND element='joomla'",
    );
    $fixtureDb->exec(
        "UPDATE joomla_extensions SET " .
            "params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.playback_enabled',1) WHERE " .
            "type='plugin' AND folder='jlmsvideo' AND element IN ('youtube','vimeo')",
    );
    $fixtureDb->exec(
        "UPDATE joomla_extensions SET " .
            "params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.video_external_policy','embed') " .
            "WHERE type='component' AND element='com_jlms'",
    );
    unset($fixtureDb);
}

// Simulate the web request context required by AdministratorApplication in a CLI smoke test.
$_SERVER["HTTP_HOST"] = "localhost";
$_SERVER["SERVER_NAME"] = "localhost";
$_SERVER["SERVER_PORT"] = "80";
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/administrator/index.php?option=com_jlms";
$_SERVER["SCRIPT_NAME"] = "/administrator/index.php";
$_SERVER["PHP_SELF"] = "/administrator/index.php";
$_SERVER["SCRIPT_FILENAME"] = $root . "/administrator/index.php";

require_once __DIR__ . '/helpers/joomla-video.bootstrap-defines-01.php';
require $root . "/administrator/includes/defines.php";
require $root . "/administrator/includes/framework.php";

require_once __DIR__ . '/helpers/joomla-video.bootstrap-defines-02.php';

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
$container
    ->alias("session.web", "session.web.administrator")
    ->alias("session", "session.web.administrator")
    ->alias(Session::class, "session.web.administrator")
    ->alias(SessionInterface::class, "session.web.administrator");
$app = $container->get(AdministratorApplication::class);
Factory::$application = $app;
$app->loadLanguage(
    $container->get(\Joomla\CMS\Language\LanguageFactoryInterface::class)->createLanguage("en-GB", false),
);
$app->createExtensionNamespaceMap();
$db = $container->get(DatabaseInterface::class);
$userId = (int) $db->setQuery("SELECT id FROM #__users WHERE username = 'cioperator'")->loadResult();
if (!$userId) {
    throw new \RuntimeException("Test Joomla operator missing.");
}
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserById($userId));
$component = $app->bootComponent("com_jlms");
$factory = $component->getMVCFactory();

// LES-04: capacity remains bounded by application and hosting limits.
$capacityStorage = static fn(
    int $configuredMiB,
) => new \Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedMaterialStorage(
    static fn() => new \Joomla\Registry\Registry(["material_max_mib" => $configuredMiB]),
);
$oneMiBLimit = $capacityStorage(1)->limit();
$zeroMiBLimit = $capacityStorage(0)->limit();
$maxMiBLimit = $capacityStorage(20)->limit();
$oversizedMiBLimit = $capacityStorage(999)->limit();
if ($oneMiBLimit <= 0 || $oneMiBLimit > 1048576) {
    throw new \RuntimeException("LES-04 configured 1 MiB ceiling is not enforced.");
}
if ($zeroMiBLimit !== $oneMiBLimit) {
    throw new \RuntimeException("LES-04 zero configuration must clamp to 1 MiB.");
}
if ($maxMiBLimit <= 0 || $maxMiBLimit > 20 * 1048576) {
    throw new \RuntimeException("LES-04 managed upload limit exceeds 20 MiB.");
}
if ($oversizedMiBLimit !== $maxMiBLimit) {
    throw new \RuntimeException("LES-04 oversized configuration bypasses the ceiling.");
}

$cloudflareManifest = simplexml_load_file(
    dirname(__DIR__) . "/plugins/jlmsvideo/cloudflarestream/cloudflarestream.xml",
);
if (!$cloudflareManifest) {
    throw new \RuntimeException("Cloudflare Stream plugin manifest is invalid.");
}
$cloudflareFields = [];
foreach ($cloudflareManifest->config->fields->fieldset->field as $field) {
    $cloudflareFields[(string) $field["name"]] = $field;
}
if (
    isset($cloudflareFields["signing_key_ref"]) ||
    isset($cloudflareFields["signing_key_id"]) ||
    isset($cloudflareFields["signing_mode"])
) {
    throw new \RuntimeException("Cloudflare Stream must expose only the implemented API signing configuration.");
}
$checks = 0;


// SEC-P1/STORAGE-BOUNDARY: local MP4 is plaintext by design and is safe
// only when its configured physical root resolves outside every webroot.
// A missing value may not fall back to managed public material storage.
$badVideoRoot = new \Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedVideoStorage(
    static fn(): \Joomla\Registry\Registry => new \Joomla\Registry\Registry(["video_storage_root" => ""]),
);
videoDenied(fn() => $badVideoRoot->root(), 503, "unset local video root fails closed");
$publicVideoRoot = new \Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedVideoStorage(
    static fn(): \Joomla\Registry\Registry => new \Joomla\Registry\Registry(["video_storage_root" => $root]),
);
videoDenied(fn() => $publicVideoRoot->root(), 503, "webroot local video storage is refused");
$goodVideoPath = dirname($root) . "/private-video-sec-p1";
if (!is_dir($goodVideoPath) && !mkdir($goodVideoPath, 0700)) {
    throw new \RuntimeException("SEC-P1 private-video fixture cannot create root");
}
try {
    $goodVideoRoot = new \Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedVideoStorage(
        static fn(): \Joomla\Registry\Registry => new \Joomla\Registry\Registry([
            "video_storage_root" => $goodVideoPath,
        ]),
    );
    videoExpect(
        $goodVideoRoot->root() === realpath($goodVideoPath),
        "configured off-webroot video root remains supported",
    );
} finally {
    rmdir($goodVideoPath);
}


$learning = $component->getLearningService();
$authoring = $component->getCurriculumService();
$publication = $component->getPublicationService();
$actor = $app->getIdentity();
$student = new \Joomla\CMS\User\User(
    (int) $db->setQuery("SELECT id FROM #__users WHERE username='cilearner'")->loadResult(),
);
$other = new \Joomla\CMS\User\User(
    (int) $db->setQuery("SELECT id FROM #__users WHERE username='cilearnerother'")->loadResult(),
);
if (($argv[2] ?? "") === "retained") {
    $fixture = json_decode(file_get_contents(dirname($root) . "/video-fixture.json"), true, 16, JSON_THROW_ON_ERROR);
    $view = $learning->classroom($student, "en-GB", $fixture["enrollment_id"], $fixture["lesson_id"]);
    videoExpect($view["percent"] === 100 && $view["completed_at"] !== null, "video completion survives upgrade");
    echo json_encode(["video_upgrade" => "passed"]) . PHP_EOL;
    exit(0);
}
$level = (int) $db->setQuery("SELECT id FROM #__viewlevels WHERE title='CI learning access'")->loadResult();
$group = (int) $db->setQuery("SELECT id FROM #__usergroups WHERE title='CI learning entitlement'")->loadResult();
$catid = (int) $db
    ->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")
    ->loadResult();
$model = $factory->createModel("Course", "Administrator", ["ignore_request" => true]);
if (
    !$model->save([
        "id" => 0,
        "revision" => 1,
        "title" => "Video fixture",
        "alias" => "video-fixture",
        "catid" => $catid,
        "language" => "*",
        "catalog_access" => 1,
        "content_access" => $level,
        "enrollment_mode" => "auto",
        "presentation_description" => "<p>Course presentation for the publication fixture.</p>",
        "summary" => "Video fixture",
    ])
) {
    throw new \RuntimeException($model->getError());
}
$course = (int) $model->getState("course.id");
$version = $authoring->createDraft($actor, $course, 1);
$scope = static function () use ($db, $course, $version): array {
    return [
        "course_id" => $course,
        "version_id" => $version,
        "expected_version_revision" => (int) $db
            ->setQuery("SELECT revision FROM #__jlms_course_versions WHERE id=" . $version)
            ->loadResult(),
    ];
};
$module = $authoring->saveModule($actor, $scope() + ["id" => 0, "title" => "Video chapter"]);
$localAlternative = $authoring->saveLesson($actor, $scope() + [
    "id" => 0, "module_id" => $module, "title" => "Local alternative text",
    "kind" => "text", "required" => "0",
    "body" => "<p>Alternative text is served only by the authorised classroom.</p>",
]);
$lesson = $authoring->saveLesson(
    $actor,
    $scope() + [
        "id" => 0,
        "module_id" => $module,
        "title" => "Video lesson",
        "kind" => "video",
        "video_alternative_lesson_id" => $localAlternative,
        "required" => "1",
        "provider_key" => "youtube",
        "source_reference" => "dQw4w9WgXcQ",
        "duration_seconds" => "10",
        "duration_confirmed" => "1",
        "body" => "<p>Video description</p>",
    ],
);
// SCR-06: explicit manual rule is allowed only after a deliberate author choice.
// A second OPTIONAL video keeps the legacy required coverage fixture unchanged.
$manualVideo = $authoring->saveLesson(
    $actor,
    $scope() + [
        "id" => 0,
        "module_id" => $module,
        "title" => "Manual video lesson",
        "kind" => "video",
        "required" => "0",
        "video_completion_rule" => "manual",
        "provider_key" => "youtube",
        "source_reference" => "dQw4w9WgXcQ",
        "duration_seconds" => "10",
        "duration_confirmed" => "1",
        "body" => "<p>Explicit manual lesson.</p>",
    ],
);
videoExpect(
    (string) $db->setQuery("SELECT completion_rule FROM #__jlms_lessons WHERE id=" .
        $manualVideo)->loadResult() === "manual",
    "SCR-06: explicit manual video rule is persisted in the draft"
);
$invalidVideoRule = false;
try {
    $authoring->saveLesson($actor, $scope() + [
        "id" => 0, "module_id" => $module, "title" => "Invalid video rule",
        "kind" => "video", "required" => "0", "video_completion_rule" => "unsafe_rule",
        "provider_key" => "youtube", "source_reference" => "dQw4w9WgXcQ",
        "duration_seconds" => "10", "duration_confirmed" => "1", "body" => "<p>Reject.</p>",
    ]);
} catch (\DomainException $error) {
    $invalidVideoRule = $error->getMessage() === "COM_JLMS_INVALID_INPUT";
}
videoExpect($invalidVideoRule, "SCR-06: unknown manual/video completion rule cannot be submitted");

$cr = (int) $db->setQuery("SELECT revision FROM #__jlms_courses WHERE id=" . $course)->loadResult();
$settings = \Joomla\CMS\Component\ComponentHelper::getParams("com_jlms");
$settings->set("video_external_policy", "blocked");
try {
    $publication->transition(
        $actor,
        $course,
        $version,
        $cr,
        $scope()["expected_version_revision"],
        (int) $publication->transitions($actor, $course)[0]->id,
    );
    throw new \RuntimeException("Blocked YouTube policy permitted publication");
} catch (\DomainException $error) {
    videoExpect(
        $error->getMessage() === "COM_JLMS_VIDEO_EDITOR_POLICY_BLOCKED",
        "Blocked YouTube policy returns its own actionable error",
    );
} finally {
    $settings->set("video_external_policy", "embed");
}
$publication->transition(
    $actor,
    $course,
    $version,
    $cr,
    $scope()["expected_version_revision"],
    (int) $publication->transitions($actor, $course)[0]->id,
);
$enrollment = $learning->start($student, "en-GB", $course, bin2hex(random_bytes(16)));
$now = (int) floor(microtime(true) * 1000);
$service = new \Jlms\Component\Jlms\Administrator\Application\VideoProgressService(
    $db,
    $learning,
    $authoring,
    static function () use (&$now): int {
        return $now;
    },
);
$manualView = $learning->classroom($student, "en-GB", $enrollment, $manualVideo);
videoExpect(
    $manualView["lesson"]->completion_rule === "manual"
    && $manualView["lesson"]->kind === "video",
    "SCR-06: authorised classroom receives only the frozen editorial manual rule"
);
videoDenied(
    fn() => $service->playback($student, "en-GB", $enrollment, $manualVideo, false),
    403,
    "SCR-06: manual video rule does not bypass provider consent"
);
$manualGrant = $service->playback($student, "en-GB", $enrollment, $manualVideo, true);
videoExpect(
    $manualGrant["tracking"] === false && $manualGrant["session_id"] === null,
    "SCR-06: explicit manual video never receives a coverage-tracking session"
);
videoDenied(
    fn() => $learning->complete($other, "en-GB", $enrollment, $manualVideo, bin2hex(random_bytes(16))),
    404,
    "SCR-06: foreign enrollment cannot manually complete a video"
);
$learning->complete($student, "en-GB", $enrollment, $manualVideo, bin2hex(random_bytes(16)));
videoExpect(
    (string) $db->setQuery("SELECT state FROM #__jlms_lesson_progress WHERE enrollment_id=" .
        $enrollment . " AND lesson_id=" . $manualVideo)->loadResult() === "completed",
    "SCR-06: authorised manual video confirmation updates only the lesson ledger"
);
videoExpect(
    (int) $db->setQuery("SELECT completed_total FROM #__jlms_enrollments WHERE id=" .
        $enrollment)->loadResult() === 0,
    "SCR-06: optional manual lesson cannot earn credit for the required coverage video"
);
videoFixtureUpdate(
    "jlms_lessons",
    (object) [
        "id" => $lesson,
        "publish_up" => gmdate("Y-m-d H:i:s", time() + 3600),
        "publish_down" => gmdate("Y-m-d H:i:s", time() + 7200),
    ],
);
videoDenied(
    fn() => $learning->videoContext($student, "en-GB", $enrollment, $lesson),
    403,
    "scheduled video source is protected before release",
);
videoDenied(
    fn() => $service->playback($student, "en-GB", $enrollment, $lesson, true),
    403,
    "scheduled video player is protected before release",
);
videoFixtureUpdate("jlms_lessons", (object) ["id" => $lesson, "publish_up" => null, "publish_down" => null], true);
videoDenied(fn() => $service->playback($student, "en-GB", $enrollment, $lesson, false), 403, "no consent");
videoDenied(fn() => $service->playback($other, "en-GB", $enrollment, $lesson, true), 404, "foreign enrollment");
videoDenied(fn() => $service->playback(new \Joomla\CMS\User\User(), "en-GB", $enrollment, $lesson, true), 401, "guest");
videoDenied(
    fn() => $learning->complete($student, "en-GB", $enrollment, $lesson, bin2hex(random_bytes(16))),
    403,
    "manual completion cannot bypass video",
);
$view = $learning->classroom($student, "en-GB", $enrollment, $lesson);
videoExpect(
    $view["lesson"]->kind === "video" && !isset($view["lesson"]->source_json),
    "classroom never exposes reference",
);
videoExpect(
    ($view["video_alternative"]["id"] ?? 0) === $localAlternative
    && ($view["video_alternative"]["title"] ?? "") === "Local alternative text",
    "SCR-08: explicit internal alternative is visible only in an authorised course classroom"
);
videoFixtureUpdate("jlms_lessons", (object) [
    "id" => $localAlternative,
    "publish_up" => gmdate("Y-m-d H:i:s", time() + 3600),
]);
$scheduledAlternative = $learning->classroom($student, "en-GB", $enrollment, $lesson);
videoExpect(
    $scheduledAlternative["video_alternative"] === null,
    "SCR-08: scheduled alternative title and link are not disclosed before availability"
);
videoFixtureUpdate("jlms_lessons", (object) ["id" => $localAlternative, "publish_up" => null], true);
$noDisclosure = $learning->classroom($student, "en-GB", $enrollment, $lesson);
videoExpect(
    ($noDisclosure["video_alternative"]["id"] ?? 0) === $localAlternative,
    "SCR-08: a now-available alternative becomes visible without relaxing video policy"
);

// LCM-01: withdrawal, archive and trash stop existing video access without
// deleting progress. A promotional end date alone is not a teaching deadline.
foreach ([0, 2, -2] as $blockedState) {
    videoFixtureUpdate(
        "jlms_courses",
        (object) ["id" => $course, "state" => $blockedState, "publish_down" => "2000-01-01 00:00:00"],
    );
    videoDenied(
        fn() => $learning->videoContext($student, "en-GB", $enrollment, $lesson),
        404,
        "inactive course rejects direct video context",
    );
    videoDenied(
        fn() => $service->playback($student, "en-GB", $enrollment, $lesson, true),
        404,
        "inactive course rejects video playback",
    );
    videoDenied(
        fn() => $learning->start($other, "en-GB", $course, bin2hex(random_bytes(16))),
        404,
        "inactive course rejects a new learner",
    );
}
videoFixtureUpdate("jlms_courses", (object) ["id" => $course, "state" => 1, "publish_down" => "2000-01-01 00:00:00"]);
[, , , $availableVideo] = $learning->videoContext($student, "en-GB", $enrollment, $lesson);
videoExpect($availableVideo->access_expires_at === null, "promotional end date alone is not a playback deadline");
$a = $service->playback($student, "en-GB", $enrollment, $lesson, true);
$expiredSession = (object) ["id" => $a["session_id"], "expires_at" => gmdate("Y-m-d H:i:s", time() - 3600)];
$db->updateObject("#__jlms_progress_sessions", $expiredSession, "id");
$b = $service->playback($student, "en-GB", $enrollment, $lesson, true);
$expiredCount = (int) $db
    ->setQuery(
        $db
            ->createQuery()
            ->select("COUNT(*)")
            ->from("#__jlms_progress_sessions")
            ->where("id = :id")
            ->bind(":id", $expiredSession->id),
    )
    ->loadResult();
videoExpect($expiredCount === 0, "authorised playback purges expired player sessions");
$c = $service->playback($student, "en-GB", $enrollment, $lesson, true);
videoExpect($b["options"]["reference"] === "dQw4w9WgXcQ" && !isset($b["playback_token"]), "safe descriptor");
videoFixtureUpdate("jlms_courses", (object) ["id" => $course, "state" => 1, "publish_down" => null], true);
$event = static fn(int $id, string $type, int $p, int $t, bool $playing = true): array => [
    "id" => $id,
    "type" => $type,
    "position_ms" => $p,
    "client_ms" => $t,
    "rate_milli" => 1000,
    "playing" => $playing,
];
$batch = json_encode([$event(1, "play", 0, 0), $event(2, "position", 5000, 5000)], JSON_THROW_ON_ERROR);
$now += 5000;
$save = static fn(array $grant, int $seq, string $events, int $duration = 10000) => $service->save(
    $student,
    "en-GB",
    $enrollment,
    $lesson,
    $grant["session_id"],
    $grant["source_revision"],
    $seq,
    $duration,
    $events,
);
$r = $save($b, 1, $batch);
videoExpect($r["covered_ms"] === 5000 && !$r["lesson_completed"], "first valid interval");
videoExpect($save($b, 1, $batch)["replay"], "identical batch replay");

// LCM-01: a pre-existing session cannot bypass archive/withdrawal/trash using
// an idempotent replay, a fresh sequence or a fresh playback request. Test with
// deliberately stale open flags: course state must gate access independently.
$beforeLifecycleProgress = $db
    ->setQuery(
        "SELECT covered_ms, resume_ms, state, completed_at, revision FROM " .
            "#__jlms_lesson_progress WHERE enrollment_id=" .
            (int) $enrollment .
            " AND lesson_id=" .
            (int) $lesson,
    )
    ->loadAssoc();
$beforeLifecycleSession = $db
    ->setQuery(
        "SELECT sequence_no, last_batch_hash FROM #__jlms_progress_sessions WHERE id=" . $db->quote($b["session_id"]),
    )
    ->loadAssoc();
foreach ([0, 2, -2] as $inactiveCourseState) {
    videoFixtureUpdate(
        "jlms_courses",
        (object) ["id" => $course, "state" => $inactiveCourseState, "admissions_open" => 1, "delivery_enabled" => 1],
    );
    videoDenied(
        fn() => $service->playback($student, "en-GB", $enrollment, $lesson, true),
        404,
        "LCM-01 inactive course denies new playback",
    );
    videoDenied(fn() => $save($b, 1, $batch), 404, "LCM-01 inactive course denies idempotent session replay");
    videoDenied(fn() => $save($b, 2, $batch), 404, "LCM-01 inactive course denies new session telemetry");
    videoDenied(
        fn() => $learning->start($other, "en-GB", $course, bin2hex(random_bytes(16))),
        404,
        "LCM-01 inactive course denies direct new enrollment",
    );
    $afterLifecycleProgress = $db
        ->setQuery(
            "SELECT covered_ms, resume_ms, state, completed_at, revision FROM " .
                "#__jlms_lesson_progress WHERE enrollment_id=" .
                (int) $enrollment .
                " AND lesson_id=" .
                (int) $lesson,
        )
        ->loadAssoc();
    $afterLifecycleSession = $db
        ->setQuery(
            "SELECT sequence_no, last_batch_hash FROM #__jlms_progress_sessions WHERE id=" .
                $db->quote($b["session_id"]),
        )
        ->loadAssoc();
    videoExpect(
        $afterLifecycleProgress === $beforeLifecycleProgress && $afterLifecycleSession === $beforeLifecycleSession,
        "LCM-01 inactive state preserves progress and session metadata without writes",
    );
}
videoFixtureUpdate(
    "jlms_courses",
    (object) ["id" => $course, "state" => 1, "admissions_open" => 1, "delivery_enabled" => 1],
);
videoExpect($save($b, 1, $batch)["replay"], "LCM-01 restored fixture retains valid session replay");

// ISC-07: revocation during an already-open player session must reject both
// fresh playback and writes, including an otherwise valid idempotent replay.
// Restore only the isolated CI enrollment; all learning history must survive.
$originalEnrollmentStatus = (string) $db
    ->setQuery("SELECT status FROM #__jlms_enrollments WHERE id=" . (int) $enrollment)
    ->loadResult();
$priorProgress = $db
    ->setQuery(
        "SELECT covered_ms, resume_ms, state, completed_at, revision FROM " .
            "#__jlms_lesson_progress WHERE enrollment_id=" .
            (int) $enrollment .
            " AND lesson_id=" .
            (int) $lesson,
    )
    ->loadAssoc();
$priorPlayerSession = $db
    ->setQuery(
        "SELECT sequence_no, last_batch_hash FROM #__jlms_progress_sessions WHERE id=" . $db->quote($b["session_id"]),
    )
    ->loadAssoc();
try {
    foreach (["suspended", "cancelled"] as $revokedStatus) {
        $revocation = (object) ["id" => $enrollment, "status" => $revokedStatus];
        $db->updateObject("#__jlms_enrollments", $revocation, "id");
        videoDenied(
            fn() => $service->playback($student, "en-GB", $enrollment, $lesson, true),
            403,
            "revoked enrollment rejects fresh video playback: " . $revokedStatus,
        );
        videoDenied(
            fn() => $save($b, 1, $batch),
            403,
            "revoked enrollment rejects replay of active session: " . $revokedStatus,
        );
        videoDenied(
            fn() => $save($b, 2, $batch),
            403,
            "revoked enrollment rejects new telemetry in active session: " . $revokedStatus,
        );
        $afterProgress = $db
            ->setQuery(
                "SELECT covered_ms, resume_ms, state, completed_at, revision FROM " .
                    "#__jlms_lesson_progress WHERE enrollment_id=" .
                    (int) $enrollment .
                    " AND lesson_id=" .
                    (int) $lesson,
            )
            ->loadAssoc();
        $afterPlayerSession = $db
            ->setQuery(
                "SELECT sequence_no, last_batch_hash FROM #__jlms_progress_sessions WHERE id=" .
                    $db->quote($b["session_id"]),
            )
            ->loadAssoc();
        videoExpect(
            $afterProgress === $priorProgress && $afterPlayerSession === $priorPlayerSession,
            "revoked enrollment preserves progress and session without writing: " . $revokedStatus,
        );
    }
} finally {
    $restore = (object) ["id" => $enrollment, "status" => $originalEnrollmentStatus];
    $db->updateObject("#__jlms_enrollments", $restore, "id");
}
videoExpect($save($b, 1, $batch)["replay"], "restored enrollment retains valid idempotent player replay");

videoDenied(fn() => $save($b, 1, "[]"), 409, "different replay");
videoDenied(fn() => $save($b, 3, $batch), 409, "sequence gap");
videoDenied(fn() => $save($b, 2, $batch, 20000), 422, "duration mismatch");
$student->getAuthorisedViewLevels();
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int) $student->id, $group);
videoDenied(fn() => $save($b, 1, $batch), 403, "revoked access even on replay");
\Joomla\CMS\User\UserHelper::addUserToGroup((int) $student->id, $group);
$now += 4000;
$otherBatch = json_encode([$event(1, "play", 5000, 0), $event(2, "position", 9000, 4000)], JSON_THROW_ON_ERROR);
$r = $save($c, 1, $otherBatch);
videoExpect(
    $r["covered_ms"] === 9000 && $r["lesson_completed"] && $r["course_completed"],
    "cross-tab interval union completes exactly",
);
videoExpect($r["resume_conflict"] && $r["resume_ms"] === 5000, "stale tab cannot overwrite resume");
$now += 1000;
$staleAgain = json_encode([$event(3, "position", 6000, 6000)], JSON_THROW_ON_ERROR);
$r = $save($b, 2, $staleAgain);
videoExpect(
    $r["resume_conflict"] && $r["resume_ms"] === 5000,
    "stale session keeps the newer resume after first conflict",
);
$replay = $save($b, 2, $staleAgain);
videoExpect(
    $replay["replay"] && $replay["resume_conflict"] && $replay["resume_ms"] === 5000,
    "idempotent replay preserves the stale resume conflict",
);
$now += 1000;
$staleThird = json_encode([$event(4, "position", 7000, 7000)], JSON_THROW_ON_ERROR);
$r = $save($b, 3, $staleThird);
videoExpect($r["resume_conflict"] && $r["resume_ms"] === 5000, "repeated stale writes cannot replace the newer resume");

videoExpect(
    (int) $db->setQuery("SELECT completed_total FROM #__jlms_enrollments WHERE id=" . $enrollment)->loadResult() === 1,
    "completion counted once",
);
$persistedResume = (int) $db
    ->setQuery(
        "SELECT resume_ms FROM #__jlms_lesson_progress WHERE enrollment_id=" .
            $enrollment .
            " AND lesson_id=" .
            $lesson,
    )
    ->loadResult();
$completedReplay = $service->playback($student, "en-GB", $enrollment, $lesson, true);
videoExpect(
    $completedReplay["lesson_completed"] === true && $completedReplay["resume_ms"] === 0,
    "a completed lesson opens from the beginning rather than seeking to its previous end",
);
videoExpect(
    (int) $db
        ->setQuery(
            "SELECT resume_ms FROM #__jlms_lesson_progress WHERE enrollment_id=" .
                $enrollment .
                " AND lesson_id=" .
                $lesson,
        )
        ->loadResult() === $persistedResume,
    "reopening does not erase stored resume, coverage, or historical completion",
);
videoExpect(
    (int) $db->setQuery("SELECT completed_total FROM #__jlms_enrollments WHERE id=" . $enrollment)->loadResult() === 1,
    "reopening a completed video never counts completion twice",
);
$service->close($student, "en-GB", $enrollment, $lesson, $completedReplay["session_id"]);
videoExpect($save($c, 1, $otherBatch)["replay"], "completion replay");
$service->close($student, "en-GB", $enrollment, $lesson, $b["session_id"]);
videoDenied(
    fn() => $save($b, 2, json_encode([$event(3, "position", 6000, 6000)], JSON_THROW_ON_ERROR)),
    409,
    "closed session cannot write",
);
file_put_contents(
    dirname($root) . "/video-fixture.json",
    json_encode(
        [
            "course_id" => $course,
            "enrollment_id" => $enrollment,
            "lesson_id" => $lesson,
            "user_id" => (int) $student->id,
        ],
        JSON_THROW_ON_ERROR,
    ),
);
echo json_encode(["video_checks" => $checks, "status" => "passed"]) . PHP_EOL;
