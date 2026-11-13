# DOC-1.0 — Documentazione prodotto e assistenza contestuale

Stato: **piano approvato per la release 1.0.0; implementazione da avviare**, senza attribuire copertura funzionale o accettazione. Questo documento definisce il gate documentale e UX della versione stabile, non dichiara già disponibili manuali o una guida contestuale completa.

## Obiettivo e destinatari

JD Learn 1.0.0 deve essere comprensibile anche a chi non ha mai amministrato un LMS: manuali aggiornati, guida contestuale di schermata, spiegazioni di campo e procedure di verifica. Tutto in italiano e inglese, con nomi e percorsi corrispondenti al prodotto rilasciato. Distinguere sempre configurazione amministrativa, compiti del docente, uso dello studente e informazioni per sviluppatori.

## Deliverable obbligatori della prima stabile

| ID | Priorità | Deliverable | Accettazione |
|---|---|---|---|
| DOC-01 | P0 | Guida installazione, requisiti Joomla/PHP/DB, primo avvio, upgrade e rollback/backup | Verifica su installazione pulita e aggiornamento con dati |
| DOC-02 | P0 | Manuale amministratore: corsi, versioni, lezioni, iscritti, provider, ACL, email, attestati PDF, privacy e manutenzione | Ogni schermata amministrativa della 1.0.0 ha una procedura verificata |
| DOC-03 | P1 | Manuale docente e studente, con percorsi tipici e screenshot reali accessibili | Eseguibili da utenti non sviluppatori |
| DOC-04 | P1 | Riferimento tecnico: struttura pacchetto, servizi, plugin, DB, aggiornamenti, task/cron, estensioni e troubleshooting | Collegamenti a sorgente/versioni e operazioni di recupero verificate |
| DOC-05 | P0 | Guida contestuale collegata alle schermate amministrative Joomla | Comando di aiuto coerente nelle schermate complesse; link a capitolo pertinente e localizzato |
| DOC-06 | P0 | Descrizioni/tooltip dei campi e delle azioni non ovvie | Spiegano scopo, formato, range, default, dipendenze, effetti di modifica e avvertenze; IT/EN |
| DOC-07 | P1 | Procedure guidate per configurazioni ad alto rischio, test della configurazione e messaggi di errore attuabili | Niente segreti in tooltip, log o screenshot; convalida server-side |
| DOC-08 | P0 | Matrice di copertura documentale e gate pre-release | Nessuna schermata o flusso core incluso privo di guida; esiti manuali registrati |
| DOC-09 | P1 | Manuale risoluzione problemi e FAQ, casistiche CI/SMTP/video/storage/ACL | Soluzioni verificate sul sito Joomla di prova |
| DOC-10 | P1 | Documentazione rilascio, changelog, incompatibilità, limiti noti e riferimenti al package | Contenuto allineato alla versione candidata |

## Modello di assistenza in tre livelli

1. **Campo**: usare prioritariamente gli attributi nativi dei form Joomla (`description`, label localizzate e layout accessibile); tooltip solo quando utili, attivabili con tastiera e touch, non solo hover. In presenza di vincoli o rischi mostrare testo visibile vicino al campo. Gli esempi non devono sostituire validazione o default. Niente libreria proprietaria di tooltip se Joomla copre l'esigenza.
2. **Schermata**: azione `Guida` contestuale nell'area di amministrazione, coerente con le convenzioni Joomla e capace di aprire il paragrafo pertinente del manuale nella lingua corrente. La guida descrive anche pulsanti, stati, permessi, conseguenze e flussi prima/dopo il salvataggio.
3. **Procedura**: per configurazioni articolate una guida passo-passo con prerequisiti, esempi sicuri, verifica della configurazione, errori recuperabili e rimandi. Evitare tour invasivi o popup ripetuti; testare su smartphone e con screen reader.

## Ricognizione iniziale (13 novembre 2026)

Analisi limitata ai form XML verificati in main: `component/administrator/forms/course.xml` contiene 39 elementi `field`, 10 con attributo `description`; `component/administrator/config.xml` ne contiene 26, 11 descritti. Questi numeri **non rappresentano la copertura complessiva**: sono inclusi campi nascosti, alcune descrizioni Joomla core e form/controlli generati in PHP. Sono già presenti aiuti per riepilogo, immagine e prerequisiti corso, radice storage materiali, socket ClamAV e retention. Non è dimostrata una guida contestuale completa, schermata per schermata. Serve censimento di tutti i form XML, view/layout PHP, pulsanti e percorsi frontend, con matrice per campo/schermata/lingua, livello di difficoltà, aiuto esistente e collaudo.

Priorità UX: editor e pubblicazione corsi/versioni; programma, rilascio e prerequisiti; provider video/tracciamento; ACL e livelli di accesso; iscrizioni; email/Scheduler/SMTP e opt-in; attestati PDF; storage/protezione file; configurazione e manutenzione. Per lo studente privilegiare istruzioni chiare in contesto per ripresa, completamento, accessi e attestati.

## Piano attuativo (dopo le implementazioni, prima del gate 1.0.0)

- **DOC-P1 — inventario e architettura editoriale (P0):** mappare tutte le schermate, campi, azioni, permessi e riferimenti requisiti; struttura `docs/manuals/`, indice e convenzione link/versioni, modello unico IT/EN. Non spostare documenti storici rompendo i link.
- **DOC-P2 — tooltip e field help prioritari (P0):** completare form XML e schermate custom più ostiche; stringhe IT/EN, formati, default e avvisi senza duplicare validazione Joomla. Richiede PR runtime, test di rendering e accessibilità.
- **DOC-P3 — guida contestuale backend (P0):** integrare entry point guida per schermate principali, link per route/task e lingua ai manuali; verificare ACL, 404, mobile, tastiera e funzionamento offline se documentazione inclusa nel pacchetto.
- **DOC-P4 — manuali dei diversi ruoli (P0):** installazione/upgrade, amministratore, docente, studente, sviluppatore, troubleshooting; esempi, immagini reali e differenza tra funzionalità incluse e future.
- **DOC-P5 — revisione finale e accettazione (P0):** walkthrough reali con profili amministratore inesperto, docente e studente; screenshot/versioni; lint link, copertura IT/EN e revisione tecnica, editoriale e accessibilità. Nessuna pubblicazione 1.0.0 senza evidenza del gate.

Le PR sono pianificate, non ancora implementate. Tutte le PR implementative vanno alla milestone `1.0.0`, includono `Comportamenti da testare` sul sito Joomla e aggiornano la tracciabilità. Una sola PR documentale che approva il piano non incrementa la percentuale di sviluppo. In seguito, ogni PR funzionale deve indicare **Impatto documentazione e aiuto contestuale**: schermate/campi/manuali da aggiornare o `nessuno` motivato.

## Criteri di qualità verificabili

- Coerenza delle versioni e dei requisiti, testi IT/EN con chiavi di lingua e nessun link rotto.
- Help raggiungibile anche con tastiera/touch, focus e screen reader; mai istruzioni basate solo sul colore.
- Campi complessi: significato, esempio valido, vincoli, permessi, conseguenze, ripristino e rimando al capitolo.
- Aggiornamenti non sovrascrivono contenuti amministrativi quando Joomla prevede override e non alterano impostazioni.
- Manuali pubblicati solo dopo walkthrough con installazione e upgrade reali, collaudi diversi dalla sola CI.

## Credentials / Open Badges — fase successiva, non gate 1.0.0

Progettare sin d'ora lo stesso modello DOC-05/06/07 per configurazione emittenti multipli, algoritmi/chiavi di firma, rotazione, revoca, scadenza, URL pubblici di verifica e conformità Open Badges. Non inserirlo nel manuale 1.0.0 come funzione disponibile. Prevedere un wizard con controlli della chiave, esempi senza segreti, spiegazione della differenza chiave pubblica/privata, prova di firma/verifica, diagnosi e gestione errori prima di attivare l'emissione. Le chiavi private non devono essere esposte in tooltip, log o documentazione; nessuna promessa di certificazione 1EdTech.
