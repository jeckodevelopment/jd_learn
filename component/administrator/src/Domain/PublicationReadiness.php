<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Domain;

use Jlms\Component\Jlms\Administrator\Application\CoursePresentation;

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

        try {
            CoursePresentation::requireDescription($course);
        } catch (\DomainException $error) {
            $blockers[] = $error->getMessage();
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
        $details = CoursePresentation::details($course->params_json ?? null) ?? [];
        if (trim((string) ($details['image'] ?? '')) === '') {
            $warnings[] = 'COM_JLMS_READINESS_IMAGE_MISSING';
        }

        return ['blockers' => array_values(array_unique($blockers)), 'warnings' => $warnings];
    }
}
