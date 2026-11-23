<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/** One authoritative lookup for the JD Learn mail scheduler job. */
final class MailSchedulerSetup
{
    public const TASK_TYPE = 'jlms.mail.dispatch';

    public static function status(DatabaseInterface $db): array
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'state', 'execution_rules', 'last_execution', 'last_exit_code', 'times_executed', 'times_failed']))
            ->from($db->quoteName('#__scheduler_tasks'))
            ->where($db->quoteName('type') . ' = ' . $db->quote(self::TASK_TYPE))
            ->order($db->quoteName('id') . ' ASC');
        $tasks = $db->setQuery($query)->loadObjectList();
        $query = $db->getQuery(true)->select($db->quoteName('enabled'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('task'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('jlms'));
        $pluginEnabled = (int) $db->setQuery($query)->loadResult() === 1;
        $task = $tasks[0] ?? null;
        $rules = $task ? json_decode((string) $task->execution_rules, true) : [];
        $rules = is_array($rules) ? $rules : [];
        $interval = ($rules['rule-type'] ?? '') === 'interval-minutes'
            ? (int) ($rules['interval-minutes'] ?? 0) : null;
        $state = !$task ? 'missing' : (!$pluginEnabled || (int) $task->state !== 1 ? 'disabled'
            : ((int) ($task->times_executed ?? 0) + (int) ($task->times_failed ?? 0) === 0 ? 'waiting'
                : ((int) ($task->last_exit_code ?? 0) !== 0 ? 'failed' : 'operational')));
        return [
            'state' => $state,
            'id' => $task ? (int) $task->id : null,
            'interval' => $interval,
            'last_execution' => $task->last_execution ?? null,
            'duplicates' => count($tasks) > 1,
            'plugin_enabled' => $pluginEnabled,
        ];
    }

    /** Idempotent: never alter an existing task's operator-managed configuration. */
    public static function ensureDefault(DatabaseInterface $db): void
    {
        $query = $db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__scheduler_tasks'))
            ->where($db->quoteName('type') . ' = ' . $db->quote(self::TASK_TYPE));
        if ((int) $db->setQuery($query)->loadResult() !== 0) {
            return;
        }
        $component = Factory::getApplication()->bootComponent('com_scheduler');
        $model = $component->getMVCFactory()->createModel('Task', 'Administrator', ['ignore_request' => true]);
        if (
            !$model->save([
            'title' => 'JD Learn: invia email in coda',
            'type' => self::TASK_TYPE,
            'state' => 0,
            'execution_rules' => [
                'rule-type' => 'interval-minutes',
                'interval-minutes' => 5,
                'exec-time' => gmdate('H:i'),
                'exec-day' => gmdate('d'),
            ],
            'params' => ['limit' => 100],
            ])
        ) {
            throw new \RuntimeException('JD Learn mail task creation failed: ' . $model->getError());
        }
    }
}
