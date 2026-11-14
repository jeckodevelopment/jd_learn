<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

/**
 * Read-only canonical mail placeholder catalog.
 *
 * This describes tags actually registered by JD Learn; approved future tags
 * must not appear here until their event producer supplies them.
 */
final class MailTemplateVariableCatalog
{
    private const DEFINITIONS = [
        'USER_FULLNAME' => ['text', 'Nome completo del destinatario', 'Recipient full name'],
        'USER_FULLNAME_HTML' => ['html-escaped', 'Nome destinatario per HTML', 'HTML-escaped recipient name'],
        'COURSE_TITLE' => ['text', 'Titolo del corso', 'Course title'],
        'COURSE_TITLE_HTML' => ['html-escaped', 'Titolo corso per HTML', 'HTML-escaped course title'],
        'SITE_NAME' => ['text', 'Nome del sito', 'Site name'],
        'SITE_NAME_HTML' => ['html-escaped', 'Nome del sito per HTML', 'HTML-escaped site name'],
        'COURSE_URL' => ['url', 'Collegamento al corso', 'Course link'],
        'COURSE_ENDS_AT' => ['date', 'Data di scadenza del corso', 'Course expiry date'],
        'CERTIFICATE_URL' => ['url', 'Collegamento all’area attestati autenticata', 'Authenticated certificate area link'],
        'MESSAGE_SUBJECT' => ['text', 'Oggetto della comunicazione manuale', 'Manual message subject'],
        'MESSAGE_BODY_TEXT' => ['text', 'Messaggio manuale in testo semplice', 'Plain-text manual message'],
        'MESSAGE_BODY_HTML' => ['html-fragment', 'Corpo HTML sanitizzato della comunicazione', 'Sanitized HTML message body'],
        'COURSE_IMAGE_HTML' => ['html-fragment', 'Immagine pubblica opzionale del corso', 'Optional public course image'],
        'COURSE_INTRO_TEXT' => ['text', 'Introduzione testuale pubblica del corso', 'Public course introduction text'],
        'COURSE_INTRO_HTML' => ['html-fragment', 'Introduzione HTML sanitizzata del corso', 'Sanitized public course introduction'],
        'COURSE_LESSONS_TEXT' => ['text', 'Elenco testuale delle lezioni pubbliche', 'Plain-text public lessons list'],
        'COURSE_LESSONS_HTML' => ['html-fragment', 'Elenco HTML delle lezioni pubbliche', 'HTML public lessons list'],
        'UNSUBSCRIBE_URL' => ['url', 'Collegamento di revoca del consenso', 'Consent unsubscribe link'],
        'UNSUBSCRIBE_URL_HTML' => ['html-escaped-url', 'Collegamento di revoca per attributo HTML', 'HTML-escaped unsubscribe link'],
        'UNSUBSCRIBE_URL_TEXT' => ['url', 'Collegamento testuale di revoca', 'Plain-text unsubscribe link'],
        'CONFIRM_URL' => ['url', 'Collegamento di conferma adesione', 'Consent confirmation link'],
    ];

    /** @return array<string, array{type: string, description: string}> */
    public static function forTemplate(string $templateId, string $language = 'it-IT'): array
    {
        $result = [];
        foreach (MailTemplateInstaller::tagsFor($templateId) as $tag) {
            if (!isset(self::DEFINITIONS[$tag])) {
                throw new \LogicException('Undocumented JD Learn mail token: ' . $tag);
            }
            [$type, $it, $en] = self::DEFINITIONS[$tag];
            $result[$tag] = ['type' => $type, 'description' => $language === 'it-IT' ? $it : $en];
        }
        return $result;
    }
}
