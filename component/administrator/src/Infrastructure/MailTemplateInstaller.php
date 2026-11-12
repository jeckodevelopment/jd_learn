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
        ];
    }
}
