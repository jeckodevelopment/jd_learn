<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Domain;

/** Browser declarations, not proof of attention. Server time caps cumulative credit per session. */
final class VideoTelemetry
{
    public static function consume(array $state, array $events, int $duration, int $nowMs): array
    {
        if ($duration < 1000 || $duration > 28800000 || !array_is_list($events) || !$events || count($events) > 100) {
            throw new \InvalidArgumentException('COM_JLMS_VIDEO_EVENTS_INVALID');
        }
        $intervals = [];
        $issued = $state['issued_ms'];
        $spent = $state['spent_wall_ms'] ?? 0;
        foreach ($events as $event) {
            if (!is_array($event) || count($event) !== 6 || !is_int($event['id'] ?? null) || !is_string($event['type'] ?? null)
                || !is_int($event['position_ms'] ?? null) || !is_int($event['client_ms'] ?? null)
                || !is_int($event['rate_milli'] ?? null) || !is_bool($event['playing'] ?? null)
                || !in_array($event['type'], ['play', 'position', 'pause', 'seeking', 'seeked', 'ended', 'buffering', 'ratechange'], true)
                || $event['id'] <= ($state['last_event_id'] ?? 0) || $event['position_ms'] < 0 || $event['position_ms'] > $duration
                || $event['client_ms'] < ($state['client_ms'] ?? 0) || $event['client_ms'] > 86400000
                || !in_array($event['rate_milli'], [250, 500, 750, 1000, 1250, 1500, 1750, 2000], true)) {
                throw new \InvalidArgumentException('COM_JLMS_VIDEO_EVENTS_INVALID');
            }
            if (($state['playing'] ?? false) && isset($state['position_ms'], $state['client_ms'])
                && in_array($event['type'], ['position', 'pause', 'ended'], true)) {
                $elapsed = $event['client_ms'] - $state['client_ms'];
                $distance = $event['position_ms'] - $state['position_ms'];
                $expected = $elapsed * $state['rate_milli'] / 1000;
                $cost = $distance > 0 ? (int) ceil($distance * 1000 / $state['rate_milli']) : 0;
                if ($elapsed > 0 && $elapsed <= 30000 && $distance > 0 && abs($distance - $expected) <= max(250, $expected * 0.02)
                    && $spent + $cost <= max(0, $nowMs - $issued) + 2000) {
                    $intervals[] = [$state['position_ms'], $event['position_ms']];
                    $spent += $cost;
                }
            }
            $state['last_event_id'] = $event['id'];
            $state['position_ms'] = $event['position_ms'];
            $state['client_ms'] = $event['client_ms'];
            $state['rate_milli'] = $event['rate_milli'];
            $state['playing'] = $event['playing'] && !in_array($event['type'], ['seeking', 'seeked', 'buffering', 'pause', 'ended'], true);
        }
        $state['spent_wall_ms'] = $spent;
        return ['state' => $state, 'intervals' => $intervals, 'resume_ms' => $state['position_ms']];
    }
}
