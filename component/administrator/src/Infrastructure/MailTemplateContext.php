<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Resolves optional, public course context for native Joomla mail templates. */
final class MailTemplateContext
{
    public static function enrich(DatabaseInterface $db, int $courseId, array $data): array
    {
        $query = $db->createQuery()->select('params_json')->from('#__jlms_courses')
            ->where('id = :id')->bind(':id', $courseId, ParameterType::INTEGER);
        $params = $db->setQuery($query)->loadResult();
        $course = is_string($params) ? json_decode($params, true) : null;
        $presentation = is_array($course) && is_array($course['presentation'] ?? null) ? $course['presentation'] : [];
        $calendar = is_array($course) && is_array($course['calendar'] ?? null) ? $course['calendar'] : [];

        $description = is_string($presentation['description'] ?? null) ? $presentation['description'] : '';
        $description = html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = trim(preg_replace('/\s+/u', ' ', $description) ?? '');
        $minutes = filter_var($presentation['estimated_minutes'] ?? null, FILTER_VALIDATE_INT, ['options'
            => ['min_range' => 1]]);
        $start = self::dateValue($calendar['starts_at'] ?? null);
        $mailFrom = (string) Factory::getConfig()->get('mailfrom', '');
        $contact = filter_var($mailFrom, FILTER_VALIDATE_EMAIL) ? $mailFrom : '';

        $enrollmentId = filter_var($data['ENROLLMENT_ID'] ?? null, FILTER_VALIDATE_INT, ['options'
            => ['min_range' => 1]]);
        $lessonId = filter_var($data['LESSON_ID'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $lessonUrl = ($enrollmentId && $lessonId)
            ? Uri::root() . 'index.php?option=com_jlms&view=classroom&id=' . $enrollmentId . '&lesson_id=' . $lessonId
            : '';

        return array_merge($data, [
            'COURSE_DESCRIPTION' => $description,
            'COURSE_DURATION' => $minutes ? $minutes . ' min' : '',
            'COURSE_START_DATE' => $start,
            // Lesson body content is protected learning material, not email metadata.
            'LESSON_DESCRIPTION' => '',
            'LESSON_URL' => $lessonUrl,
            'CONTACT_EMAIL' => $contact,
        ]);
    }

    private static function dateValue(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }
        $value = trim($value);
        // Reject relative and impossible dates. Never invent a calendar start
        // date from the worker's current clock in a learner notification.
        if (preg_match('/\A\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})?)?\z/D', $value) !== 1) {
            return '';
        }
        try {
            $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
            $errors = \DateTimeImmutable::getLastErrors();
            if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                return '';
            }
            return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s') . ' UTC';
        } catch (\Throwable) {
            return '';
        }
    }
}
