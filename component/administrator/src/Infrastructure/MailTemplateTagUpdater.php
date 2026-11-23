<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Reconciles the list of available tags on already-installed Joomla Mail Templates.
 * Never edits the administrator's subject, plain text, HTML or other mail preferences.
 */
final class MailTemplateTagUpdater
{
    private const OBSOLETE = [
        'NAME', 'COURSE', 'SITENAME', 'NAME_HTML', 'COURSE_HTML', 'SITENAME_HTML',
    ];

    /** Replace only exact legacy placeholders, leaving all editorial content intact. */
    public static function migratePlaceholders(string $content): string
    {
        return strtr($content, [
            '{NAME}' => '{USER_FULLNAME}',
            '{COURSE}' => '{COURSE_TITLE}',
            '{SITENAME}' => '{SITE_NAME}',
            '{NAME_HTML}' => '{USER_FULLNAME_HTML}',
            '{COURSE_HTML}' => '{COURSE_TITLE_HTML}',
            '{SITENAME_HTML}' => '{SITE_NAME_HTML}',
        ]);
    }

    /**
     * @param array<string, mixed> $params Decoded Joomla mail template preferences.
     * @param list<string> $canonicalTags Supported JD Learn placeholders.
     * @return array<string, mixed>
     */
    public static function mergeParams(array $params, array $canonicalTags): array
    {
        $existing = $params['tags'] ?? [];
        if (!is_array($existing)) {
            throw new \UnexpectedValueException('Invalid JD Learn mail template tag metadata.');
        }
        $tags = [];
        foreach (array_merge($existing, $canonicalTags) as $tag) {
            if (!is_string($tag) || $tag === '' || in_array($tag, self::OBSOLETE, true)) {
                continue;
            }
            $tags[$tag] = true;
        }
        $params['tags'] = array_keys($tags);
        return $params;
    }

    /**
     * Update only the tags key in params for every default/translated Joomla row.
     * Other keys, text, language, sender settings, format and attachments are retained.
     */
    public static function synchronize(DatabaseInterface $db): int
    {
        $updated = 0;
        foreach (MailTemplateInstaller::templateIds() as $templateId) {
            $q = $db->createQuery()->select(['template_id', 'language', 'params', 'subject', 'body', 'htmlbody'])
                ->from('#__mail_templates')->where('template_id = :id')
                ->bind(':id', $templateId, ParameterType::STRING);
            $rows = $db->setQuery($q)->loadObjectList();
            foreach ($rows as $row) {
                try {
                    $params = json_decode((string) $row->params, true, 64, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    // Fail safely instead of destroying existing administrator preferences.
                    throw new \RuntimeException('JD Learn mail template metadata is invalid; upgrade did not replace it.', 0, $e);
                }
                if (!is_array($params)) {
                    throw new \RuntimeException('JD Learn mail template preferences must be an object.');
                }
                $merged = self::mergeParams($params, MailTemplateInstaller::tagsFor($templateId));
                $updatedTemplate = (object) [
                    'template_id' => (string) $row->template_id,
                    'language' => (string) $row->language,
                ];
                if ($params !== $merged) {
                    $updatedTemplate->params = json_encode($merged, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                }
                foreach (['subject', 'body', 'htmlbody'] as $field) {
                    $old = (string) ($row->{$field} ?? '');
                    $new = self::migratePlaceholders($old);
                    if ($new !== $old) {
                        $updatedTemplate->{$field} = $new;
                    }
                }
                if (count(get_object_vars($updatedTemplate)) === 2) {
                    continue;
                }
                $db->updateObject('#__mail_templates', $updatedTemplate, ['template_id', 'language']);
                ++$updated;
            }
        }
        return $updated;
    }
}
