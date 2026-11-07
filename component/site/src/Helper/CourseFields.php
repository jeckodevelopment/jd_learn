<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Helper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

/** Current editorial fields only; never a source of assigned teaching rules. */
final class CourseFields
{
    public static function prepare(object $course, string $language): array
    {
        $positions = [1 => [], 2 => [], 3 => []];
        if (!PluginHelper::isEnabled('system', 'fields')
            || !ComponentHelper::getParams('com_jlms')->get('custom_fields_enable', 1)) {
            return $positions;
        }
        // The native helper applies publication, field/group access, category and
        // installed field-plugin preparation. Keep definitions and values in core.
        foreach (FieldsHelper::getFields('com_jlms.course', $course, true) as $field) {
            $position = (int) $field->params->get('display', 2);
            $fieldLanguage = (string) ($field->language ?? '*');
            $itemLanguage = $course->language !== '*' ? $course->language : $language;
            if (!isset($positions[$position]) || !in_array($fieldLanguage, ['*', $itemLanguage], true)) {
                continue;
            }
            $positions[$position][] = $field;
        }
        return $positions;
    }
}
