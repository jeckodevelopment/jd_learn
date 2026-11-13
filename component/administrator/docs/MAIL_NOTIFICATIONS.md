# Email operative JD Learn

JD Learn registra i messaggi in Joomla → Sistema → Modelli email e invia tramite il mailer configurato nel sito. Non dispone di trasporto SMTP separato.

La disponibilità di un attestato viene accodata alla prima emissione, con template `com_jlms.certificate.available`. Il link conduce all’area personale, che richiede l’autenticazione; il file PDF continua a essere scaricato dal flusso protetto. La preferenza globale `mail_certificate_enabled` è attiva di default e il corso può ereditare, abilitare o disabilitare la singola notifica. L’outbox viene elaborato dal task `JD Learn: send queued emails`; i retry non annullano il completamento né l’emissione.

Variabili del template: `NAME`, `COURSE`, `SITENAME`, `CERTIFICATE_URL`. Le personalizzazioni fatte in Joomla sono preservate dagli aggiornamenti. Per le prove end-to-end vedere `docs/QA_TESTBOOK.md` nel repository.

La comunicazione operativa agli iscritti è disponibile dal roster del corso. Usa `com_jlms.course.message`, la preferenza `mail_communication_enabled` con override `mail_communication` per corso e il task `JD Learn: send queued emails`. Selezione, anteprima e conferma si limitano agli account attivi e autorizzati del corso; i job processano 25 destinatari per esecuzione. Il test viene inviato solo all’indirizzo dell’operatore corrente. Il template usa `NAME`, `NAME_HTML`, `COURSE`, `COURSE_HTML`, `MESSAGE_SUBJECT`, `MESSAGE_BODY_TEXT`, `MESSAGE_BODY_HTML`, `SITENAME` e `SITENAME_HTML`; i placeholder HTML dinamici sono escapati.
