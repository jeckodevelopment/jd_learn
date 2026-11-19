# DOC-08 — Matrice di copertura documentale dei flussi core

Versione di riferimento: 0.60.9-dev, 19/11/2026. Questa è una **prima tranche**, non l'accettazione DOC-08: collega dieci flussi di prodotto a guide esistenti e alla guida backend localizzata. Le 29 viste amministrative sono coperte separatamente dall'inventario [DOC-05](ADMIN_HELP_COVERAGE.md), con gate CI già attivo. Non equiparare la presenza di un link a una prova di completezza editoriale o a un collaudo Joomla.

| Requisito | Flusso | Guida operativa | Aiuto IT | Aiuto EN | Collaudo manuale |
|---|---|---|---|---|---|
| CAT-01 | Catalogo, ricerca e filtri | [Guida](../docs/PUBLICATION.md) | [IT](../component/administrator/help/it-IT/courses.html) | [EN](../component/administrator/help/en-GB/courses.html) | Non eseguito |
| CAT-02 | Scheda corso, programma e pubblicazione | [Guida](../docs/COURSE_EDITOR.md) | [IT](../component/administrator/help/it-IT/course.html) | [EN](../component/administrator/help/en-GB/course.html) | Non eseguito |
| CAT-03 | Percorsi e ordinamento | [Guida](../docs/LEARNING.md) | [IT](../component/administrator/help/it-IT/pathways.html) | [EN](../component/administrator/help/en-GB/pathways.html) | Non eseguito |
| VID-02 | Video, consenso e riproduzione | [Guida](../docs/VIDEO.md) | [IT](../component/administrator/help/it-IT/curriculum.html) | [EN](../component/administrator/help/en-GB/curriculum.html) | Non eseguito |
| MAT-01 | Materiali e accesso | [Guida](../docs/MATERIALS.md) | [IT](../component/administrator/help/it-IT/materials.html) | [EN](../component/administrator/help/en-GB/materials.html) | Non eseguito |
| MAIL-08 | Comunicazioni e invio di prova | [Guida](../docs/MAIL_TEMPLATES.md) | [IT](../component/administrator/help/it-IT/coursemessage.html) | [EN](../component/administrator/help/en-GB/coursemessage.html) | Non eseguito |
| MAIL-10 | Coda e diagnostica email | [Guida](../docs/MAIL_TEMPLATES.md) | [IT](../component/administrator/help/it-IT/mailqueue.html) | [EN](../component/administrator/help/en-GB/mailqueue.html) | Non eseguito |
| CERT-03 | Attestati e download | [Guida](../docs/CERTIFICATES_1.0.0.md) | [IT](../component/administrator/help/it-IT/certificates.html) | [EN](../component/administrator/help/en-GB/certificates.html) | Non eseguito |
| JOO-04 | Configurazione e Scheduler | [Guida](../docs/RELEASE_PROCEDURE.md) | [IT](../component/administrator/help/it-IT/dashboard.html) | [EN](../component/administrator/help/en-GB/dashboard.html) | Non eseguito |
| TEA-01 | Profili docente | [Guida](../docs/TEACHERS.md) | [IT](../component/administrator/help/it-IT/teachers.html) | [EN](../component/administrator/help/en-GB/teachers.html) | Non eseguito |

## Gate automatico

`tests/contextual-help-doc05.py` verifica anche questa tabella: duplicati di requisiti, file effettivamente presenti, coppie IT/EN, appartenenza all'inventario reale delle viste e stato di accettazione esplicito. Nuove viste continuano a essere rilevate dal gate DOC-05. Il gate DOC-08 **non** dimostra che tutti i flussi 1.0.0 siano mappati; è necessario completare la riconciliazione con tutti i criteri `docs/REQUIREMENTS_STATUS.csv` prima della release.

## Comportamenti da testare sul sito Joomla

1. Dopo installazione/upgrade della build, aprire dalla schermata interessata Guida; controllare il capitolo corrispondente IT/EN e assenza di 404.
2. Percorrere ciascuno dei dieci flussi end-to-end con ruolo amministratore autorizzato; verificare che i passaggi del documento siano eseguibili, non contengano istruzioni superate e non richiedano funzionalità fuori perimetro.
3. Ripetere con ruolo ACL limitato e controllare che l'aiuto non suggerisca bypass; verificare tastiera e screen reader.
4. Registrare build, esito ed eventuali difetti nel testbook; non cambiare «Non eseguito» senza evidenza.

**Residui:** mappatura di tutti gli altri flussi core e loro requisiti, controllo contenutistico bilingue, screenshot accessibili dove previsti e accettazione manuale. Nessuna variazione di runtime o versione SemVer per questa PR documentale.
