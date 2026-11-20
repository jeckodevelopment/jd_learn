<?php
declare(strict_types=1);

require __DIR__ . '/../component/administrator/src/Infrastructure/SchedulerHealth.php';

use Jlms\Component\Jlms\Administrator\Infrastructure\SchedulerHealth;

$expected = [
    'missing' => 'MISSING',
    'invalid_rules' => 'INVALID_RULES',
    'disabled' => 'DISABLED',
    'duplicates' => 'DUPLICATES',
    'failed' => 'FAILED',
    'overdue' => 'OVERDUE',
    'unknown' => 'UNKNOWN',
    'waiting' => 'WAITING',
    'operational' => 'OPERATIONAL',
];
foreach ($expected as $state => $suffix) {
    if (SchedulerHealth::guidanceKey($state) !== 'COM_JLMS_SCHEDULER_GUIDANCE_' . $suffix) {
        throw new RuntimeException('Wrong scheduler guidance for ' . $state);
    }
    if (SchedulerHealth::guidanceKey($state, true) !== 'COM_JLMS_SCHEDULER_GUIDANCE_DUPLICATES') {
        throw new RuntimeException('Duplicate task warning must take priority over ' . $state);
    }
}
if (SchedulerHealth::guidanceKey('unrecognized') !== 'COM_JLMS_SCHEDULER_GUIDANCE_UNKNOWN') {
    throw new RuntimeException('Unknown scheduler state must fail closed to neutral guidance');
}
echo "Scheduler guidance states and duplicate precedence: OK\n";

foreach (['', '{', 'null', '[]', '{}', '{"rule-type":42}', '{"interval-minutes":5}'] as $broken) {
    if (SchedulerHealth::validExecutionRules($broken)) {
        throw new RuntimeException('Malformed rules accepted: ' . $broken);
    }
}
foreach (['{"rule-type":"interval-minutes","interval-minutes":5}', '{"rule-type":"cron-expression","cron-expression":"0 * * * *"}'] as $wellFormed) {
    if (!SchedulerHealth::validExecutionRules($wellFormed)) {
        throw new RuntimeException('Valid JSON schedule structure rejected');
    }
}
$clock = new DateTimeImmutable('2026-11-20 12:00:00', new DateTimeZone('UTC'));
$task = (object) [
    'id' => 123, 'state' => 1, 'last_execution' => '2026-11-20 11:30:00',
    'last_exit_code' => 0, 'times_executed' => 1, 'times_failed' => 0,
    'next_execution' => '2026-11-20 13:00:00', 'execution_rules' => '{',
];
$invalid = SchedulerHealth::assess($task, true, false, $clock);
if ($invalid['state'] !== 'invalid_rules') {
    throw new RuntimeException('Malformed scheduler rules must not produce operational state');
}
$task->execution_rules = '{"rule-type":"interval-minutes","interval-minutes":5}';
if (SchedulerHealth::assess($task, true, false, $clock)['state'] !== 'operational') {
    throw new RuntimeException('Valid scheduler rules regression');
}
