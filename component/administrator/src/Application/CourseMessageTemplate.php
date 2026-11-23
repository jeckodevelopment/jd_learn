<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplatePlaceholderValidator;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateInstaller;

/**
 * A per-communication snapshot of Joomla's canonical mail template.
 * Never changes #__mail_templates or broadens the canonical tag catalog.
 */
final class CourseMessageTemplate
{
    public const TEMPLATE_ID = 'com_jlms.course.message';

    /** @return array{subject:string,body:string,htmlbody:string} */
    public static function load(string $language = '*'): array
    {
        $template = MailTemplate::getTemplate(self::TEMPLATE_ID, $language)
            ?? MailTemplate::getTemplate(self::TEMPLATE_ID, '*');
        if ($template === null) {
            throw new \RuntimeException('COM_JLMS_COMMUNICATION_TEMPLATE_MISSING', 422);
        }

        return [
            'subject' => (string) $template->subject,
            'body' => (string) $template->body,
            'htmlbody' => (string) $template->htmlbody,
        ];
    }

    /**
     * Upgrade only the editable copy. Joomla preserves the content of old
     * rows, including localized overrides, when tag metadata is synchronized.
     *
     * @return array{subject:string,body:string,htmlbody:string}
     */
    public static function forComposition(string $language = '*'): array
    {
        $source = self::load($language);
        $replacements = [
            '{NAME}' => '{USER_FULLNAME}',
            '{NAME_HTML}' => '{USER_FULLNAME_HTML}',
            '{COURSE}' => '{COURSE_TITLE}',
            '{COURSE_HTML}' => '{COURSE_TITLE_HTML}',
            '{SITENAME}' => '{SITE_NAME}',
            '{SITENAME_HTML}' => '{SITE_NAME_HTML}',
            '{MESSAGE_SUBJECT}' => Text::_('COM_JLMS_COMMUNICATION_DEFAULT_SUBJECT'),
            '{MESSAGE_BODY_TEXT}' => Text::_('COM_JLMS_COMMUNICATION_DEFAULT_BODY'),
            '{MESSAGE_BODY_HTML}' => Text::_('COM_JLMS_COMMUNICATION_DEFAULT_BODY'),
        ];

        return array_map(static fn (string $part): string => strtr($part, $replacements), $source);
    }

    /**
     * Derive the text alternative from the same edited HTML source.
     * Filter markup first so hidden scripts never become plaintext content.
     */
    public static function plainTextFromHtml(string $html): string
    {
        // Script/style contents are not part of the readable email alternative.
        $html = preg_replace('~<(script|style)\\b[^>]*>.*?</\\1\\s*>~is', '', $html) ?? $html;
        $safe = (string) InputFilter::getInstance(
            [],
            [],
            InputFilter::ONLY_BLOCK_DEFINED_TAGS,
            InputFilter::ONLY_BLOCK_DEFINED_ATTRIBUTES
        )->clean($html, 'html');
        $withLines = preg_replace('~<br\s*/?>|</(?:p|div|li|h[1-6])>~i', "\n", $safe) ?? $safe;
        $text = html_entity_decode(strip_tags($withLines), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(strtr($text, [
            '{USER_FULLNAME_HTML}' => '{USER_FULLNAME}',
            '{COURSE_TITLE_HTML}' => '{COURSE_TITLE}',
            '{SITE_NAME_HTML}' => '{SITE_NAME}',
        ]));
    }

    /**
     * Validate a single-message copy without persisting or changing the Joomla template.
     * The HTML sanitizer does not trust arbitrary administrator-entered markup.
     *
     * @param array<string, mixed> $fields
     * @param array<string, scalar> $data Recipient-specific placeholders to validate against.
     * @return array{subject:string,body:string,htmlbody:string}
     */
    public static function validate(array $fields, array $data): array
    {
        foreach (['subject', 'body', 'htmlbody'] as $key) {
            if (!isset($fields[$key]) || !is_string($fields[$key])) {
                throw new \RuntimeException('COM_JLMS_COMMUNICATION_TEMPLATE_INVALID', 422);
            }
        }

        $subject = trim($fields['subject']);
        $body = trim($fields['body']);
        $htmlbody = trim($fields['htmlbody']);
        if (
            $subject === '' || mb_strlen($subject) > 300
            || preg_match('/[\x00-\x1f\x7f]/', $subject)
            || $body === '' || mb_strlen($body) > 16000
            || mb_strlen($htmlbody) > 32000
        ) {
            throw new \RuntimeException('COM_JLMS_COMMUNICATION_TEMPLATE_INVALID', 422);
        }

        $htmlbody = (string) InputFilter::getInstance([], [], InputFilter::ONLY_BLOCK_DEFINED_TAGS, InputFilter::ONLY_BLOCK_DEFINED_ATTRIBUTES)->clean($htmlbody, 'html');
        // These legacy slots referred to a separate free-message editor. In
        // single-editor mode they would reference their own field and must not
        // silently render recursively or leak raw placeholders to recipients.
        foreach ([$subject, $body, $htmlbody] as $part) {
            if (preg_match('/\\{MESSAGE_(?:SUBJECT|BODY_TEXT|BODY_HTML)\\}/', $part)) {
                throw new \RuntimeException('COM_JLMS_COMMUNICATION_TEMPLATE_LEGACY_SLOT', 422);
            }
        }
        $missing = MailTemplatePlaceholderValidator::unresolved(
            self::TEMPLATE_ID,
            $data,
            $subject,
            $body,
            $htmlbody
        );
        if ($missing !== []) {
            throw new CourseMessageVariableException($missing);
        }

        return ['subject' => $subject, 'body' => $body, 'htmlbody' => $htmlbody];
    }

    /** @return list<string> */
    public static function tags(): array
    {
        return array_values(array_diff(MailTemplateInstaller::tagsFor(self::TEMPLATE_ID), [
            'MESSAGE_SUBJECT', 'MESSAGE_BODY_TEXT', 'MESSAGE_BODY_HTML',
        ]));
    }
}
