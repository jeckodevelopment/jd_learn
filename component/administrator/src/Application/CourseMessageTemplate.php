<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\Filter\InputFilter;
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
        if ($subject === '' || mb_strlen($subject) > 300
            || preg_match('/[\x00-\x1f\x7f]/', $subject)
            || $body === '' || mb_strlen($body) > 16000
            || mb_strlen($htmlbody) > 32000
        ) {
            throw new \RuntimeException('COM_JLMS_COMMUNICATION_TEMPLATE_INVALID', 422);
        }

        $htmlbody = (string) InputFilter::getInstance([], [], InputFilter::ONLY_BLOCK_DEFINED_TAGS, InputFilter::ONLY_BLOCK_DEFINED_ATTRIBUTES)->clean($htmlbody, 'html');
        $missing = MailTemplatePlaceholderValidator::unresolved(
            self::TEMPLATE_ID, $data, $subject, $body, $htmlbody
        );
        if ($missing !== []) {
            throw new \RuntimeException('COM_JLMS_COMMUNICATION_TEMPLATE_VARIABLES', 422);
        }

        return ['subject' => $subject, 'body' => $body, 'htmlbody' => $htmlbody];
    }

    /** @return list<string> */
    public static function tags(): array
    {
        return MailTemplateInstaller::tagsFor(self::TEMPLATE_ID);
    }
}
