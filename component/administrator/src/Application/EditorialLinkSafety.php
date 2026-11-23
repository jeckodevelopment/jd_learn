<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

/**
 * Secure outgoing links in HTML already filtered by Joomla.
 * Never use this helper as a replacement for the HTML allowlist.
 */
final class EditorialLinkSafety
{

    /** Flag credential-bearing query or fragment keys in any public URL. */
    public static function containsSensitiveParameters(string $url): bool
    {
        $parts = parse_url($url);
        if ($parts === false) { return true; }
        foreach (['query', 'fragment'] as $field) {
            parse_str(ltrim((string) ($parts[$field] ?? ''), '#?'), $parameters);
            foreach (array_keys($parameters) as $key) {
                $normal = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '_',
                    preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', (string) $key)));
                if (preg_match('/(?:^|_)(?:token|secret|password|passwd|credential|authorization|auth|api_key|apikey|client_secret|access_key|private_key|signature|signed|jwt)(?:$|_)/D', $normal)) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function secure(string $filteredHtml): string
    {
        return preg_replace_callback('/<a\b[^>]*>/i', static function (array $match): string {
            // Never trust editor-provided link relationship attributes.
            $tag = preg_replace('/\s+rel\s*=\s*(["\']).*?\1/is', '', $match[0]) ?? '<a>';
            if (preg_match('/\bhref\s*=\s*(["\'])(.*?)\1/is', $tag, $href)) {
                $url = html_entity_decode($href[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $parts = parse_url($url);
                $sensitive = $parts === false || isset($parts['user']) || isset($parts['pass']);
                $sensitive = $sensitive || self::containsSensitiveParameters($url);
                if ($sensitive) {
                    $tag = preg_replace('/\s+href\s*=\s*(["\']).*?\1/is', '', $tag) ?? '<a>';
                }
            }
            return preg_replace('/^<a(?=[\s>])/i', '<a rel="noreferrer noopener"', $tag) ?? '<a>';
        }, $filteredHtml) ?? '';
    }
}
