<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Filter\InputFilter;
use Jlms\Component\Jlms\Administrator\Domain\DecimalId;

/** Promotional data only. Never receives lesson bodies, provider configuration or learner data. */
final class CoursePresentation
{
    public const FIELDS = ['description', 'objectives', 'audience', 'prerequisite_text', 'difficulty', 'estimated_minutes', 'image', 'image_alt', 'image_decorative', 'seo_title', 'seo_description'];

    public static function html(string $html): string
    {
        return InputFilter::getInstance(
            ['p', 'br', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'h2', 'h3', 'h4', 'blockquote', 'a', 'span'],
            ['href', 'title', 'lang', 'dir'], 0, 0, 1,
        )->clean($html, 'html');
    }

    private static function normalise(array $values): array
    {
        $result = ['_schema' => 1];
        foreach (self::FIELDS as $field) {
            $value = $values[$field] ?? '';
            if ($field === 'image_decorative') {
                if (!in_array($value, ['', '0', '1', 0, 1, false, true], true)) {
                    throw new \InvalidArgumentException('COM_JLMS_PRESENTATION_INVALID');
                }
                $result[$field] = in_array($value, ['1', 1, true], true);
                continue;
            }
            if ($field === 'estimated_minutes') {
                if ($value === '' || $value === null) { $result[$field] = null; continue; }
                if (!is_int($value) && !is_string($value)) { throw new \InvalidArgumentException('COM_JLMS_PRESENTATION_INVALID'); }
                try { $result[$field] = DecimalId::parse((string) $value); }
                catch (\InvalidArgumentException) { throw new \InvalidArgumentException('COM_JLMS_PRESENTATION_INVALID'); }
                continue;
            }
            if (!is_string($value) || preg_match('//u', $value) !== 1
                || strlen($value) > ($field === 'description' ? 1048576 : 65535)) {
                throw new \InvalidArgumentException('COM_JLMS_PRESENTATION_INVALID');
            }
            if ($field === 'difficulty' && !in_array($value, ['', 'beginner', 'intermediate', 'advanced'], true)) {
                throw new \InvalidArgumentException('COM_JLMS_PRESENTATION_INVALID');
            }
            if (in_array($field, ['image_alt', 'seo_title', 'seo_description'], true)
                && (mb_strlen($value, 'UTF-8') > ($field === 'seo_title' ? 255 : 500)
                    || preg_match('/[\x00-\x1f\x7f]/', $value)
                    || ($field !== 'image_alt' && preg_match('/[<>]/', $value)))) {
                throw new \InvalidArgumentException('COM_JLMS_PRESENTATION_INVALID');
            }
            $result[$field] = $field === 'description' ? self::html($value) : trim($value);
            if ($field === 'image') { $result[$field] = CourseArtwork::path($value); }
        }
        if ($result['image'] !== '' && !$result['image_decorative'] && $result['image_alt'] === '') {
            throw new \InvalidArgumentException('COM_JLMS_COURSE_IMAGE_ALT_REQUIRED');
        }
        if ($result['image_decorative']) { $result['image_alt'] = ''; }
        return $result;
    }

    public static function details(?string $params): ?array
    {
        $data = json_decode($params ?: '{}', true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data)) { throw new \InvalidArgumentException('COM_JLMS_PRESENTATION_INVALID'); }
        if (!array_key_exists('presentation', $data)) { return null; }
        if (!is_array($data['presentation']) || ($data['presentation']['_schema'] ?? null) !== 1) {
            throw new \InvalidArgumentException('COM_JLMS_PRESENTATION_INVALID');
        }
        return self::normalise($data['presentation']);
    }

    /** Omitted fields retain their existing values. Raw params_json is never an accepted form input. */
    public static function merge(array $input, ?string $params, callable $filterHtml): ?string
    {
        $values = self::details($params) ?? [];
        $changed = false;
        foreach (self::FIELDS as $field) {
            $name = 'presentation_' . $field;
            if (array_key_exists($name, $input)) { $values[$field] = $input[$name]; $changed = true; }
        }
        if (!$changed) { return null; }
        // Validate types and lengths before invoking Joomla's actor-specific HTML policy.
        $values = self::normalise($values);
        $values['description'] = self::html($filterHtml($values['description']));
        CourseArtwork::validateLocal($values['image']);
        $data = json_decode($params ?: '{}', true, 32, JSON_THROW_ON_ERROR);
        $data['presentation'] = $values;
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public static function requireDescription(object $course): void
    {
        $details = self::details($course->params_json);
        $description = $details['description'] ?? '';
        $text = html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_replace('/[\\s\\p{Z}\\p{Cf}]/u', '', $text) === '') {
            throw new \DomainException('COM_JLMS_COURSE_DESCRIPTION_REQUIRED');
        }
    }

    public static function snapshot(object $course): string
    {
        $snapshot = ['_schema' => 1, 'summary' => $course->summary];
        $details = self::details($course->params_json);
        if ($details !== null) {
            CourseArtwork::validateLocal($details['image']);
            $snapshot['details'] = $details;
        }
        $calendar=CourseSchedule::snapshot($course);
        if ($calendar!==null) { $snapshot['calendar']=$calendar; }
        return json_encode($snapshot, JSON_THROW_ON_ERROR);
    }

    public static function fromSnapshot(string $json): array
    {
        $snapshot = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($snapshot) || ($snapshot['_schema'] ?? null) !== 1) {
            throw new \InvalidArgumentException('COM_JLMS_PRESENTATION_INVALID');
        }
        if (!array_key_exists('details', $snapshot)) { return []; }
        if (!is_array($snapshot['details']) || ($snapshot['details']['_schema'] ?? null) !== 1) {
            throw new \InvalidArgumentException('COM_JLMS_PRESENTATION_INVALID');
        }
        return self::normalise($snapshot['details']);
    }
}
