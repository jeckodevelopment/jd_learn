<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/scheduler-health-joo04.functions.php';


require_once __DIR__ . '/../component/administrator/src/Infrastructure/SchedulerHealth.php';

use Jlms\Component\Jlms\Administrator\Infrastructure\SchedulerHealth;

$now = new DateTimeImmutable('2026-11-19 12:00:00', new DateTimeZone('UTC'));
$task = (object) ['id' => 7, 'state' => 1, 'last_execution' => null,
    'next_execution' => '2026-11-19 12:05:00', 'times_executed' => 0, 'times_failed' => 0,
        'last_exit_code' => null, 'execution_rules' => '{"rule-type":"interval-minutes","interval-minutes":5}'];

if (count(SchedulerHealth::TYPES) !== 3) {
    throw new RuntimeException('Every task routine must be monitored');
}
checkState(null, true, false, $now, 'missing');
checkState($task, false, false, $now, 'disabled');
$off = clone $task;
$off->state = 0;
checkState($off, true, false, $now, 'disabled');
checkState($task, true, true, $now, 'duplicates');
checkState($task, true, false, $now, 'waiting');
$task->next_execution = '2026-11-19 11:50:00';
checkState($task, true, false, $now, 'waiting');
$task->next_execution = '2026-11-19 11:49:59';
checkState($task, true, false, $now, 'overdue');
$task->times_executed = 1;
$task->last_execution = '2026-11-19 11:40:00';
$task->next_execution = '2026-11-19 12:05:00';
checkState($task, true, false, $now, 'operational');
$task->last_exit_code = 5;
checkState($task, true, false, $now, 'failed');
$task->last_exit_code = 0;
$task->next_execution = null;
checkState($task, true, false, $now, 'unknown');
$task->next_execution = 'not-a-date';
checkState($task, true, false, $now, 'unknown');
echo "JOO-04: 3 routines and 11 pure classification scenarios OK\n";
