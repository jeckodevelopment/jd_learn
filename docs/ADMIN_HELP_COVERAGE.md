# DOC-05 — Inventario della guida contestuale del backend

**Ambito:** tutte le classi `component/administrator/src/View/*/HtmlView.php` attualmente distribuite. Inventario relativo alla candidata 0.60.8-dev (19 novembre 2026). Le voci sono viste amministrative reali, incluse le schermate secondarie raggiunte da azioni contestuali, e non i menu Joomla di altri componenti.

**Copertura sorgente:** 29/29 viste con `ToolbarHelper::help('<nome vista>', true)`. **Copertura documentale locale:** 58/58 pagine HTML (29 in `it-IT`, 29 in `en-GB`), cartella `help` distribuita dal manifest. Il test `python3 tests/contextual-help-doc05.py` rileva automaticamente nuove viste e fallisce per pulsanti o pagine assenti, lingue incorrette, documenti orfani e `return` prematuri.

Le guide spiegano scopo, operazioni, avvertenze e conseguenze nella lingua scelta, ma **non** equivalgono al collaudo manuale Joomla della finestra Guida, né dimostrano il collegamento a capitoli completi dei manuali. Il criterio DOC-05 sui link ai manuali resta aperto, come pure l'accettazione per ruoli, lingue, tastiera, lettore schermo, installazione e upgrade.

| Area | Vista backend | Capitolo IT | Capitolo EN |
|---|---|---|---|
| Accesso, configurazione e catalogo | `Dashboard` | `component/administrator/help/it-IT/dashboard.html` | `component/administrator/help/en-GB/dashboard.html` |
| Accesso, configurazione e catalogo | `Courses` | `component/administrator/help/it-IT/courses.html` | `component/administrator/help/en-GB/courses.html` |
| Accesso, configurazione e catalogo | `Course` | `component/administrator/help/it-IT/course.html` | `component/administrator/help/en-GB/course.html` |
| Accesso, configurazione e catalogo | `Curriculum` | `component/administrator/help/it-IT/curriculum.html` | `component/administrator/help/en-GB/curriculum.html` |
| Accesso, configurazione e catalogo | `Coursepreview` | `component/administrator/help/it-IT/coursepreview.html` | `component/administrator/help/en-GB/coursepreview.html` |
| Accesso, configurazione e catalogo | `Publicationreview` | `component/administrator/help/it-IT/publicationreview.html` | `component/administrator/help/en-GB/publicationreview.html` |
| Accesso, configurazione e catalogo | `Courseannouncement` | `component/administrator/help/it-IT/courseannouncement.html` | `component/administrator/help/en-GB/courseannouncement.html` |
| Accesso, configurazione e catalogo | `Courseoperations` | `component/administrator/help/it-IT/courseoperations.html` | `component/administrator/help/en-GB/courseoperations.html` |
| Accesso, configurazione e catalogo | `Categoryreassignment` | `component/administrator/help/it-IT/categoryreassignment.html` | `component/administrator/help/en-GB/categoryreassignment.html` |
| Persone, iscrizioni e percorsi | `Enrollments` | `component/administrator/help/it-IT/enrollments.html` | `component/administrator/help/en-GB/enrollments.html` |
| Persone, iscrizioni e percorsi | `Manualenrollment` | `component/administrator/help/it-IT/manualenrollment.html` | `component/administrator/help/en-GB/manualenrollment.html` |
| Persone, iscrizioni e percorsi | `Enrollmentstate` | `component/administrator/help/it-IT/enrollmentstate.html` | `component/administrator/help/en-GB/enrollmentstate.html` |
| Persone, iscrizioni e percorsi | `Enrollmentmigration` | `component/administrator/help/it-IT/enrollmentmigration.html` | `component/administrator/help/en-GB/enrollmentmigration.html` |
| Persone, iscrizioni e percorsi | `Pathways` | `component/administrator/help/it-IT/pathways.html` | `component/administrator/help/en-GB/pathways.html` |
| Persone, iscrizioni e percorsi | `Pathway` | `component/administrator/help/it-IT/pathway.html` | `component/administrator/help/en-GB/pathway.html` |
| Persone, iscrizioni e percorsi | `Teachers` | `component/administrator/help/it-IT/teachers.html` | `component/administrator/help/en-GB/teachers.html` |
| Persone, iscrizioni e percorsi | `Teacher` | `component/administrator/help/it-IT/teacher.html` | `component/administrator/help/en-GB/teacher.html` |
| Materiali, verifiche e quiz | `Materials` | `component/administrator/help/it-IT/materials.html` | `component/administrator/help/en-GB/materials.html` |
| Materiali, verifiche e quiz | `Questionbank` | `component/administrator/help/it-IT/questionbank.html` | `component/administrator/help/en-GB/questionbank.html` |
| Materiali, verifiche e quiz | `Question` | `component/administrator/help/it-IT/question.html` | `component/administrator/help/en-GB/question.html` |
| Materiali, verifiche e quiz | `Quiz` | `component/administrator/help/it-IT/quiz.html` | `component/administrator/help/en-GB/quiz.html` |
| Materiali, verifiche e quiz | `Quizextend` | `component/administrator/help/it-IT/quizextend.html` | `component/administrator/help/en-GB/quizextend.html` |
| Materiali, verifiche e quiz | `Quiztime` | `component/administrator/help/it-IT/quiztime.html` | `component/administrator/help/en-GB/quiztime.html` |
| Materiali, verifiche e quiz | `Quizreview` | `component/administrator/help/it-IT/quizreview.html` | `component/administrator/help/en-GB/quizreview.html` |
| Comunicazioni e notifiche | `Mailqueue` | `component/administrator/help/it-IT/mailqueue.html` | `component/administrator/help/en-GB/mailqueue.html` |
| Comunicazioni e notifiche | `Mailvariables` | `component/administrator/help/it-IT/mailvariables.html` | `component/administrator/help/en-GB/mailvariables.html` |
| Comunicazioni e notifiche | `Coursemessage` | `component/administrator/help/it-IT/coursemessage.html` | `component/administrator/help/en-GB/coursemessage.html` |
| Attestati | `Certificates` | `component/administrator/help/it-IT/certificates.html` | `component/administrator/help/en-GB/certificates.html` |
| Attestati | `Certificate` | `component/administrator/help/it-IT/certificate.html` | `component/administrator/help/en-GB/certificate.html` |

## Checklist di accettazione mancante

1. **Integrazione:** installazione pulita e upgrade Joomla con il pacchetto verificato; prova della risoluzione delle guide offline in entrambe le lingue, senza 404 e senza cambiare l'operazione in corso.
2. **Ruoli:** utente amministrativo autorizzato e account con ACL limitate; i pulsanti non espongono informazioni protette né forzano scritture o azioni non autorizzate.
3. **Accessibilità:** ordine del focus, attivazione da tastiera, apertura/chiusura e lettore di schermo; viewport mobile.
4. **Contenuto:** confronto passo-passo con procedure amministrative reali e manuali IT/EN aggiornati, risoluzione dei link e assenza di istruzioni obsolete.
5. **Gate DOC-05:** riconciliare `docs/FUNCTIONAL_CRITERIA_1.0.0.json` e il registro requisiti solo per i criteri dimostrati; non considerare completato l'intero DOC-05 prima dell'accettazione.
