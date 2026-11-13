<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

/** Validated global and per-course switches for operational learner emails. */
final class MailPreferences
{
    public const EVENTS = ['enrollment', 'completion', 'suspension', 'reactivation', 'certificate', 'communication', 'announcement'];

    public static function merge(array $input, ?string $params): ?string
    {
        $preferences = [];
        foreach (self::EVENTS as $event) {
            $field = 'mail_' . $event;
            if (array_key_exists($field, $input)) {
                $value = $input[$field];
                if (!is_string($value) || !in_array($value, ['inherit', 'enabled', 'disabled'], true)) {
                    throw new \InvalidArgumentException('COM_JLMS_MAIL_PREFERENCE_INVALID');
                }
                $preferences[$event] = $value;
            }
        }
        if (!$preferences) {
            return null;
        }

        $data = json_decode($params ?: '{}', true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('COM_JLMS_MAIL_PREFERENCE_INVALID');
        }
        $current = $data['mail_preferences'] ?? [];
        if (!is_array($current)) {
            throw new \InvalidArgumentException('COM_JLMS_MAIL_PREFERENCE_INVALID');
        }
        foreach ($preferences as $event => $value) {
            if ($value === 'inherit') {
                unset($current[$event]);
            } else {
                $current[$event] = $value;
            }
        }
        if ($current) {
            $data['mail_preferences'] = $current;
        } else {
            unset($data['mail_preferences']);
        }

        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public static function enabled(string $event, bool $globalEnabled, ?string $courseParams): bool
    {
        if (!in_array($event, self::EVENTS, true)) {
            return false;
        }
        $data = json_decode($courseParams ?: '{}', true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            return false;
        }
        $value = $data['mail_preferences'][$event] ?? 'inherit';
        return match ($value) {
            'enabled' => true,
            'disabled' => false,
            'inherit' => $globalEnabled,
            default => false,
        };
    }
}
