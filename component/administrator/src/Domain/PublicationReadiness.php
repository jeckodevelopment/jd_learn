<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Domain;

/** Quick structural checks for the editor; publication remains authoritative. */
final class PublicationReadiness
{
    /**
     * @param list<object> $modules
     * @param list<object> $lessons
     * @return array{blockers:list<string>,warnings:list<string>}
     */
    public static function inspect(object $course, array $modules, array $lessons): array
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
        foreach ($lessons as $lesson) {
            $moduleId = (int) $lesson->module_id;
            if (!array_key_exists($moduleId, $moduleCounts)) {
                continue;
            }
            $moduleCounts[$moduleId]++;
            $required += (int) $lesson->required;
        }

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
