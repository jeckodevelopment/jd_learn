<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\Database\DatabaseInterface;

/**
 * Read-only status for every JD Learn task plugin routine.
 *
 * Joomla's next_execution is the source of truth for cron and interval rules.
 * A 10-minute grace period tolerates normal Lazy Scheduler delays; a due task
 * older than that is flagged, not silently marked healthy.
 */
final class SchedulerHealth
{
    public const TYPES = [
        'jlms.quiz.expire' => 'COM_JLMS_SCHEDULER_QUIZ',
        'jlms.materials.cleanup' => 'COM_JLMS_SCHEDULER_MATERIALS',
        'jlms.mail.dispatch' => 'COM_JLMS_SCHEDULER_MAIL',
    ];

    public static function inspect(DatabaseInterface $db, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $types = array_keys(self::TYPES);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'type', 'state', 'last_execution', 'last_exit_code',
                'next_execution', 'times_executed', 'times_failed', 'execution_rules']))
            ->from($db->quoteName('#__scheduler_tasks'))
            ->where($db->quoteName('type') . ' IN (' . implode(',', array_map([$db, 'quote'], $types)) . ')')
            ->order($db->quoteName('type') . ' ASC')
            ->order($db->quoteName('id') . ' ASC');
        $tasks = $db->setQuery($query)->loadObjectList();

        $pluginQuery = $db->getQuery(true)->select($db->quoteName('enabled'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('task'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('jlms'));
        $enabled = (int) $db->setQuery($pluginQuery)->loadResult() === 1;

        $groups = array_fill_keys($types, []);
        foreach ($tasks as $task) {
            $groups[(string) $task->type][] = $task;
        }
        $results = [];
        foreach (self::TYPES as $type => $label) {
            $matching = $groups[$type];
            $results[$type] = self::assess($matching[0] ?? null, $enabled, count($matching) > 1, $now);
            $results[$type]['label'] = $label;
            $results[$type]['type'] = $type;
        }
        return $results;
    }

    /**
     * Read-only, localized remediation advice. Never triggers a task or
     * changes operator-managed scheduler settings. Duplicate tasks take
     * precedence because they may lead to repeated dispatch.
     */
    public static function guidanceKey(string $state, bool $duplicates = false): string
    {
        if ($duplicates || $state === 'duplicates') {
            return 'COM_JLMS_SCHEDULER_GUIDANCE_DUPLICATES';
        }

        return match ($state) {
            'missing' => 'COM_JLMS_SCHEDULER_GUIDANCE_MISSING',
            'invalid_rules' => 'COM_JLMS_SCHEDULER_GUIDANCE_INVALID_RULES',
            'disabled' => 'COM_JLMS_SCHEDULER_GUIDANCE_DISABLED',
            'failed' => 'COM_JLMS_SCHEDULER_GUIDANCE_FAILED',
            'overdue' => 'COM_JLMS_SCHEDULER_GUIDANCE_OVERDUE',
            'waiting' => 'COM_JLMS_SCHEDULER_GUIDANCE_WAITING',
            'operational' => 'COM_JLMS_SCHEDULER_GUIDANCE_OPERATIONAL',
            default => 'COM_JLMS_SCHEDULER_GUIDANCE_UNKNOWN',
        };
    }

    /** Validate basic JSON structure only; Joomla owns schedule semantics. */
    public static function validExecutionRules(string $rules): bool
    {
        if (trim($rules) === '') {
            return false;
        }
        try {
            $decoded = json_decode($rules, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }
        return is_array($decoded) && !array_is_list($decoded)
            && is_string($decoded['rule-type'] ?? null)
            && trim($decoded['rule-type']) !== '';
    }

    /**
     * Pure classification for reproducible tests.
     * Missing/unknown next execution is not described as a healthy schedule.
     */
    public static function assess(?object $task, bool $pluginEnabled, bool $duplicates, \DateTimeImmutable $now): array
    {
        $state = 'missing';
        $last = $task ? (string) ($task->last_execution ?? '') : '';
        $next = $task ? (string) ($task->next_execution ?? '') : '';
        $executions = $task ? (int) ($task->times_executed ?? 0) + (int) ($task->times_failed ?? 0) : 0;

        if ($task !== null) {
            if (!$pluginEnabled || (int) $task->state !== 1) {
                $state = 'disabled';
            } elseif ($duplicates) {
                $state = 'duplicates';
            } elseif (!self::validExecutionRules((string) ($task->execution_rules ?? ''))) {
                $state = 'invalid_rules';
            } elseif ($executions > 0 && (int) ($task->last_exit_code ?? 0) !== 0) {
                $state = 'failed';
            } elseif ($next === '' || $next === '0000-00-00 00:00:00') {
                $state = 'unknown';
            } else {
                try {
                    $due = new \DateTimeImmutable($next, new \DateTimeZone('UTC'));
                    $lateBy = $now->getTimestamp() - $due->getTimestamp();
                    $state = $lateBy > 600 ? 'overdue' : ($executions === 0 ? 'waiting' : 'operational');
                } catch (\Exception) {
                    $state = 'unknown';
                }
            }
        }
        return [
            'state' => $state,
            'last_execution' => $last !== '' ? $last : null,
            'next_execution' => $next !== '' ? $next : null,
            'id' => $task ? (int) $task->id : null,
            'duplicates' => $duplicates,
        ];
    }
}
