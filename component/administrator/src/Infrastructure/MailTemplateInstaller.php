<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\CMS\Mail\MailTemplate;

/**
 * Registers editable JD Learn templates in Joomla's native Mail Templates manager.
 * Existing records are never overwritten so administrator edits survive updates.
 */
final class MailTemplateInstaller
{
    /** @return array<int, string> IDs created during this invocation. */
    public static function ensureRegistered(): array
    {
        $created = [];

        foreach (self::definitions() as $key => [$subject, $body, $tags, $htmlBody]) {
            if (MailTemplate::getTemplate($key, '*') !== null) {
                continue;
            }

            if (!MailTemplate::createTemplate($key, $subject, $body, $tags, $htmlBody)) {
                throw new \RuntimeException('JD Learn could not register the Joomla mail template: ' . $key);
            }

            $created[] = $key;
        }

        return $created;
    }

    /**
     * Only operational events approved for the 1.0.0 scope are registered here.
     *
     * @return array<string, array{string, string, array<int, string>, string}>
     */
    private static function definitions(): array
    {
        return [
            'com_jlms.enrollment.requested' => [
                'Richiesta di iscrizione ricevuta: {COURSE}',
                "Ciao {NAME},\n\nabbiamo ricevuto la tua richiesta di iscrizione al corso {COURSE}.\n\n{SITENAME}",
                ['NAME', 'COURSE', 'SITENAME'],
                '<p>Ciao {NAME},</p><p>abbiamo ricevuto la tua richiesta di iscrizione al corso <strong>{COURSE}</strong>.</p><p>{SITENAME}</p>',
            ],
            'com_jlms.enrollment.confirmed' => [
                'Iscrizione confermata: {COURSE}',
                "Ciao {NAME},\n\nla tua iscrizione al corso {COURSE} è confermata.\n\n{SITENAME}",
                ['NAME', 'COURSE', 'SITENAME', 'COURSE_URL'],
                '<p>Ciao {NAME},</p><p>la tua iscrizione al corso <strong>{COURSE}</strong> è confermata.</p><p><a href="{COURSE_URL}">Apri il corso</a></p><p>{SITENAME}</p>',
            ],
            'com_jlms.course.completed' => [
                'Corso completato: {COURSE}',
                "Ciao {NAME},\n\nhai completato il corso {COURSE}.\n\n{SITENAME}",
                ['NAME', 'COURSE', 'SITENAME'],
                '<p>Ciao {NAME},</p><p>hai completato il corso <strong>{COURSE}</strong>.</p><p>{SITENAME}</p>',
            ],
            'com_jlms.certificate.available' => [
                'Attestato disponibile: {COURSE}',
                "Ciao {NAME},\n\nil tuo attestato per il corso {COURSE} è disponibile nella tua area personale.\n\n{SITENAME}",
                ['NAME', 'COURSE', 'SITENAME', 'CERTIFICATE_URL'],
                '<p>Ciao {NAME},</p><p>il tuo attestato per il corso <strong>{COURSE}</strong> è disponibile nella tua area personale.</p><p><a href="{CERTIFICATE_URL}">Apri la tua area personale</a></p><p>{SITENAME}</p>',
            ],
            'com_jlms.enrollment.suspended' => [
                'Accesso al corso sospeso: {COURSE}',
                "Ciao {NAME},\n\nl'accesso al corso {COURSE} è stato sospeso. Per informazioni contatta il responsabile del corso.\n\n{SITENAME}",
                ['NAME', 'COURSE', 'SITENAME'],
                '<p>Ciao {NAME},</p><p>l\'accesso al corso <strong>{COURSE}</strong> è stato sospeso. Per informazioni contatta il responsabile del corso.</p><p>{SITENAME}</p>',
            ],
            'com_jlms.enrollment.reactivated' => [
                'Accesso al corso riattivato: {COURSE}',
                "Ciao {NAME},\n\nl'accesso al corso {COURSE} è stato riattivato.\n\n{SITENAME}",
                ['NAME', 'COURSE', 'SITENAME'],
                '<p>Ciao {NAME},</p><p>l\'accesso al corso <strong>{COURSE}</strong> è stato riattivato.</p><p>{SITENAME}</p>',
            ],
            'com_jlms.course.message' => [
                '{COURSE}: {MESSAGE_SUBJECT}',
                "{MESSAGE_BODY_TEXT}\n\n{SITENAME}",
                ['NAME', 'NAME_HTML', 'COURSE', 'COURSE_HTML', 'MESSAGE_SUBJECT', 'MESSAGE_BODY_TEXT', 'MESSAGE_BODY_HTML', 'SITENAME', 'SITENAME_HTML'],
                '<p>Ciao {NAME_HTML},</p><p><strong>{COURSE_HTML}</strong></p><p>{MESSAGE_BODY_HTML}</p><p>{SITENAME_HTML}</p>',
            ],
            'com_jlms.course.published' => [
                'Nuovo corso disponibile: {COURSE_TITLE}',
                "Ciao {NAME},\n\n{COURSE_INTRO_TEXT}\n\nScopri il corso: {COURSE_URL}\n\nLezioni pubbliche:\n{COURSE_LESSONS_TEXT}\n\nRicevi questa comunicazione perché hai scelto di ricevere notizie sui nuovi corsi. Per revocare l’adesione: {UNSUBSCRIBE_URL_TEXT}\n\n{SITE_NAME}",
                ['NAME', 'COURSE_TITLE', 'COURSE_TITLE_HTML', 'COURSE_IMAGE_HTML', 'COURSE_INTRO_TEXT', 'COURSE_INTRO_HTML', 'COURSE_LESSONS_TEXT', 'COURSE_LESSONS_HTML', 'COURSE_URL', 'SITE_NAME', 'SITE_NAME_HTML', 'UNSUBSCRIBE_URL', 'UNSUBSCRIBE_URL_HTML', 'UNSUBSCRIBE_URL_TEXT'],
                '<p>Ciao {NAME},</p>{COURSE_IMAGE_HTML}<h2>{COURSE_TITLE_HTML}</h2><p>{COURSE_INTRO_HTML}</p>{COURSE_LESSONS_HTML}<p><a href="{COURSE_URL}">Scopri il corso</a></p><p>Ricevi questa comunicazione perché hai scelto di ricevere notizie sui nuovi corsi. <a href="{UNSUBSCRIBE_URL_HTML}">Revoca l’adesione</a>.</p><p>{SITE_NAME_HTML}</p>',
            ],
            'com_jlms.course.consent' => [
                'Conferma l’adesione alle novità di {SITE_NAME}',
                "Ciao {NAME},\n\nper confermare la richiesta di ricevere comunicazioni sulla disponibilità di nuovi corsi, apri questo link entro 24 ore:\n{CONFIRM_URL}\n\nSe non hai fatto tu la richiesta, ignora questa email.\n\n{SITE_NAME}",
                ['NAME', 'CONFIRM_URL', 'SITE_NAME'],
                '<p>Ciao {NAME},</p><p>Per confermare la richiesta di ricevere comunicazioni sulla disponibilità di nuovi corsi, <a href="{CONFIRM_URL}">conferma l’adesione entro 24 ore</a>.</p><p>Se non hai fatto tu la richiesta, ignora questa email.</p><p>{SITE_NAME}</p>',
            ],
        ];
    }
}
