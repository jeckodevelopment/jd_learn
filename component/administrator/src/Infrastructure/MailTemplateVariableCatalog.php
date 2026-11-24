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
        'LESSON_TITLE' => ['text', 'Titolo della lezione resa disponibile', 'Title of the newly available lesson'],
        'LESSON_TITLE_HTML' => ['html-escaped', 'Titolo lezione per HTML', 'HTML-escaped lesson title'],
        'COURSE_TITLE' => ['text', 'Titolo del corso', 'Course title'],
        'COURSE_TITLE_HTML' => ['html-escaped', 'Titolo corso per HTML', 'HTML-escaped course title'],
        'COURSE_DESCRIPTION' => ['text', 'Descrizione editoriale pubblica del corso',
            'Public editorial course description'],
        'COURSE_DURATION' => ['text', 'Durata stimata del corso se configurata',
            'Estimated course duration when configured'],
        'COURSE_START_DATE' => ['date', 'Data UTC di inizio configurata per il corso',
            'Configured course start date in UTC'],
        'LESSON_DESCRIPTION' => ['text', 'Descrizione autonoma della lezione, se disponibile e sicura',
            'Standalone lesson description when available and safe'],
        'LESSON_URL' => ['url', 'Collegamento autenticato alla lezione disponibile per l’iscrizione',
            'Authenticated link to the lesson available to this enrolment'],
        'TEACHER_NAME' => ['text', 'Nome del docente assegnato pertinente', 'Name of the relevant assigned teacher'],
        'CONTACT_EMAIL' => ['text', 'Indirizzo mittente Joomla se configurato e valido',
            'Joomla sender address when configured and valid'],
        'SITE_NAME' => ['text', 'Nome del sito', 'Site name'],
        'SITE_NAME_HTML' => ['html-escaped', 'Nome del sito per HTML', 'HTML-escaped site name'],
        'COURSE_MANAGEMENT_URL' => [
            'url',
            'URL amministrativa del registro iscrizioni del corso, accessibile solo ai responsabili autorizzati',
            'Administrative course enrollment management URL for authorized managers'
        ],
        'DASHBOARD_URL' => ['url', 'Collegamento autenticato all’area personale',
            'Authenticated personal dashboard link'],
        'COURSE_URL' => ['url', 'Collegamento al corso', 'Course link'],
        'ENROLLMENT_DATE' => ['date', 'Data UTC di iscrizione al corso', 'UTC course enrollment date'],
        'COMPLETION_DATE' => ['date', 'Data UTC di completamento del corso', 'UTC course completion date'],
        'COURSE_ENDS_AT' => ['date', 'Data di scadenza del corso', 'Course expiry date'],
        'CERTIFICATE_URL' => ['url', 'Collegamento all’area attestati autenticata',
            'Authenticated certificate area link'],
        'MESSAGE_SUBJECT' => ['text', 'Oggetto della comunicazione manuale', 'Manual message subject'],
        'MESSAGE_BODY_TEXT' => ['text', 'Messaggio manuale in testo semplice', 'Plain-text manual message'],
        'MESSAGE_BODY_HTML' => ['html-fragment', 'Corpo HTML sanitizzato della comunicazione',
            'Sanitized HTML message body'],
        'COURSE_IMAGE_HTML' => ['html-fragment', 'Immagine pubblica opzionale del corso',
            'Optional public course image'],
        'COURSE_INTRO_TEXT' => ['text', 'Introduzione testuale pubblica del corso', 'Public course introduction text'],
        'COURSE_INTRO_HTML' => ['html-fragment', 'Introduzione HTML sanitizzata del corso',
            'Sanitized public course introduction'],
        'COURSE_LESSONS_TEXT' => ['text', 'Elenco testuale delle lezioni pubbliche', 'Plain-text public lessons list'],
        'COURSE_LESSONS_HTML' => ['html-fragment', 'Elenco HTML delle lezioni pubbliche', 'HTML public lessons list'],
        'UNSUBSCRIBE_URL' => ['url', 'Collegamento di revoca del consenso', 'Consent unsubscribe link'],
        'UNSUBSCRIBE_URL_HTML' => ['html-escaped-url', 'Collegamento di revoca per attributo HTML',
            'HTML-escaped unsubscribe link'],
        'UNSUBSCRIBE_URL_TEXT' => ['url', 'Collegamento testuale di revoca', 'Plain-text unsubscribe link'],
        'CONFIRM_URL' => ['url', 'Collegamento di conferma adesione', 'Consent confirmation link'],
    ];

    /**
     * Joomla MailTemplate::addUnsafeTags() marks raw values to be escaped
     * during HTML rendering. Never mark already HTML-escaped values or
     * sanitized HTML fragments, otherwise Joomla would double-escape them.
     *
     * @return list<string>
     */
    public static function tagsRequiringHtmlEscape(string $templateId): array
    {
        $result = [];
        foreach (self::forTemplate($templateId) as $name => $entry) {
            if (in_array($entry['type'], ['text', 'date', 'url'], true)) {
                $result[] = $name;
            }
        }
        return $result;
    }

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
