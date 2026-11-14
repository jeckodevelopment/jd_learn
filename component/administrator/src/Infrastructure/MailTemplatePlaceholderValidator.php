<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

/**
 * Checks customized Joomla Mail Templates before SMTP dispatch.
 *
 * Only checks JD Learn-style {UPPER_SNAKE_CASE} tokens: Joomla allows other
 * text and translation constructs. No sensitive values are logged.
 */
final class MailTemplatePlaceholderValidator
{
    /** @param array<string, mixed> $data
     *  @return array<int, string> Sorted, distinct unresolved token names.
     */
    public static function unresolved(string $templateId, array $data, string ...$parts): array
    {
        $allowed = array_fill_keys(MailTemplateInstaller::tagsFor($templateId), true);
        $missing = [];
        foreach ($parts as $part) {
            preg_match_all('/\\{([A-Z][A-Z0-9_]*)\\}/', $part, $matches);
            foreach ($matches[1] as $name) {
                if (!isset($allowed[$name]) || !array_key_exists($name, $data) || !is_scalar($data[$name])) {
                    $missing[$name] = true;
                }
            }
        }
        $names = array_keys($missing);
        sort($names, SORT_STRING);
        return $names;
    }
}
