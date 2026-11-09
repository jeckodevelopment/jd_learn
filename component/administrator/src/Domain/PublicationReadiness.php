<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Domain;

/** Quick structural checks for the editor; publication remains authoritative. */
final class PublicationReadiness
{
    /**
     * @param list<object> $modules
     * @param list<object> $lessons
     * @param list<object> $attachments
     * @return array{blockers:list<string>,warnings:list<string>}
     */
    public static function inspect(object $course, array $modules, array $lessons, array $attachments = [], bool $scanRequired = false): array
    {
        $blockers = [];
        $warnings = [];

        $params = json_decode((string) ($course->params_json ?? '{}'), true);
        $presentation = is_array($params) && is_array($params['presentation'] ?? null)
            ? $params['presentation']
            : [];
        $description = html_entity_decode(strip_tags((string) ($presentation['description'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_replace('/[\s\p{Z}\p{Cf}]/u', '', $description) === '') {
            $blockers[] = 'COM_JLMS_COURSE_DESCRIPTION_REQUIRED';
        }

        if (!$modules) {
            $blockers[] = 'COM_JLMS_READINESS_MODULES_REQUIRED';
        }

        $moduleCounts = [];
        foreach ($modules as $module) {
            $moduleCounts[(int) $module->id] = 0;
        }

        $required = 0;
        $initialRequired = 0;
        foreach ($lessons as $lesson) {
            $moduleId = (int) $lesson->module_id;
            if (!array_key_exists($moduleId, $moduleCounts)) {
                continue;
            }
            $moduleCounts[$moduleId]++;
            $required += (int) $lesson->required;
            if ((int) $lesson->required === 1 && (int) ($lesson->release_after_days ?? 0) === 0) { $initialRequired++; }

            if (!LessonAvailability::validStored($lesson->publish_up ?? null, $lesson->publish_down ?? null)
                || !LessonAvailability::validReleaseDays($lesson->release_after_days ?? null)) {
                $blockers[] = 'COM_JLMS_LESSON_SCHEDULE_INVALID';
            } elseif (($lesson->publish_up ?? null) !== null || ($lesson->publish_down ?? null) !== null) {
                $warnings[] = 'COM_JLMS_READINESS_LESSON_SCHEDULED';
            }
            if ((int) ($lesson->release_after_days ?? 0) > 0) { $warnings[] = 'COM_JLMS_READINESS_LESSON_DRIP'; }
            if ((int) ($lesson->required ?? 0) === 1 && LessonAvailability::validStored($lesson->publish_up ?? null, $lesson->publish_down ?? null)) {
                $courseWindow = \Jlms\Component\Jlms\Administrator\Application\CourseSchedule::read($course);
                if (!LessonAvailability::reachable($lesson->publish_up ?? null, $lesson->publish_down ?? null, $courseWindow)) {
                    $blockers[] = 'COM_JLMS_LESSON_UNREACHABLE';
                }
            }

            if (($lesson->kind ?? '') === 'document') {
                $linked = false;
                foreach ($attachments as $attachment) {
                    if ((int) ($attachment->lesson_id ?? 0) === (int) $lesson->id && ($attachment->status ?? '') === 'ready'
                        && (int) ($attachment->state ?? 0) === 1 && (int) ($attachment->preview ?? 0) === 0) {
                        $linked = true;
                        break;
                    }
                }
                if (!$linked) { $blockers[] = 'COM_JLMS_DOCUMENT_MATERIAL_REQUIRED'; }
            } elseif (($lesson->kind ?? '') === 'audio') {
                $linked = false;
                foreach ($attachments as $attachment) {
                    if ((int) ($attachment->lesson_id ?? 0) === (int) $lesson->id && ($attachment->status ?? '') === 'ready'
                        && in_array(($attachment->mime_type ?? ''), ['audio/mpeg', 'audio/ogg'], true)
                        && (int) ($attachment->state ?? 0) === 1 && (int) ($attachment->preview ?? 0) === 0) { $linked = true; break; }
                }
                if (!$linked) { $blockers[] = 'COM_JLMS_AUDIO_MATERIAL_REQUIRED'; }
            } elseif (($lesson->kind ?? '') === 'video') {
                $lessonParams = json_decode((string) ($lesson->params_json ?? '{}'), true);
                $duration = (int) ($lesson->duration_ms ?? 0);
                if ($duration < 1000 || $duration > 28800000
                    || !is_array($lessonParams) || ($lessonParams['video_duration_confirmed'] ?? false) !== true) {
                    $blockers[] = 'COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED';
                }
            }
        }

        if ($required > 0 && $initialRequired === 0) {
            $blockers[] = 'COM_JLMS_REQUIRED_INITIAL_LESSON_REQUIRED';
        }

        foreach ($attachments as $attachment) {
            $scan = json_decode((string) ($attachment->scan_json ?? '{}'), true);
            if (($scanRequired && (!is_array($scan) || ($scan['engine'] ?? '') !== 'clamd' || ($scan['result'] ?? '') !== 'clean'))
                || ($attachment->status ?? '') !== 'ready' || (int) ($attachment->preview ?? 0) !== 0
                || !MaterialAvailability::validStored($attachment->publish_up ?? null, $attachment->publish_down ?? null)) {
                $blockers[] = 'COM_JLMS_MATERIAL_NOT_READY';
            }
            if (($attachment->publish_up ?? null) !== null || ($attachment->publish_down ?? null) !== null) {
                $warnings[] = 'COM_JLMS_READINESS_MATERIAL_SCHEDULED';
            }
        }

        $warnings = array_values(array_unique($warnings));

        foreach ($moduleCounts as $count) {
            if ($count === 0) {
                $blockers[] = 'COM_JLMS_READINESS_MODULE_EMPTY';
                break;
            }
        }
        if ($required === 0) {
            $blockers[] = 'COM_JLMS_READINESS_REQUIRED_LESSON';
        }

        if (trim((string) ($course->summary ?? '')) === '') {
            $warnings[] = 'COM_JLMS_READINESS_SUMMARY_MISSING';
        }
        if (trim((string) ($presentation['image'] ?? '')) === '') {
            $warnings[] = 'COM_JLMS_READINESS_IMAGE_MISSING';
        }

        return ['blockers' => array_values(array_unique($blockers)), 'warnings' => $warnings];
    }
}
