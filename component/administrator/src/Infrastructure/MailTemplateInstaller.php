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

    /** @return array<int, string> Registered JD Learn Mail Template IDs in canonical order. */
    public static function templateIds(): array
    {
        return array_keys(self::definitions());
    }

    /** @return array<int, string> Canonical placeholders registered for this template. */
    public static function tagsFor(string $templateId): array
    {
        $definitions = self::definitions();
        return isset($definitions[$templateId]) ? $definitions[$templateId][2] : [];
    }

    /**
     * Only operational events approved for the 1.0.0 scope are registered here.
     *
     * @return array<string, array{string, string, array<int, string>, string}>
     */
    private static function definitions(): array
    {
        return [
            'com_jlms.enrollment.manager_request' => [
                'Nuova richiesta di iscrizione: {COURSE_TITLE}',
                "Ciao {USER_FULLNAME},\n\nÈ stata presentata una richiesta di iscrizione al corso {COURSE_TITLE} che richiede una decisione. Accedi al registro iscrizioni del corso con il tuo account autorizzato:\n\n{COURSE_MANAGEMENT_URL}\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'USER_FULLNAME_HTML', 'COURSE_TITLE', 'COURSE_TITLE_HTML',
                    'COURSE_MANAGEMENT_URL', 'SITE_NAME', 'SITE_NAME_HTML'],
                '<p>Ciao {USER_FULLNAME_HTML},</p><p>È stata presentata una richiesta di iscrizione al corso <strong>{COURSE_TITLE_HTML}</strong> che richiede una decisione.</p><p><a href="{COURSE_MANAGEMENT_URL}">Apri il registro iscrizioni</a></p><p>{SITE_NAME_HTML}</p>',
            ],
            'com_jlms.course.updated' => [
                'Aggiornamento del percorso: {COURSE_TITLE}',
                "Ciao {USER_FULLNAME},\n\nIl tuo percorso nel corso {COURSE_TITLE} è stato aggiornato a una nuova versione didattica. Trovi le lezioni attualmente disponibili nella tua area personale.\n\n{DASHBOARD_URL}\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'USER_FULLNAME_HTML', 'COURSE_TITLE', 'COURSE_TITLE_HTML',
                    'DASHBOARD_URL', 'SITE_NAME', 'SITE_NAME_HTML'],
                '<p>Ciao {USER_FULLNAME_HTML},</p><p>Il tuo percorso nel corso <strong>{COURSE_TITLE_HTML}</strong> è stato aggiornato a una nuova versione didattica.</p><p>Trovi le lezioni attualmente disponibili nella tua area personale.</p><p><a href="{DASHBOARD_URL}">Apri la tua area personale</a></p><p>{SITE_NAME_HTML}</p>',
            ],
            'com_jlms.lesson.available' => [
                'Nuova lezione disponibile: {LESSON_TITLE}',
                "Ciao {USER_FULLNAME},\n\nla lezione {LESSON_TITLE} del corso {COURSE_TITLE} è ora disponibile.\n{LESSON_DESCRIPTION}\n\n{LESSON_URL}\n\n{COURSE_DESCRIPTION}\nDurata stimata: {COURSE_DURATION}\nInizio corso: {COURSE_START_DATE}\nAssistenza: {CONTACT_EMAIL}\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'USER_FULLNAME_HTML', 'LESSON_TITLE', 'LESSON_TITLE_HTML',
                    'COURSE_TITLE', 'COURSE_TITLE_HTML', 'COURSE_DESCRIPTION', 'COURSE_DURATION',
                        'COURSE_START_DATE', 'LESSON_DESCRIPTION', 'LESSON_URL', 'CONTACT_EMAIL',
                            'DASHBOARD_URL', 'SITE_NAME', 'SITE_NAME_HTML'],
                '<p>Ciao {USER_FULLNAME_HTML},</p><p>La lezione <strong>{LESSON_TITLE_HTML}</strong> del corso <strong>{COURSE_TITLE_HTML}</strong> è ora disponibile.</p><p>{LESSON_DESCRIPTION}</p><p><a href="{LESSON_URL}">Apri la lezione</a></p><p>{COURSE_DESCRIPTION}</p><p>Durata stimata: {COURSE_DURATION}<br>Inizio corso: {COURSE_START_DATE}<br>Assistenza: {CONTACT_EMAIL}</p><p>{SITE_NAME_HTML}</p>',
            ],
            'com_jlms.teacher.assigned' => [
                'Assegnazione al corso: {COURSE_TITLE}',
                "Ciao {USER_FULLNAME},\n\nsei stato assegnato come docente ({TEACHER_NAME}) al corso {COURSE_TITLE}. La gestione del corso rimane soggetta ai permessi Joomla.\n\nDescrizione: {COURSE_DESCRIPTION}\nDurata stimata: {COURSE_DURATION}\nInizio corso: {COURSE_START_DATE}\nAssistenza: {CONTACT_EMAIL}\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'COURSE_TITLE', 'TEACHER_NAME', 'COURSE_DESCRIPTION',
                    'COURSE_DURATION', 'COURSE_START_DATE', 'CONTACT_EMAIL', 'SITE_NAME'],
                '<p>Ciao {USER_FULLNAME},</p><p>sei stato assegnato come docente ({TEACHER_NAME}) al corso <strong>{COURSE_TITLE}</strong>. La gestione del corso rimane soggetta ai permessi Joomla.</p><p>{COURSE_DESCRIPTION}</p><p>Durata stimata: {COURSE_DURATION}<br>Inizio corso: {COURSE_START_DATE}<br>Assistenza: {CONTACT_EMAIL}</p><p>{SITE_NAME}</p>',
            ],
            'com_jlms.enrollment.requested' => [
                'Richiesta di iscrizione ricevuta: {COURSE_TITLE}',
                "Ciao {USER_FULLNAME},\n\nabbiamo ricevuto la tua richiesta di iscrizione al corso {COURSE_TITLE}.\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'COURSE_TITLE', 'SITE_NAME'],
                '<p>Ciao {USER_FULLNAME},</p><p>abbiamo ricevuto la tua richiesta di iscrizione al corso <strong>{COURSE_TITLE}</strong>.</p><p>{SITE_NAME}</p>',
            ],
            'com_jlms.enrollment.approved' => [
                'Richiesta di iscrizione approvata: {COURSE_TITLE}',
                "Ciao {USER_FULLNAME},\n\nla tua richiesta di iscrizione al corso {COURSE_TITLE} è stata approvata.\n\nAccedi alla tua area personale: {DASHBOARD_URL}\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'COURSE_TITLE', 'DASHBOARD_URL', 'SITE_NAME'],
                '<p>Ciao {USER_FULLNAME},</p><p>la tua richiesta per il corso <strong>{COURSE_TITLE}</strong> è stata approvata.</p><p><a href="{DASHBOARD_URL}">Apri la tua area personale</a></p><p>{SITE_NAME}</p>',
            ],
            'com_jlms.enrollment.rejected' => [
                'Richiesta di iscrizione non approvata: {COURSE_TITLE}',
                "Ciao {USER_FULLNAME},\n\nla tua richiesta di iscrizione al corso {COURSE_TITLE} non è stata approvata.\n\nPer informazioni, contatta il responsabile del corso.\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'COURSE_TITLE', 'SITE_NAME'],
                '<p>Ciao {USER_FULLNAME},</p><p>la tua richiesta per il corso <strong>{COURSE_TITLE}</strong> non è stata approvata.</p><p>Per informazioni contatta il responsabile del corso.</p><p>{SITE_NAME}</p>',
            ],
            'com_jlms.enrollment.confirmed' => [
                'Iscrizione confermata: {COURSE_TITLE}',
                "Ciao {USER_FULLNAME},\n\nla tua iscrizione al corso {COURSE_TITLE} è confermata (data UTC: {ENROLLMENT_DATE}).\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'COURSE_TITLE', 'SITE_NAME', 'COURSE_URL', 'ENROLLMENT_DATE'],
                '<p>Ciao {USER_FULLNAME},</p><p>la tua iscrizione al corso <strong>{COURSE_TITLE}</strong> è confermata (data UTC: {ENROLLMENT_DATE}).</p><p><a href="{COURSE_URL}">Apri il corso</a></p><p>{SITE_NAME}</p>',
            ],
            'com_jlms.course.expiring' => [
                'Il corso {COURSE_TITLE} termina il {COURSE_ENDS_AT}',
                "Ciao {USER_FULLNAME},\n\nil corso {COURSE_TITLE} terminerà il {COURSE_ENDS_AT}. Completa le attività entro la scadenza.\n\n{COURSE_URL}\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'COURSE_TITLE', 'COURSE_ENDS_AT', 'COURSE_URL', 'SITE_NAME'],
                '<p>Ciao {USER_FULLNAME},</p><p>il corso <strong>{COURSE_TITLE}</strong> terminerà il {COURSE_ENDS_AT}. Completa le attività entro la scadenza.</p><p><a href="{COURSE_URL}">Apri il corso</a></p><p>{SITE_NAME}</p>',
            ],
            'com_jlms.course.completed' => [
                'Corso completato: {COURSE_TITLE}',
                "Ciao {USER_FULLNAME},\n\nhai completato il corso {COURSE_TITLE} (data UTC: {COMPLETION_DATE}).\n\nAccedi alla tua area personale: {DASHBOARD_URL}\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'COURSE_TITLE', 'SITE_NAME', 'COMPLETION_DATE', 'DASHBOARD_URL'],
                '<p>Ciao {USER_FULLNAME},</p><p>hai completato il corso <strong>{COURSE_TITLE}</strong> (data UTC: {COMPLETION_DATE}).</p><p><a href="{DASHBOARD_URL}">Apri la tua area personale</a></p><p>{SITE_NAME}</p>',
            ],
            'com_jlms.certificate.available' => [
                'Attestato disponibile: {COURSE_TITLE}',
                "Ciao {USER_FULLNAME},\n\nil tuo attestato per il corso {COURSE_TITLE} è disponibile nella tua area personale.\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'COURSE_TITLE', 'SITE_NAME', 'CERTIFICATE_URL'],
                '<p>Ciao {USER_FULLNAME},</p><p>il tuo attestato per il corso <strong>{COURSE_TITLE}</strong> è disponibile nella tua area personale.</p><p><a href="{CERTIFICATE_URL}">Apri la tua area personale</a></p><p>{SITE_NAME}</p>',
            ],
            'com_jlms.enrollment.suspended' => [
                'Accesso al corso sospeso: {COURSE_TITLE}',
                "Ciao {USER_FULLNAME},\n\nl'accesso al corso {COURSE_TITLE} è stato sospeso. Per informazioni contatta il responsabile del corso.\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'COURSE_TITLE', 'SITE_NAME'],
                '<p>Ciao {USER_FULLNAME},</p><p>l\'accesso al corso <strong>{COURSE_TITLE}</strong> è stato sospeso. Per informazioni contatta il responsabile del corso.</p><p>{SITE_NAME}</p>',
            ],
            'com_jlms.enrollment.reactivated' => [
                'Accesso al corso riattivato: {COURSE_TITLE}',
                "Ciao {USER_FULLNAME},\n\nl'accesso al corso {COURSE_TITLE} è stato riattivato.\n\nApri la tua area personale: {DASHBOARD_URL}\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'COURSE_TITLE', 'SITE_NAME', 'DASHBOARD_URL'],
                '<p>Ciao {USER_FULLNAME},</p><p>l\'accesso al corso <strong>{COURSE_TITLE}</strong> è stato riattivato.</p><p><a href="{DASHBOARD_URL}">Apri la tua area personale</a></p><p>{SITE_NAME}</p>',
            ],
            'com_jlms.enrollment.cancelled' => [
                'Iscrizione annullata: {COURSE_TITLE}',
                "Ciao {USER_FULLNAME},\n\nl'iscrizione al corso {COURSE_TITLE} è stata annullata. Per informazioni contatta il responsabile del corso.\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'COURSE_TITLE', 'SITE_NAME'],
                '<p>Ciao {USER_FULLNAME},</p><p>l\'iscrizione al corso <strong>{COURSE_TITLE}</strong> è stata annullata. Per informazioni contatta il responsabile del corso.</p><p>{SITE_NAME}</p>',
            ],
                        'com_jlms.course.message' => [
                '{COURSE_TITLE}: {MESSAGE_SUBJECT}',
                "{MESSAGE_BODY_TEXT}\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'USER_FULLNAME_HTML', 'COURSE_TITLE', 'COURSE_TITLE_HTML',
                    'MESSAGE_SUBJECT', 'MESSAGE_BODY_TEXT', 'MESSAGE_BODY_HTML', 'SITE_NAME', 'SITE_NAME_HTML'],
                '<p>Ciao {USER_FULLNAME_HTML},</p><p><strong>{COURSE_TITLE_HTML}</strong></p><p>{MESSAGE_BODY_HTML}</p><p>{SITE_NAME_HTML}</p>',
            ],
            'com_jlms.course.published' => [
                'Nuovo corso disponibile: {COURSE_TITLE}',
                "Ciao {USER_FULLNAME},\n\n{COURSE_INTRO_TEXT}\n\nScopri il corso: {COURSE_URL}\n\nLezioni pubbliche:\n{COURSE_LESSONS_TEXT}\n\nRicevi questa comunicazione perché hai scelto di ricevere notizie sui nuovi corsi. Per revocare l’adesione: {UNSUBSCRIBE_URL_TEXT}\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'COURSE_TITLE', 'COURSE_TITLE_HTML', 'COURSE_DESCRIPTION',
                    'COURSE_DURATION', 'COURSE_START_DATE', 'CONTACT_EMAIL', 'COURSE_IMAGE_HTML',
                        'COURSE_INTRO_TEXT', 'COURSE_INTRO_HTML', 'COURSE_LESSONS_TEXT',
                            'COURSE_LESSONS_HTML', 'COURSE_URL', 'SITE_NAME', 'SITE_NAME_HTML',
                                'UNSUBSCRIBE_URL', 'UNSUBSCRIBE_URL_HTML', 'UNSUBSCRIBE_URL_TEXT'],
                '<p>Ciao {USER_FULLNAME},</p>{COURSE_IMAGE_HTML}<h2>{COURSE_TITLE_HTML}</h2><p>{COURSE_DESCRIPTION}</p><p>{COURSE_INTRO_HTML}</p>{COURSE_LESSONS_HTML}<p>Durata stimata: {COURSE_DURATION}<br>Inizio corso: {COURSE_START_DATE}<br>Assistenza: {CONTACT_EMAIL}</p><p><a href="{COURSE_URL}">Scopri il corso</a></p><p>Ricevi questa comunicazione perché hai scelto di ricevere notizie sui nuovi corsi. <a href="{UNSUBSCRIBE_URL_HTML}">Revoca l’adesione</a>.</p><p>{SITE_NAME_HTML}</p>',
            ],
            'com_jlms.course.consent' => [
                'Conferma l’adesione alle novità di {SITE_NAME}',
                "Ciao {USER_FULLNAME},\n\nper confermare la richiesta di ricevere comunicazioni sulla disponibilità di nuovi corsi, apri questo link entro 24 ore:\n{CONFIRM_URL}\n\nSe non hai fatto tu la richiesta, ignora questa email.\n\n{SITE_NAME}",
                ['USER_FULLNAME', 'CONFIRM_URL', 'SITE_NAME'],
                '<p>Ciao {USER_FULLNAME},</p><p>Per confermare la richiesta di ricevere comunicazioni sulla disponibilità di nuovi corsi, <a href="{CONFIRM_URL}">conferma l’adesione entro 24 ore</a>.</p><p>Se non hai fatto tu la richiesta, ignora questa email.</p><p>{SITE_NAME}</p>',
            ],
        ];
    }
}
