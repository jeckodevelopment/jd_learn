# Email operative JD Learn

JD Learn registra i messaggi in Joomla → Sistema → Modelli email e invia tramite il mailer configurato nel sito. Non dispone di trasporto SMTP separato.

La disponibilità di un attestato viene accodata alla prima emissione, con template `com_jlms.certificate.available`. Il link conduce all’area personale, che richiede l’autenticazione; il file PDF continua a essere scaricato dal flusso protetto. La preferenza globale `mail_certificate_enabled` è attiva di default e il corso può ereditare, abilitare o disabilitare la singola notifica. L’outbox viene elaborato dal task `JD Learn: send queued emails`; i retry non annullano il completamento né l’emissione.

Variabili del template: `NAME`, `COURSE`, `SITENAME`, `CERTIFICATE_URL`. Le personalizzazioni fatte in Joomla sono preservate dagli aggiornamenti. Per le prove end-to-end vedere `docs/QA_TESTBOOK.md` nel repository.
