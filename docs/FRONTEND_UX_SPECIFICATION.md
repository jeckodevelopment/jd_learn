# Specifica UX frontend approvata — JD Learn

> **Decisioni concordate il 16 novembre 2026.** Documento normativo di progettazione, non prova di implementazione o accettazione. La matrice canonica è [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv); lo stato runtime è in [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md); il contratto CSS **attuale** è [FRONTEND_CSS.md](FRONTEND_CSS.md). Questa specifica descrive la **destinazione**, non afferma che classi, viste e opzioni nuove siano già presenti.

## 1. Obiettivi e principi non negoziabili

- JD Learn è un **componente Joomla**, non un'app/sito autonomo. Usare template, Web Asset Manager, router/SEF, voci menu, breadcrumb, ACL, lingue, layout e override nativi Joomla. Non sostituire header, menu o footer del template e non costruire una sidebar di navigazione globale concorrente.
- **Un solo stile nativo** professionale, minimale e neutro; nessun selettore globale aggressivo. Personalizzazione tramite CSS del template, proprietà custom e override. Non introdurre temi proprietari multipli né dipendenze obbligatorie da framework UI esterni.
- Responsive in base allo **spazio reale del contenitore** e non soltanto alla viewport; supportare desktop, tablet e smartphone, pagine con moduli Joomla laterali, zoom e testi ingranditi. Progressive enhancement: ricerca GET, paginazione e programma leggibili senza JS.
- Target accessibilità **WCAG 2.2 AA**, da verificare: focus visibile, ordine semantico di titoli, landmark non duplicati, tastiera, contrasto, descrizioni degli stati e annunci screen reader. Non basta il solo esito CI.
- Sicurezza e privacy: niente leakage dei contenuti riservati, degli URL degli asset, dei nominativi/account non autorizzati, delle risposte quiz o delle informazioni di altre iscrizioni. Le configurazioni grafiche non modificano i controlli server; non scrivere progressi con semplici GET.
- Contenuti e componenti grafici condivisi tra cataloghi, categorie, area personale e percorsi; non duplicare servizi di autorizzazione, progressi o iscrizione. Multilingua e override Joomla da preservare.

## 2. Mappa viste e disponibilità menu

| Vista logica | Menu Joomla | Baseline osservata al 16/11 | Destinazione |
|---|---|---|---|
| Catalogo corsi / scheda corso | Sì / Sì | `courses`, `course` e relativi XML presenti | Revisione UX e parametri |
| Categorie / categoria | Sì / Sì | `categories`, `category` e relativi XML presenti | Sottocategorie + corsi configurabili |
| Catalogo percorsi / scheda percorso | Sì / Sì da completare | `pathways` e XML presenti; `pathway` PHP presente, XML non trovato | Griglia + timeline e menu dettaglio ove necessario |
| Elenco docenti / profilo docente | Sì / Sì | Layout `teacher` e XML presenti; `teachers` non trovato nella ricognizione | Nuova lista pubblica, profili |
| La mia formazione | Sì | `dashboard` e XML presenti | Riepilogo personale senza duplicazione dei dettagli |
| I miei corsi | Sì | Attualmente dentro `dashboard` | Vista autonoma e storico |
| I miei attestati | Sì | Attualmente dentro `dashboard` | Vista autonoma, download autenticato |
| Preferenze JD Learn | Sì | Consenso annunci dentro `dashboard` | Vista autonoma, preferenze gestite dal componente |
| Aula / quiz | Normalmente non necessarie come voci menu | Layout `classroom`, `quiz` presenti | Navigazione contestuale protetta |
| Modifica profilo docente | Accesso contestuale autorizzato | `teacheredit` presente | Form essenziale, non backoffice parallelo |

Una vista **selezionabile** dal gestore menu non implica che tutte le installazioni debbano crearne una voce visibile. Collegamenti contestuali devono usare router Joomla e fallback sensati quando manca una voce associata.

## 3. Catalogo corsi (FE-01)

- **Griglia di schede** predefinita, 1/2/3 colonne adattive per larghezza disponibile; no griglie di 4–5 colonne. Copertina facoltativa (fallback neutro), categoria, titolo non troncato arbitrariamente, introduzione breve, durata/lezioni se note, CTA verso scheda. Semantica di collegamento unico senza link annidati; altezze e spazi coerenti senza dimensioni rigide.
- Ricerca e controlli **attivabili/disattivabili dalla singola voce menu**; l'opzione principale nasconde l'intera regione ricerca/filtri/ordinamento. Sotto-opzioni per filtro categoria e ordinamento, se il gruppo è abilitato. Parametri utili: copertina visibile, numero per pagina, ordinamento iniziale, categoria iniziale; non trasformare ogni parametro estetico in impostazione applicativa. Per i siti con pochi corsi il catalogo deve poter mostrare direttamente la sola griglia.
- Ricerca e filtri GET, paginazione e routing Joomla, URL riproducibili; disattivare il form **non** disattiva paginazione/ACL e non rende pubblici filtri privati. Considerare gestione di query preesistenti quando il menu disabilita i controlli.
- Opzioni secondo precedenza Joomla: generale -> voce menu (Globale/Sì/No dove applicabile). L'implementazione deve documentare esattamente la risoluzione dei parametri e i default, evitando incompatibilità con menu già salvati.

## 4. Scheda corso (FE-02)

- Presentazione con copertina eventuale, titolo, introduzione, descrizione, obiettivi, destinatari, prerequisiti informativi, durata/livello/numero lezioni se disponibili, docenti autorizzati e programma.
- **Programma visibile pubblicamente con moduli e titoli delle lezioni** soltanto quando tali metadati sono pubblicabili secondo ACL, lingua, stato di pubblicazione e regole di visibilità; mai corpo lezioni, URL video, allegati privati o quiz. Moduli espandibili; le sezioni vuote non vengono renderizzate.
- **Pannello laterale riepilogativo/iscrizione sticky** quando geometricamente possibile; fallback statico. Su mobile nel flusso dopo l'introduzione e prima del programma, senza overlay fissi. CTA contestuale: accesso/iscrizione, inizia, riprendi, rivedi o stato non disponibile, sempre guidata dal backend.
- Docente singolo o multiplo, profili pubblici collegabili; nessun recapito personale Joomla esposto.
- Preview dei materiali solo quando esplicitamente autorizzata; prerequisiti descrittivi distinti dalle regole server effettive.

## 5. Aula didattica (FE-03, FE-04)

- **Modalità standard** completamente integrata nel template Joomla. Intestazione compatta: ritorno a I miei corsi, titolo del corso, nome/i del docente (solo se pubblicabili), progresso corso e stato; no hero o metadati superflui.
- Programma laterale **richiudibile** e inizialmente aperto su contenitori desktop sufficientemente ampi. Per spazio intermedio inizialmente chiuso; su mobile pannello richiamabile prima della lezione. Moduli espandibili; modulo corrente aperto, lezione corrente evidenziata, stati completata/in corso/da iniziare/non disponibile comprensibili. Solo contenuti accessibili possono essere aperti.
- Sidebar **sticky quando possibile**, entro il contenitore JD Learn e senza interferire con header sticky del template, overflow o viewport ridotte; fallback statico. CSS container queries dove idonee con fallback; evitare altezze fisse, scroll orizzontale e scroll annidato non necessario. Il contenuto didattico rimane leggibile e dominante.
- Tipi di lezione: testo HTML leggibile (larghezza di lettura controllata, senza paginazione arbitraria), video (YouTube/Vimeo/Cloudflare/HTML5 con consenso e controlli già previsti, senza autoplay), audio e quiz nel perimetro esistente. Materiali della lezione visibili contestualmente, materiali corso in sezione distinta/espandibile; trascrizioni leggibili, espandibili, senza introdurre pubblicazione non autorizzata.
- **Navigazione sempre manuale**. Precedente/successiva in posizione coerente, con restrizioni effettive; il cambio lezione **non** accredita completamento. Azioni **contestuali** per testo/video/audio/quiz secondo le regole applicative; nessuna CTA che consenta di saltare prerequisiti. Non mostrare conferme inutili, salvo rischio di perdere dati (es. quiz).
- Progressi: stato di salvataggio **discreto e temporaneo quando serve**; errori persistenti e accessibili fino a risoluzione o presa d'atto, senza far credere che il progresso sia stato salvato. Stato didattico distinto dalla percentuale di playback. Ripresa ultima lezione autorizzata; archiviazione e revoca non riaprono aula.
- Completamento corso: conferma sobria, link alla formazione e download attestato **solo se pronto e autorizzato**.
- Modalità più immersiva: **opzionale, disattivata per default e oggetto di valutazione separata**, non prerequisito della 1.0.0; non nascondere arbitrariamente header/footer di template terzi. Riutilizzare un solo motore di contenuto e le stesse regole.
- Errori del player, consenso, rete, revoca e caricamento devono essere chiari e accessibili, con retry quando appropriato; niente dettagli sensibili.

## 6. Area personale (FE-05, FE-06)

Quattro viste indipendenti, ciascuna **selezionabile come voce menu Joomla**; navigazione interna fra di esse **facoltativa e configurabile** (nessuna sidebar globale). URL protetti, risposte private/no-store e noindex; il proprietario può vedere soltanto dati propri.

- **La mia formazione**: riepilogo (corsi in corso, completati, attestati), ultima attività riprendibile, eventuali percorsi con progressi disponibili, link alle viste autonome; niente statistiche inventate/gamification.
- **I miei corsi**: filtro tutti/da iniziare/in corso/completati, schede con avanzamento e stato di **accesso distinto** da completamento, CTA inizia/riprendi/rivedi soltanto se consentita. Storico dei corsi archiviati/non più accessibili conservato senza riaprire contenuti.
- **I miei attestati**: titolo, data, codice, stato e download PDF autenticato soltanto per documenti propri e disponibili; nessuna azione amministrativa di modifica, revoca o rigenerazione.
- **Preferenze**: consensi delle comunicazioni facoltative gestiti da JD Learn, distinti dalle notifiche operative. Nessun duplicato delle impostazioni generali dell'account Joomla.
- Separare viste e layout ma **riutilizzare** servizi già esistenti (oggi dashboard contiene corsi, attestati e consenso). Mantenere per compatibilità i percorsi e link consolidati durante la migrazione.

## 7. Percorsi formativi (FE-07)

- Catalogo percorsi a **griglia di schede** come i corsi: copertina **dedicata facoltativa**, titolo, introduzione, categoria, numero corsi quando disponibile. Ricerca/filtri eventualmente esposti secondo opzioni della voce menu; il dettaglio richiede supporto metadata XML se mancante.
- Dettaglio percorso: presentazione, numero corsi, durata **solo se affidabile**, sequenza **verticale ordinata** con card dei singoli corsi, loro stati/percentuali se disponibili e CTA alla scheda corso. Non dedurre prerequisiti obbligatori dal solo ordine editoriale.
- Progresso personale visibile **solo a utenti autenticati** e solo se la proiezione lo calcola; non creare medie percentuali nel template. Gli ospiti vedono la presentazione pubblica. Corsi ritirati/non accessibili segnalati senza leakage.
- Non inventare iscrizione aggregata al percorso: la CTA ordinaria conduce ai corsi e usa le iscrizioni previste dal backend; badge del percorso e credenziali avanzate restano fuori scope.

## 8. Categorie e docenti (FE-08, FE-09)

- Categorie: elenco navigabile a schede, conteggi solo di contenuti pubblici/accessibili. Nel dettaglio mostrare **sottocategorie e corsi nella stessa pagina**, con visibilità configurabile separatamente da opzioni menu; paginazioni distinte e riuso delle course card. Gestire anche categorie vuote o senza figli.
- Docenti: **nuova vista pubblica autonoma `teachers`** e relativo metadata menu; elenco di profili effettivamente pubblicabili, paginato, schede con foto facoltativa, nome pubblico e descrizione. La vista `teacher` presenta foto, bio e corsi visibili. Niente email, username o dati personali dell'account Joomla di default.
- `teacheredit`: form limitato al proprio profilo e ai permessi autorizzati, senza creare un backend editoriale parallelo nel sito pubblico.

## 9. Contratto CSS/JS e override (FE-10)

La guida [FRONTEND_CSS.md](FRONTEND_CSS.md) descrive **il contratto attualmente implementato**, compresi ID come `jlms-courses`, `jlms-course-grid`, `jlms-classroom`, `jlms-classroom-outline`, `jlms-lesson-content`, `jlms-dashboard`. Non cambiare nomi già pubblicati solo per uniformità.

Linee guida per le nuove implementazioni:
- Radice CSS scoped `.jlms-*` per ogni vista; classi pubbliche stabili per componenti ripetuti (es. `.jlms-course-card__cover`, `.jlms-pathway-card__title`, `.jlms-teacher-profile__bio`), ID univoci di regione e ID generati deterministicamente per istanze ripetute.
- JS con `data-jlms-*` o hook contrattualizzati, evitando che un cambio di classe estetica rompa la funzione. Se un attributo funzionale esistente viene sostituito, migrare JS e test nella **stessa PR**.
- Token CSS esistenti `--jlms-color-primary`, `--jlms-color-text`, `--jlms-color-surface`, `--jlms-space-unit`, `--jlms-radius`, `--jlms-content-width`, con fallback e test su template che non definiscono token Joomla specifici.
- Elencare per vista: markup semantico/landmark, ID, classi, variabili, data-attributes e loro stabilità, opzioni menu, override `templates/NOME/html/com_jlms/<vista>/default.php`, asset WAM, dipendenze e regressioni possibili.
- Evitare di rendere obbligatori HTML, CSS o JS di un tema specifico; no secondo `main` se il template ne ha già uno.

## 10. Acceptance gates trasversali (FE-11, FE-12)

1. **Menu:** ogni vista pubblicabile selezionabile da Menu → Nuova voce Joomla, etichette IT/EN, ereditarietà opzioni e valori di default; variazioni per singola voce senza influenzare altre istanze del catalogo.
2. **Protezione:** anonimo/autenticato/gruppi ACL, account bloccato, lingua, corso/lezione ritirati e finestre; nessun titolo o asset protetto trapela con link diretto, SEF disabilitato o filtri GET.
3. **Responsive:** contenitore largo/stretto, template con moduli laterali, mobile, 200% zoom, tastiera, screen reader, contrasto e stampa/download documenti.
4. **Aula:** sidebar richiudibile, sticky/fallback, progressi e errori, nessun autoplay o avanzamento didattico per semplice navigazione, video/audio/testo e quiz esistente; sessione/revoca durante il playback.
5. **Storico:** completato ma non accessibile, corso archiviato, ripresa, attestato pronto/non pronto, download proprio/altrui, preferenze opt-in e isolamento multiutente.
6. **Override:** personalizzare le sole classi CSS non rompe JS, token, ACL, routing, paginazione o player; nessun leakage e nessuna regola globalizzata.
7. CI non equivale ad accettazione: registrare prove manuali nel [QA_TESTBOOK.md](QA_TESTBOOK.md). Stima globale della release **invariata per questa specifica documentale**.

## 11. Punti tecnici da verificare prima del runtime

- Inventario completo view/model/controller/layout e XML menu (ricognizione iniziale non esaustiva).
- Parametri e default esistenti, compatibilità dei menu già salvati e delle query GET.
- Definizione di «pubblicabile» per il programma e conteggi categoria, soprattutto con corso riservato ma scheda promozionale pubblica.
- Disponibilità dei dati docenti, foto percorso e profilo pubblico: eventuale evoluzione di schema/proiezione va tracciata separatamente, non improvvisata nel template.
- Sticky con i template Joomla che usano overflow/header fissi; comportamento di persistenza del toggle e accessibilità.
- Separazione sicura della dashboard senza regressioni sui link nelle notifiche e sugli attestati già emessi.

## 12. Tracciabilità e piano

La scomposizione operativa FE-01–FE-12 è nel [piano PR 1.0.0](STABLE_1.0.0_PR_PLAN.md). Gli ID FE sono **work item di pianificazione UX, non nuovi ID di requisito canonici né PR GitHub**. Le funzionalità sono già riconducibili in parte ai requisiti esistenti (CAT/STU/UI/CERT). Prima delle PR runtime riconciliare puntualmente la matrice e, soltanto se una capacità approvata non è rappresentata, registrare un addendum secondo le regole canoniche (inclusi baseline, release plan e conteggi), senza inserire righe isolate. Prima di ogni PR runtime aggiornare la riga pertinente di [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv) con il numero effettivo della PR e lo stato supportato da prove, oltre a testbook, contratto CSS, changelog e documentazione pertinente. Non attribuire copertura implementativa o accettazione alla sola approvazione UX.
