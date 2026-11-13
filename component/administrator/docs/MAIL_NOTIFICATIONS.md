# Email operative JD Learn

JD Learn registra i messaggi in Joomla → Sistema → Modelli email e invia tramite il mailer configurato nel sito. Non dispone di trasporto SMTP separato.

La disponibilità di un attestato viene accodata alla prima emissione, con template `com_jlms.certificate.available`. Il link conduce all’area personale, che richiede l’autenticazione; il file PDF continua a essere scaricato dal flusso protetto. La preferenza globale `mail_certificate_enabled` è attiva di default e il corso può ereditare, abilitare o disabilitare la singola notifica. L’outbox viene elaborato dal task `JD Learn: send queued emails`; i retry non annullano il completamento né l’emissione.

Variabili del template: `NAME`, `COURSE`, `SITENAME`, `CERTIFICATE_URL`. Le personalizzazioni fatte in Joomla sono preservate dagli aggiornamenti. Per le prove end-to-end vedere `docs/QA_TESTBOOK.md` nel repository.


## Comunicazione manuale per corso

La PR [#313](https://github.com/jeckodevelopment/jd_learn/pull/313) introduce il template operativo com_jlms.course.message e invio dal roster ai soli iscritti attivi selezionati. Preview/conferma, test all'indirizzo dell'operatore e invio asincrono via Scheduler/outbox. Le preferenze mail_communication_enabled e mail_communication governano la disponibilità; ACL e roster sono verificati di nuovo lato server.


## MAIL-11.1 — template nuovo corso

La PR #315, integrata in 0.56.10-dev, registra il template nativo `com_jlms.course.published` con contenuto HTML/testo e tag per titolo, immagine, introduzione, lezioni pubbliche ordinate, CTA e revoca. L’installer non sovrascrive personalizzazioni esistenti. È solo la registrazione del modello: non accoda né invia annunci. Opt-in confermato, revoca funzionante e controlli di visibilità restano necessari prima di attivare il flusso.

La PR #317, integrata in 0.56.11-dev, introduce nel frontend la richiesta opt-in facoltativa e non preselezionata. Il double opt-in invia `com_jlms.course.consent` all’indirizzo dell’account e salva solo l’hash SHA-256 di un token monouso con scadenza di 24 ore. La prova conserva origine, versione informativa e timestamp; la preferenza può essere revocata dall’Area personale ed è inclusa nei flussi del plugin Privacy. L’invio degli annunci resta disattivato; mancano integrazione in iscrizione/registrazione, link anonimo di revoca e conferma/preview amministrativa.

La PR #319, integrata in 0.56.12-dev, aggiunge una casella facoltativa e non preselezionata nel modulo di iscrizione automatica. La richiesta double opt-in parte solo dopo l'avvio riuscito; un POST ripetuto con consenso già pending non reinvia la conferma. Un errore del mailer non annulla l'iscrizione. Integrazione registrazione Joomla, revoca via link e invio amministrativo restano aperti; accettazione manuale Joomla/SMTP pendente. CI standard 4/4 nel [run 43469236700](https://github.com/jeckodevelopment/jd_learn/actions/runs/43469236700); pacchetto e checksum in CHANGELOG.md. Pacchetto 0.56.12-dev disponibile fino al 27 novembre 2026 alle 17:09 UTC.
