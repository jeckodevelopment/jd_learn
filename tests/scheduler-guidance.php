<?php
declare(strict_types=1);

require __DIR__ . '/../component/administrator/src/Infrastructure/SchedulerHealth.php';

use Jlms\Component\Jlms\Administrator\Infrastructure\SchedulerHealth;

$expected = [
    'missing' => 'MISSING',
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
