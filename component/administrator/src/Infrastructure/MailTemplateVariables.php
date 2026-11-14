<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

/**
 * Compatibility aliases for JD Learn mail template placeholders.
 *
 * Never interprets HTML or invents missing recipient data. Escaping and URL
 * policy remain the responsibility of each event-specific renderer.
 * Existing values always win, preserving templates and queued payloads.
 */
final class MailTemplateVariables
{
    private const PAIRS = [
        ['COURSE', 'COURSE_TITLE'],
        ['SITENAME', 'SITE_NAME'],
        ['NAME', 'USER_FULLNAME'],
        ['NAME_HTML', 'USER_FULLNAME_HTML'],
        ['COURSE_HTML', 'COURSE_TITLE_HTML'],
        ['SITENAME_HTML', 'SITE_NAME_HTML'],
    ];

    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    public static function withAliases(array $data): array
    {
        foreach (self::PAIRS as [$legacy, $canonical]) {
            if (array_key_exists($legacy, $data) && !array_key_exists($canonical, $data)) {
                $data[$canonical] = $data[$legacy];
            } elseif (array_key_exists($canonical, $data) && !array_key_exists($legacy, $data)) {
                $data[$legacy] = $data[$canonical];
            }
        }

        return $data;
    }
}
