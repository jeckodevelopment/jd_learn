# Email operative JD Learn

JD Learn registra i messaggi in Joomla → Sistema → Modelli email e invia tramite il mailer configurato nel sito. Non dispone di trasporto SMTP separato.

La disponibilità di un attestato viene accodata alla prima emissione, con template `com_jlms.certificate.available`. Il link conduce all’area personale, che richiede l’autenticazione; il file PDF continua a essere scaricato dal flusso protetto. La preferenza globale `mail_certificate_enabled` è attiva di default e il corso può ereditare, abilitare o disabilitare la singola notifica. L’outbox viene elaborato dal task `JD Learn: send queued emails`; i retry non annullano il completamento né l’emissione.

Variabili del template: `NAME`, `COURSE`, `SITENAME`, `CERTIFICATE_URL`. Le personalizzazioni fatte in Joomla sono preservate dagli aggiornamenti. Per le prove end-to-end vedere `docs/QA_TESTBOOK.md` nel repository.


## Comunicazione manuale per corso

La PR [#313](https://github.com/jeckodevelopment/jd_learn/pull/313) introduce il template operativo com_jlms.course.message e invio dal roster ai soli iscritti attivi selezionati. Preview/conferma, test all'indirizzo dell'operatore e invio asincrono via Scheduler/outbox. Le preferenze mail_communication_enabled e mail_communication governano la disponibilità; ACL e roster sono verificati di nuovo lato server.


## MAIL-11.1 — template nuovo corso

La candidata PR #315 registra il template nativo `com_jlms.course.published` con contenuto HTML/testo e tag per titolo, immagine, introduzione, lezioni pubbliche ordinate, CTA e revoca. L’installer non sovrascrive personalizzazioni esistenti. È solo la registrazione del modello: non accoda né invia annunci. Opt-in confermato, revoca funzionante e controlli di visibilità restano necessari prima di attivare il flusso.
