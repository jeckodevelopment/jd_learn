<?php

declare(strict_types=1);

/** Regression contract: one preview -> one mutation -> one outbox test event. */

require_once __DIR__ . '/../component/administrator/src/Application/CourseMessageMutation.php';
use Jlms\Component\Jlms\Administrator\Application\CourseMessageMutation;

function mail08Assert(bool $condition, string $error): void
{
    if (!$condition) {
        throw new RuntimeException($error);
    }
}

$seen = [];
for ($i = 0; $i < 100; $i++) {
    $id = CourseMessageMutation::create();
    mail08Assert(CourseMessageMutation::valid($id), 'Generated mutation is not RFC4122 UUID v4');
    mail08Assert(!isset($seen[$id]), 'Generated mutation repeated');
    $seen[$id] = true;
    $key = CourseMessageMutation::testEventKey(15, 42, $id);
    mail08Assert(preg_match('/^[a-f0-9]{64}$/', $key) === 1, 'Invalid event hash');
    mail08Assert($key === CourseMessageMutation::testEventKey(15, 42, $id), 'Same preview POST must reuse same event key');
    mail08Assert($key === CourseMessageMutation::testEventKey(15, 42, strtoupper($id)), 'UUID case must not bypass deduplication');
    mail08Assert($key !== CourseMessageMutation::testEventKey(16, 42, $id), 'Different course must not reuse test event');
    mail08Assert($key !== CourseMessageMutation::testEventKey(15, 43, $id), 'Different actor must not reuse test event');
}
mail08Assert(count($seen) === 100, 'Missing mutations');
try {
    CourseMessageMutation::testEventKey(15, 42, 'invalid');
    throw new RuntimeException('Invalid mutation must be rejected');
} catch (InvalidArgumentException) {
}

$controller = file_get_contents(__DIR__ . '/../component/administrator/src/Controller/CoursemessageController.php');
$model = file_get_contents(__DIR__ . '/../component/administrator/src/Model/CoursemessageModel.php');
$view = file_get_contents(__DIR__ . '/../component/administrator/tmpl/coursemessage/default.php');
$service = file_get_contents(__DIR__ . '/../component/administrator/src/Application/CourseCommunicationService.php');
$mailQueue = file_get_contents(__DIR__ . '/../component/administrator/src/Infrastructure/MailQueue.php');
$schema = file_get_contents(__DIR__ . '/../component/administrator/sql/install.mysql.utf8mb4.sql');

mail08Assert(
    str_contains(
        $controller,
        "'mutation_id' => " . '\\Jlms\\Component\\Jlms\\Administrator\\Application\\CourseMessageMutation::create()'
    ),
    'New preview must persist a fresh mutation'
);
mail08Assert(str_contains($model, "\\CourseMessageMutation::valid("), 'GET must validate persisted mutation');
mail08Assert(str_contains($model, "\\CourseMessageMutation::create()"), 'Legacy session must be upgraded once');
mail08Assert(str_contains($model, "'mutation_id'] = " . '$preview'
    . "['mutation_id']"), 'GET must pass persisted mutation to view');
mail08Assert(str_contains($view, '$mutation = $preview' . "['mutation_id'] ?? '';"), 'View must read, never mint, the id');
mail08Assert(!str_contains($view, 'random_bytes('), 'GET must not mint mutations');
mail08Assert(str_contains($service, 'CourseMessageMutation::testEventKey('), 'Test send must use deterministic event key');
mail08Assert(str_contains($mailQueue, 'A repeated event key is an expected idempotent no-op'), 'Outbox dedupe must remain');
mail08Assert(str_contains($schema, 'UNIQUE KEY `uq_event_key` (`event_key`)'), 'Database uniqueness needed for concurrent POSTs');

echo "MAIL-08: stable mutation across GET, unique UUID v4, scoped event key and durable unique outbox index OK\n";
