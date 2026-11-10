# Prossime attività — ordine e condizioni di chiusura

Aggiornato al **9 novembre 2026**. Main **0.41.43-dev**: PR [#162](https://github.com/jeckodevelopment/jd_learn/pull/162) integrata nel commit `4c87851d427592885ef3a5348da3e1a182de6042`; CI standard 4/4 superata nel [run 42712132178](https://github.com/jeckodevelopment/jd_learn/actions/runs/42712132178); [pacchetto installabile](IMPLEMENTATION_STATUS.md). L’anteprima dei livelli Joomla è in sola lettura e distingue catalogo/contenuto; collaudo Joomla manuale pendente. Restano prioritari M01/M02 e l’accettazione reale dei provider.

## Priorità per la prima versione stabile

Il perimetro approvato e la suddivisione dei requisiti sono in [RELEASE_SCOPE.md](RELEASE_SCOPE.md) e [RELEASE_SCOPE_MATRIX.md](RELEASE_SCOPE_MATRIX.md). Le richieste nuove sono registrate in [REQUIREMENTS_CHANGES.md](REQUIREMENTS_CHANGES.md#rf-2026-11-08-b--accesso-e-viste-amministrative-native) e negli scenari AC-129–134. La gestione amministrativa minima AC-131 è l’eccezione esplicita alla sospensione delle nuove funzionalità quiz; i criteri didattici rimanenti di fase 2 non sono gate della prima stabile.

| Ordine | Attività | Risultato richiesto |
|---|---|---|
| 1 | Gestione corsi e programma (M01/M02) | Completare il percorso intuitivo di creazione, modifica dei corsi pubblicati, aggiornamento di lezioni/materiali e pubblicazione della nuova versione, preservando corsi in uso, versioni assegnate e progressi. |
| 2 | Provider video e materiali essenziali (M04) | YouTube, Vimeo, Cloudflare Stream e media locali previsti funzionano nella lezione con consenso, accesso e progressi corretti; collaudo su browser e account reali. |
| 3 | Fruizione e progressi core (M03/M09) | Sequenziamento delle attività obbligatorie (0.41.36-dev, PR #138) e finestre assolute (0.41.37-dev, PR #141) e rilascio relativo a giorni da enrollment.started_at (0.41.38-dev, PR #143 integrata), lezioni testo/video, regole supportate, aula, catalogo, ripresa e progressi persistenti; non rendere i quiz prerequisito di pubblicazione o completamento quando il corso non li usa. |
| 4 | Iscrizione singola e roster essenziale (M06/M09, sottoinsieme) | Accesso e iscrizione singola, sospensione/riattivazione e roster per corso con stato, avanzamento e date; ACL e storico conservati. Nessun KPI/export necessario in questa fase. |
| 5 | Qualità amministrativa e integrazioni Joomla | Collaudare accesso centralizzato ai campi (issue #99/AC-129) e Privacy 0.41.21: installazione pulita, traduzioni IT/EN e upgrade che preserva la preferenza (CI Privacy superata). Restano i collaudi manuali AC-130, AC-131, AC-133 e AC-134, quest’ultimo integrato dalla PR #124 in main 0.41.29-dev, e le verifiche Privacy/campi. Nessun ampliamento quiz ulteriore. La issue #149 è implementata nella 0.41.40-dev; resta il collaudo manuale Joomla di ordine, ACL, traduzioni e stati dei provider, separato dall’accettazione M01/M04. La 0.41.43-dev integra invio in revisione, restituzione, approvazione, archiviazione/ripristino e simulazione editoriale dei livelli di accesso (PR #162). Proseguire con l’anteprima studente interattiva in sola lettura, senza ampliare i quiz. Il collaudo manuale sul sito Joomla resta da registrare. |
| 6 | Accettazione della release (M10–M12) | Installazione/upgrade, sicurezza, accessibilità, browser/provider, conservazione dati e guide per il perimetro core. Eseguire la matrice estesa al candidato stabile, non a ogni commit preparatorio. |

### Funzionalità congelate per la fase 2

Quiz e banca domande, attestati, KPI/reportistica avanzata, export CSV e importazioni/operazioni massive, edizioni e finestre complesse, notifiche e job non indispensabili al flusso core. Sono ammesse correzioni mirate di blocchi, sicurezza e integrità. La gestione essenziale degli iscritti resta in prima fase.

Privacy, ACL, sicurezza, accessibilità, lingue e conservazione dati si applicano da subito a ogni funzione inclusa.

## Primo lavoro da svolgere

Le PR #130 e #132 hanno integrato lezioni Documento e Audio. La 0.41.34-dev ha integrato la ripresa audio; la PR #136 / 0.41.35-dev integra i prerequisiti espliciti e l’accesso protetto (CI superata, Joomla da collaudare); mantenere in parallelo aperti i collaudi manuali Joomla di documenti/audio, permessi e URL diretti e l’accettazione reale dei quattro provider. Le trascrizioni audio restano non implementate.


La PR #116 ha integrato AC-130 in 0.41.26-dev per la lista docenti secondo le convenzioni Joomla; il collaudo manuale resta da registrare. La PR #120 integra AC-131 in 0.41.27-dev: elenco separato per la banca domande; CI completa superata, collaudo Joomla pendente. La PR #122 integra issue #117 / AC-133 in 0.41.28-dev: conteggio/link corsi docenti, filtro docente e traduzione titolo. La PR #124 integra issue #115 / AC-134 in 0.41.29-dev: titolo della pagina Opzioni e label Cronologia versioni tradotti in IT/EN; collaudo manuale pendente. Non avviare altri ampliamenti quiz.

Main 0.41.29 include la dashboard e le correzioni (PR #108/#110/#112, issue #105/AC-132, CI completa superata) e i percorsi aggregati (PR #93), AC-130 lista docenti (PR #116, CI standard superata) e la ricerca roster (PR #94, deviazione non approvata), i link ai campi (PR #98), la correzione Privacy (PR #102) e issue #99 (PR #104, CI completa superata). La regressione install-package verifica lo stato nativo senza preattivare il plugin; lo smoke test verifica le stringhe tradotte. I lavori backend dell'8 novembre sono tracciati in [RF-2026-11-08-B](REQUIREMENTS_CHANGES.md#rf-2026-11-08-b--accesso-e-viste-amministrative-native), AC-129–134. Issue #115 / AC-134 integra la traduzione della pagina Opzioni nella 0.41.29-dev, con collaudo manuale pendente. Privacy resta un fix di accettazione di PRI-02/CORE-08, non una nuova funzionalità.

La PR #65 ha portato i campi nativi delle lezioni nel flusso M01/M02: salvataggio autorizzato, copia in bozza, snapshot immutabile e resa nell’aula. La PR #68 ha completato il restyling previsto della schermata iniziale. Entrambe hanno superato la CI prevista; resta il collaudo nel sito Joomla. Proseguire con gli altri residui M01 indicati in [M01_ACCEPTANCE.md](M01_ACCEPTANCE.md), mantenendo in primo piano aggiornamento di corsi pubblicati, programma e materiali. Verificare che una nuova versione non sposti iscritti o alteri progressi/storico esistenti.

Issue #154 (milestone 1.0.0) è in implementazione: il fuso predefinito di un nuovo corso deve seguire la configurazione globale Joomla del sito, mentre l’autore conserva la possibilità di sceglierne uno diverso. La verifica automatizzata è aggiunta a `tests/joomla-calendar.php`; il collaudo manuale Joomla resta da svolgere dopo l’integrazione.

In parallelo, mantenere i tre provider esterni e i media locali come requisiti core: la [durata automatica e gli scarti video](VIDEO_DURATION_PLAN.md) sono implementati nella linea0.39 ma il collaudo completo con provider/browser/account reali resta necessario. Aggiornare lo stato per ciascun build e distinguere ciò che è implementato da ciò che è accettato.

Dopo il consolidamento di corsi, programma e media, completare l’iscrizione singola e il roster core secondo AC-128. Per la banca domande è autorizzata la sola riorganizzazione amministrativa AC-131; tipi, valutazione e flussi studente restano congelati, insieme ad attestati e reportistica avanzata.

## Modalità di sviluppo e verifica

Policy CI applicata: controlli leggeri durante la preparazione e integrazione standard una volta sul candidato pronto per il merge. Due ambienti PHP/database più browser ordinariamente; matrice completa su avvio manuale prima di milestone/rilascio o modifiche di compatibilità. Nessuna duplicazione al merge. Procedura e invalidazione degli esiti in [CI_POLICY.md](CI_POLICY.md).

## Condizioni tecniche delle attività successive

I paragrafi seguenti dettagliano i contratti da rispettare; l’ordine di priorità è definito dalla tabella sopra.

### Iscrizioni: ammissione e import

La preassegnazione singola è disponibile con opt-in/avviso e prove CI (R-01). Estendere questi invarianti al CSV: distinguere assegnazione didattica e diritto corrente di fruizione, con preassegnazione esplicita/avviso se manca il livello e controlli runtime sempre obbligatori. Nessun gruppo commerciale o account creato. Conservare sospensioni/progressi e identità native; stessa email non deve trasferire storico.

Quindi implementare upload/simulazione CSV secondo DAT-06/LIFE-04/05: UTF-8, delimitatore dichiarato, intestazioni `user_id,course_id,course_version_id,edition,starts_at,ends_at`, solo account esistenti, date ISO8601 con fuso, default10MiB/10000righe entro limiti configurati. Non trattare come già disponibili versione/edizione/date solo perché sono colonne: il percorso singolo implementato ammette soltanto corrente/default e richiede un’estensione verificata del contratto.

Anteprima con esito per riga, esclusioni per scope del corso e duplicati senza aggiornamenti impliciti; file/risultati riservati all’operatore. Permesso `jlms.enrollments.import` distinto dalla gestione singola, limiti di memoria e protezione dei temporanei. Per i volumi previsti servono esecuzione a batch/job con piano immutabile/hash, cursore, lease/retry/idempotenza e riepilogo recuperabile tramite strumenti amministrativi; non una lunga richiesta PHP o un cron pubblico. Un’opzione email richiede prima outbox/Mail Templates: nessuna comunicazione finché non selezionata e implementata.

**Accettazione:** nessun gruppo/password/risultato importato; confini di dimensione/righe/date/parent e scope; duplicati, file malformati, riavvio del job e concorrenza/capienza; rifiuto degli utenti non autorizzati, conservazione dello storico e cleanup secondo politica.

### Stati, edizioni e disponibilità

La 0.41.9 integrata (PR #70) completa i comandi R-03 per chiudere/aprire le ammissioni e sospendere/riprendere l’erogazione, con anteprima, motivo, conferma e audit. Il collaudo manuale Joomla è ancora necessario; il successivo lavoro implementativo resta nell’ordine M01/M02 e provider core definito sopra. Più avanti: richieste/approvazioni/rifiuti, annullamento dell’iscrizione, gestione delle edizioni/finestre e proroghe formative. Ogni operazione sensibile ha scope, motivo/anteprima/conferma, audit, replay e impatto sui quiz/risorse già aperti. Non confondere un rinnovo di livello con una nuova frequenza.

**Accettazione:** transizioni valide/negate, stesse sessioni dopo revoca/ripristino, timer che non si riaprono, stato didattico separato dall’accesso e nessuna cancellazione o migrazione silenziosa.

### Didattica, versioni e media

Prerequisiti espliciti e blocco aula candidati 0.41.35-dev; da completare il collaudo Joomla. La modalità sequenziale è integrata nella 0.41.36-dev; la 0.41.37-dev (PR #141) aggiunge finestre assolute nel fuso corso e controllo di sovrapposizione alla pubblicazione. La 0.41.38-dev implementa il rilascio relativo e rifiuta iscrizioni con lezioni obbligatorie irraggiungibili entro le scadenze note; CI 4/4 e merge completati, collaudo Joomla pendente. Il fallback R-05 è stato corretto nella 0.41.5; il test manuale sul sito resta da svolgere. Completamento del versionamento: equivalenze/migrazione esplicita con la propria anteprima di impatto e rivalutazioni quiz senza alterare evidenze congelate. Percorsi aggregati del primo rilascio, senza anticipare attestati di percorso/sequenze fra corsi differiti.

La PR #90 integrata rimuove le liste fisse di provider in pubblicazione/fruizione/editor e usa il modulo convenzionale del plugin. L'integrazione di provider ulteriori e la relativa prova end-to-end sono rinviate alla fase 2. Per la prima stabile, collaudare YouTube, Vimeo, Cloudflare Stream e HTML5 con contenuti reali autorizzati su desktop/mobile, inclusi consenso, errori e progressi; una fixture mock non sostituisce queste prove.

**Accettazione:** storico sulla versione assegnata, pubblicazione impossibile per regole non supportate/cicliche, progresso coerente dopo cambiamenti espliciti e nessuna apertura di media o risposte riservate.

### Attestati e notifiche

Prerequisito condiviso: job/outbox affidabili e Mail Templates nativi, con eventi/lingua/destinatari documentati e gestione retry/esito incerto. Implementare idoneità, modelli/PDF e snapshot, download, verifica opzionale non enumerabile, revoca/sostituzione e anomalie dopo rivalutazione. Non inviare posta dentro le transazioni didattiche.

**Accettazione:** un documento per emissione, retry senza duplicati, layout con nomi lunghi, nessuna revoca automatica impropria e disponibilità del documento anche se la mail fallisce.

### Report e integrazioni core

Completare report/KPI, dettagli studente e CSV export con scope/filtri, fusi orari e neutralizzazione formule. Finder soltanto sui contenuti indicizzabili; Tags, Custom Fields/snapshot, Content History, Media Manager, lifecycle categorie/dipendenze e audit generale. Ampliare copertura lingua, menu, override e documentazione per ogni nuova vista.

**Privacy è trasversale, non un’attività da attendere fino a questo punto:** iniziare presto il plugin `com_privacy` con capabilities/export/removal, inventario di dati e politiche configurabili; ogni nuovo job/import/export deve integrare temporanei, accessi, cancellazione/impedimenti e conservazione. Lo stesso vale per minimizzazione e audit di sicurezza.

**Accettazione:** prove native Joomla per ogni integrazione, export/removal coerenti con dati/storici reali, nessuna indicizzazione o esportazione di contenuti privati fuori dallo scope.

### Accettazione di rilascio

Eseguire tutti gli scenari obbligatori della matrice funzionale e dei provider con riferimento al build. Verificare patch corrente Joomla, install/upgrade/backup/ripristino, integrazione reale con estensione abbonamenti, test browser/accessibilità e due personalizzazioni indipendenti. Chiudere R-04 sul documento completo nel contesto target, senza patch al core.

Audit sicurezza con analisi statica/stile/scansioni e risultati motivati. Carico misto dichiarato:300studenti attivi per60minuti e500per10minuti, dataset/infrastruttura misurati, p95/errori/integrità/lock/code e prove oltre soglia. I test concorrenti della CI non sostituiscono questo benchmark.

**Accettazione:** nessun obbligo di rilascio senza esito, anomalie con decisione esplicita, report reale delle prestazioni e guide/ZIP/checksum/aggiornamenti completi. Vendita e abbonamenti restano esterni; compiti/live/SCORM/xAPI/LTI mantengono le rispettive fasi differite.

La0.41.7 (PR #65, CI standard superata) completa il primo flusso dei campi didattici lezione: valori autorizzati copiati nelle bozze, snapshot alla pubblicazione, accesso corrente e resa nell’aula. Restano il collaudo sul sito Joomla, il supporto/accettazione di tutti i tipi e plugin e il ripristino dei valori tramite Content History (CORE-10); non confondere i campi editoriali correnti con lo storico assegnato.


## Percorsi formativi (AC-79 / issue #119)

Il CRUD amministrativo e le viste pubbliche erano già implementati. La PR [#147](https://github.com/jeckodevelopment/jd_learn/pull/147) in 0.41.39-dev integra l’accesso dal sottomenu/dashboard con ACL `jlms.pathways.manage`, i test HTTP e d’installazione e la guida operativa. CI standard 4/4 superata; resta il collaudo manuale Joomla: creazione/ordinamento/pubblicazione, vista pubblica/progressi e verifica dei ruoli con/senza permesso.
