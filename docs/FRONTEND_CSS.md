# Contratto frontend CSS v1

**Specifica UX approvata (target, non ancora implementato):** [FRONTEND_UX_SPECIFICATION.md](FRONTEND_UX_SPECIFICATION.md). Gli identificatori proposti nella specifica non sono parte del contratto CSS runtime finché non vengono introdotti e documentati nelle singole PR. Questa guida resta il registro del contratto **effettivamente presente** nel codice.


**Inventario implementativo di viste, ID, JS e WAM:** [FRONTEND_CONTRACT_INVENTORY.md](FRONTEND_CONTRACT_INVENTORY.md). Il test `tests/frontend-contract-hooks.py` blocca la rimozione accidentale degli hook noti e viene eseguito nella CI. Il test non sostituisce la verifica manuale di DOM runtime e override.\n\nQuesta guida descrive il contratto corrente per layout, CSS e override. Le sezioni sono organizzate per vista/funzione; per avanzamento e limiti funzionali usare [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

Variabili: `--jlms-color-primary`, `--jlms-color-text`, `--jlms-color-surface`, `--jlms-space-unit`, `--jlms-radius`, `--jlms-content-width`. Si possono impostare nel CSS personalizzato del template. Nessun selettore globale del prodotto modifica il template ospitante.

Override Joomla: `templates/NOME/html/com_jlms/courses/default.php`; preservare gli ID delle regioni e usare classi per elementi ripetuti. Non duplicare ID. Preservare gli attributi funzionali usati dagli script e descritti nelle sezioni delle rispettive viste.

Aggiornare questa guida a ogni introduzione/modifica di stili e layout. Non modificare i file del core o del componente installato per una personalizzazione del sito.

## Ingresso amministrativo

La dashboard amministrativa usa `#jlms-admin-dashboard` e il foglio WAM `com_jlms.admin-dashboard` (`admin-dashboard.css`), caricato solo nella vista iniziale. La regione primaria `jlms-admin-dashboard__primary` porta all’elenco corsi; gli strumenti secondari e lo stato dei provider hanno regioni distinte. Gli ID e le classi sono stabili per gli override. Gli stili sono limitati alla radice della dashboard e si adattano sotto767px; il template amministrativo Joomla conserva colori e tipografia di base.

## Backend del programma didattico

Override: `administrator/templates/NOME/html/com_jlms/curriculum/default.php`. La scaletta conserva `#jlms-admin-curriculum`, `#jlms-admin-course-outline`, `.jlms-admin-module` e `data-module-id`. Nuovi ID unici `jlms-module-ID`, `jlms-lesson-ID`, `jlms-order-status`, `jlms-reorder-form`. Il form `jlms-module-form` o `jlms-lesson-form` è presente soltanto per l’editor richiesto, non su ogni pagina. I test o override che assumevano entrambi sempre visibili devono seguire i link contestuali. Le lezioni MP4 espongono upload/scan soltanto nella selezione della lezione; la scansione di sicurezza resta consentita sullo storico.

WAM `com_jlms.curriculum` registra `curriculum.css` e il modulo `curriculum.js`, caricato solo per bozze modificabili. CSS confinato alla radice amministrativa; griglia scaletta/riepilogo che passa a una colonna sotto991px. La classe `jlms-sort-enabled` attiva le maniglie solo con JS e nessun editor aperto. Preservare `data-editor-mode`, `data-sort-kind`, `data-item-id`, `data-drag-handle`, nomi/token/scope del form condiviso e attributi tradotti dello stato. Gli attributi sono contratto funzionale, non selettori estetici. Modifiche dell’override non devono introdurre form annidati, ID duplicati o scorciatoie GET mutative. La struttura usa dettagli nativi, titoli, label e link per navigazione/tastiera; il collaudo browser/accessibilità resta richiesto.

## FE-10 — primitive condivise di scheda (prima implementazione)

La classe pubblica **`.jlms-card`** è un hook CSS non funzionale, aggiunto senza rimuovere le classi specifiche: `.jlms-course-card` (catalogo corsi e percorsi), `.jlms-category-card` (categorie) e `.jlms-my-course` (dashboard personale, inclusi gli attestati incorporati). Gli override nativi continuano a trovare ID e classi originari: aggiungere `.jlms-card` è facoltativo per gli override storici. Non è un elemento cliccabile autonomo e non cambia ACL, routing, filtri, URL o autorizzazioni.

Regola CSS disponibile nel WAM `com_jlms.site`, file `component/media/css/site.css`: solo dentro `#jlms-courses`, `#jlms-categories`, `#jlms-dashboard`, `#jlms-pathways`, `.jlms-card` riceve `box-sizing:border-box`, `min-width:0`; titoli e paragrafi discendenti possono andare a capo in presenza di stringhe lunghe. La regola non modifica pulsanti, link o contenuti di altri componenti Joomla. Personalizzazione tramite `templates/NOME/css/user.css` (se disponibile) oppure CSS personalizzato del template, senza modificare file installati. Nessuna sostituzione delle variabili legacy in `:root` in questa PR: verificarne compatibilità prima di migrare eventuali default verso scope più stretti.

**Contratti JS da non rompere:** `#jlms-classroom-outline-toggle`, `#jlms-classroom-outline`, `aria-expanded`, `data-show-label` e `data-hide-label` restano indispensabili all'adattatore attuale `classroom-outline.js`. Gli ID delle card ripetute sono generati dai record, mai dal titolo o dall'indice. Le future migrazioni a `data-jlms-*` richiedono aggiornamento contestuale di JS e test, non sono implicite in FE-10.

Verifica automatica: `python3 tests/frontend-css-contract.py`. Validazione manuale obbligatoria nel [QA_TESTBOOK.md](QA_TESTBOOK.md). **Questa è una prima base FE-10, non la chiusura dell'inventario completo di CSS/JS/override** e non implica ancora la nuova UX prevista da [FRONTEND_UX_SPECIFICATION.md](FRONTEND_UX_SPECIFICATION.md).

## FE-08 — Sezioni categoria configurabili

La vista `category/default.php` mantiene `#jlms-category`, `#jlms-subcategories`, `#jlms-category-courses`, le paginazioni indipendenti `#jlms-subcategories-pagination` e `#jlms-category-courses-pagination` quando le rispettive sezioni sono visibili. Le sottocategorie riusano `.jlms-card.jlms-category-card.jlms-subcategory` in `#jlms-subcategories-list.jlms-category-grid`; i corsi riusano `.jlms-card.jlms-course-card`. Le opzioni di menu `category_show_subcategories` e `category_show_courses`, entrambe attive per default, controllano sezioni intere senza markup vuoto o dati non autorizzati. Nessuna nuova regola CSS globale; override Joomla mantengano ID, ACL e paginazione indipendente.

## FE-07 — Percorsi formativi

Catalogo `pathways/default.php`: radici #jlms-pathways, #jlms-pathway-grid; schede `.jlms-pathway-card`, `.jlms-pathway-card__cover` facoltativa (media pubblico locale) e `.jlms-pathway-card__title`. Scheda `pathway/default.php`: #jlms-pathway, copertina opzionale #jlms-pathway-cover, #jlms-pathway-course-count, #jlms-pathway-sequence e lista ordinata #jlms-pathway-courses.jlms-pathway-timeline. I singoli step sono `.jlms-pathway-timeline__item`, `.jlms-pathway-timeline__card`, `.jlms-pathway-timeline__step`, `.jlms-pathway-timeline__title`; ID `jlms-pathway-course-{index}` deterministico. Percentuale aggregata #jlms-pathway-progress e percentuali per corso sono solo autenticate e disponibili; i course link riusano routing/ACL esistenti. Solo CSS scoped, senza JS, layout responsive e override Joomla nativi. L'ordine è editoriale, non implica prerequisiti.

## FE-06 — Navigazione personale opzionale

Le viste private riusano dashboard/_personal_navigation.php. Il parametro per singola voce menu show_personal_navigation, disattivo di default, controlla il rendering di nav#jlms-personal-navigation. Lista responsive .jlms-personal-navigation__list, collegamenti .jlms-personal-navigation__link e voce corrente .jlms-personal-navigation__current con aria-current=page. CSS confinato a .jlms-dashboard, focus visibile, nessun JavaScript o menu globale duplicato. Override Joomla devono preservare ACL, routing, semantica e accessibilità.

## FE-05 — Dashboard riepilogativa (quarta tranche)

La vista `dashboard/default.php` mantiene `#jlms-dashboard.jlms-dashboard` e `#jlms-dashboard-header`. Usa la regione `#jlms-dashboard-summary`, un `dl.jlms-summary-stats` con contatori storici e i link esistenti `#jlms-dashboard-my-courses-link`, `#jlms-dashboard-my-certificates-link`, `#jlms-dashboard-preferences-link` e `#jlms-dashboard-catalog`. L'eventuale `#jlms-dashboard-latest` contiene una ripresa condizionata alla proiezione di autorizzazione. Lo stile dei contatori è confinato a `#jlms-dashboard` e non modifica token globali, componenti Joomla, o i form delle viste autonome. Il layout completo delle iscrizioni è reso solo in `mycourses/default.php`; gli override Joomla non devono reintrodurre contenuti privati né link mutativi.

## FE-05 — Preferenze personali (terza tranche)

La vista `preferences` usa `#jlms-preferences-view.jlms-dashboard` e il partial `dashboard/_preferences.php`. Gli ID `#jlms-course-announcement-preferences` e `#jlms-mail-consent-state`, il token CSRF e le azioni POST preesistenti sono preservati. Web Asset Manager `com_jlms.site`, senza nuovi JS o stili globali. Override: `templates/NOME/html/com_jlms/preferences/default.php`; collaudo Joomla IT/EN, ACL, menu e SMTP pendente.

## FE-05 — I miei attestati (PR #510)

La vista `mycertificates` utilizza la radice `#jlms-my-certificates-view.jlms-dashboard.jlms-my-certificates-view`, la griglia esistente `.jlms-my-course-grid` e le schede `.jlms-card.jlms-my-course`. Il partial `dashboard/_certificates.php` viene riutilizzato anche da `dashboard/default.php`, senza duplicazione di ID per schede ripetute. WAM `com_jlms.site`, nessun JS richiesto. Le regole `.jlms-card` sono estese alla nuova radice senza alterare gli override esistenti. Override nativo: `templates/NOME/html/com_jlms/mycertificates/default.php`; preservare owner-check sul servizio e link al task privato di download. Menu IT/EN, anonimato, ACL, viewport e override da collaudare in Joomla.

## FE-01 — Catalogo corsi a griglia e opzioni menu

La vista `courses` usa la radice `#jlms-courses` (`.jlms-catalog`) e un contenitore CSS nominato `jlms-catalog`. La griglia `#jlms-course-grid.jlms-course-grid`, esclusa la variante legacy `.jlms-course-grid--list`, distribuisce le schede su tre, due o una colonna in base alla **larghezza del contenitore**, con fallback media query per browser privi di container queries. La variante legacy elenco non viene eliminata in FE-01 per evitare regressioni, ma la modalità predefinita rimane la griglia.

Ogni card conserva `jlms-course-card-ID`, `.jlms-card.jlms-course-card`, `data-course-id`, `.jlms-course-card__title`, `.jlms-course-card__category` e `.jlms-course-card__summary` quando la descrizione è visibile. Nuove classi: `.jlms-course-card__cover` (immagine con `object-fit:cover` o segnaposto neutro), `.jlms-course-card__cover-placeholder`, `.jlms-course-card__content`, `.jlms-course-card__meta`. I due link alla categoria e al dettaglio non sono annidati. Il contenuto grafico usa il **snapshot di presentazione della versione pubblicata**, senza esporre materiale didattico privato.

Le opzioni `catalog_show_filters`, `catalog_show_order`, `catalog_show_cover`, `catalog_show_summary`, `catalog_page_size` e `catalog_default_order` sono configurabili globalmente in `com_jlms` e sovrascrivibili nella voce menu `courses` (vuoto = eredita; `0` e `1` rispettano anche i valori false). I valori globali predefiniti sono rispettivamente `1,1,1,1,12,recent`. La risoluzione applicativa è in `Site/Helper/CatalogOptions.php`, non in CSS o JavaScript. Con ricerca disattivata il server **ignora i filtri GET** passati artificialmente, mentre un `catid` esplicitamente previsto continua a limitare il catalogo. La paginazione resta attiva. L'ordinamento da GET opera solo con ricerca e controllo ordinamento abilitati; in caso contrario si usa il default configurato.

Override Joomla: `templates/NOME/html/com_jlms/courses/default.php`; mantenere identificativi e controlli server e non rendere nuovamente pubblici metadati protetti. Test statico: `python3 tests/frontend-catalog-options.py`; collaudo reale in [QA_TESTBOOK.md](QA_TESTBOOK.md). Questa tranche **non** modifica scheda corso, categorie o percorsi.

## FE-01 — Filtro per categoria e metadati didattici (seconda tranche)

Il catalogo `courses` aggiunge il controllo facoltativo `#jlms-catalog-category` nel form GET `#jlms-catalog-filters`: `filter[category_id]` seleziona la **categoria diretta** del corso, distinta da `catid` che rimane la limitazione del catalogo alla categoria e ai discendenti già presente nella rotta. Le opzioni provengono da `CatalogService::publicCourseCategories()`, calcolate soltanto sulle schede effettivamente pubblicate e visibili all'utente (stato, versione, accesso, lingua, finestra e categoria/antenati Joomla). Un ID di categoria estraneo alle opzioni visibili produce risultati vuoti; non amplia mai i permessi. Se il form di ricerca o il controllo categoria sono disabilitati, `filter[category_id]` non viene considerato dal modello.

`catalog_show_category` è configurabile in **JD Learn → Opzioni**, con override `Globale/Sì/No` nella voce menu `Catalogo corsi`; valore predefinito Sì. Le etichette sono tradotte IT/EN. Il catalogo mostra inoltre `difficulty` (Base/Intermedio/Avanzato) assieme alla durata solo se il metadato è presente nel **snapshot pubblicato**. In assenza di metadati non viene creato un riquadro vuoto. Nessuna modifica alle descrizioni o ai permessi delle lezioni. CSS scoped per il nuovo controllo, senza introdurre temi grafici proprietari.

Verifica statica `python3 tests/frontend-catalog-options.py`, test Joomla in [QA_TESTBOOK.md](QA_TESTBOOK.md). Restano da verificare comportamento dei template personalizzati, selezione degli aggregati di sottocategorie e accettazione manuale.

## FE-02 — Scheda corso, iscrizione e programma pubblico (prima tranche)

Il dettaglio `course/default.php` conserva `#jlms-course`, `#jlms-course-header`, `#jlms-course-title`, `#jlms-course-cover`, `#jlms-course-facts`, `#jlms-course-access`, `#jlms-start-form`, `#jlms-enrollment-request-form`, `#jlms-course-outline`, `#jlms-course-teachers`, `#jlms-course-materials` e gli ID delle righe. La scheda usa un'unica struttura CSS: `#jlms-course-layout.jlms-course-layout`, `#jlms-course-main.jlms-course-main`, `#jlms-course-sidebar.jlms-course-sidebar` e `.jlms-course-sidebar__inner`. **Non esistono due form di iscrizione o pulsanti duplicati:** il form esistente viene collocato nel pannello.

Il layout si organizza in due colonne (contenuto e pannello laterale) quando lo spazio reale lo consente; usa la container query `jlms-course-page` e un fallback media query. Il template può definire `--jlms-course-sticky-offset` per evitare interferenze con un proprio header sticky. La sidebar è `position:sticky` soltanto quando possibile, senza `fixed` o overlay, con fallback statico su contenitori stretti o viewport basse. Su mobile l'ordine del DOM è: introduzione, descrizione e campi nativi Joomla «dopo il contenuto», pannello di iscrizione, poi docenti e programma. Questo preserva l'ordine storico dei campi Joomla rispetto al form e la navigazione da tastiera. Il pannello continua ad applicare esclusivamente le condizioni server già presenti per accesso, modalità, finestra e CSRF.

L'outline rimane `#jlms-course-outline`, con moduli `#jlms-module-ID.jlms-module` resi in `details/summary` nativi, primo modulo aperto e titoli consultabili senza JavaScript. I titoli di lezioni con pubblicazione futura/scaduta o rilascio relativo non ancora disponibile sono **esclusi nella proiezione server** `CatalogService::outline()`: solo metadati minimi autorizzati dalla versione corrente, mai corpo/asset/risposte quiz. La disponibilità di un'anteprima rimane governata da `publicLessonPreview()`.

La modifica è di presentazione e disclosure pubblica; non altera i controller di iscrizione, i contenuti di lezione, il tracciamento, i permessi di accesso o lo schema SQL. La personalizzazione passa per `templates/NOME/html/com_jlms/course/default.php` e CSS del template, senza sovrascrivere l'header/footer Joomla. Smoke automatico `python3 tests/frontend-course-details.py`; collaudo manuale richiesto in [QA_TESTBOOK.md](QA_TESTBOOK.md).

## Catalogo e scheda corso

| Vista | ID stabili | Classi ripetute |
|---|---|---|
| Catalogo | `jlms-courses`, `jlms-catalog-header`, `jlms-course-grid`, `jlms-category-filter`, `jlms-catalog-empty`, `jlms-catalog-pagination` | `jlms-course-card`, `jlms-course-card__title`, `jlms-course-card__category`, `jlms-course-card__summary` |
| Scheda | `jlms-course`, `jlms-course-header`, `jlms-course-title`, `jlms-course-category`, `jlms-course-summary`, `jlms-course-access`, `jlms-course-outline`, `jlms-course-back` | `jlms-module`, `jlms-module__title`, `jlms-module__lessons`, `jlms-lesson`, `jlms-lesson__title`, `jlms-lesson__requirement` |

Elementi ripetuti: `jlms-course-card-{courseId}`, `jlms-module-{moduleId}`, `jlms-lesson-{lessonId}`; gli ID non dipendono dal titolo o dalla lingua. Le regioni condizionali sono presenti solo quando pertinenti. Override nativi in `templates/NOME/html/com_jlms/courses/default.php` e `course/default.php`; gli override devono conservare escaping, link Joomla e assenza di dati protetti. WAM `com_jlms.site`; variabili CSS già documentate. Le viste categorie e aula sono descritte nelle sezioni dedicate di questa guida.

## FE-03 — Programma espandibile e responsive (prima tranche)

La vista `classroom/default.php` conserva tutti gli hook esistenti (`#jlms-classroom`, `#jlms-classroom-outline-toggle`, `#jlms-classroom-outline`, `#jlms-lesson-content`, player, quiz, materiali, token e form progressi). La nuova presentazione usa `details` e `summary` nativi per ogni modulo `.jlms-classroom-module`; il modulo della lezione corrente è espanso inizialmente. Le lezioni mantengono i collegamenti contestuali e gli stati prodotti dal backend; navigare non accredita alcun completamento.

Con JavaScript attivo, `classroom-outline.js` misura la larghezza del **contenitore del componente** mediante ResizeObserver e, se inferiore o uguale a 768px, mostra il pulsante di apertura/chiusura del programma. In mancanza di ResizeObserver usa `matchMedia` come fallback; senza JavaScript il programma resta visibile e i moduli sono comunque espandibili. L'attributo `aria-expanded` e le etichette `data-show-label` / `data-hide-label` restano gli hook funzionali, non sostituirli negli override.

CSS esclusivamente scoped su `#jlms-classroom`: griglia contenuto + indice che diventa singola colonna tramite container query `jlms-classroom` (fallback media-query); indice sticky quando possibile e statico su layout stretti o viewport basse. Il template può impostare `--jlms-classroom-sticky-offset` per non coprire gli header sticky nativi Joomla. Non usa `fixed` né nasconde il template. Testo lezione con larghezza leggibile, senza limitare la larghezza di video e materiali.

L'intestazione compatta mostra i docenti **soltanto se i loro profili sono pubblicabili e accessibili** tramite `TeacherProfiles::current`; non espone email/username o altri dettagli dell'account Joomla. Questo è il primo incremento FE-03: resta da completare la UX complessiva dell'aula e il collaudo manuale, separati da FE-04 (navigazione e feedback progressi).

Contratto statico `python3 tests/frontend-classroom-layout.py`, aggiunto alla CI; casi Joomla in [QA_TESTBOOK.md](QA_TESTBOOK.md).

## FE-04 — Navigazione manuale fra lezioni (prima tranche)

La vista `classroom/default.php` aggiunge `#jlms-lesson-navigation` a fine contenuto, con `.jlms-lesson-navigation__item`, `.jlms-lesson-navigation__link`, `.jlms-lesson-navigation__unavailable`, `.jlms-lesson-navigation__label`, `.jlms-lesson-navigation__title` e `.jlms-lesson-navigation__reason`. Il componente usa i record della scaletta di **questa iscrizione già autorizzata**: precedente/successiva sono le lezioni immediatamente adiacenti nell'ordine didattico. Il server ha già prodotto `schedule_state` e `prerequisite_blocked`; soltanto adiacenti disponibili e senza prerequisiti bloccanti producono link GET (con `rel=prev/next`), altrimenti appare un'indicazione non interattiva del blocco. Le lezioni assenti ai margini non producono link.

Il nuovo `nav` ha `aria-label` localizzato IT/EN. **Non** contiene form, task mutativi, pulsanti di completamento o JavaScript. Cliccare Precedente/Successiva apre solo la lezione tramite il routing Joomla già esistente: eventuale registrazione dello stato `started` è gestita dal servizio originale dell'aula e non equivale a completamento. Progressi, quiz, player, idempotency key, allegati e ACL restano invariati. Nessuna navigazione automatica a fine lezione.

CSS scoped a `#jlms-classroom`, due colonne quando possibile, una nei contenitori piccoli, focus tastiera visibile. Override: `templates/NOME/html/com_jlms/classroom/default.php`. Collaudo manuale obbligatorio in [QA_TESTBOOK.md](QA_TESTBOOK.md); smoke CI `python3 tests/frontend-lesson-navigation.py`. Questa tranche **non** completa ancora tutti i requisiti FE-04 di feedback dei progressi.

## FE-04 — Feedback discreto e affidabile dei progressi

I messaggi di `#jlms-lesson-progress-status` e `#jlms-audio-progress-status` distinguono gli stati `saving`, `saved`, `error`, `idle` tramite `data-state`. Lo stato salvato resta visibile circa **5 secondi**, poi viene pulito; gli errori **non** scompaiono automaticamente e utilizzano `role="alert"` con `aria-live="assertive"`, mentre le conferme sono `role="status"` con `aria-live="polite"`. Lo stile è limitato a `#jlms-classroom` e non modifica template esterni.

Il JS della lezione conserva un solo retry e la medesima idempotency key per `lesson.begin`; la risposta del server è sempre autorevole. Il codice audio conserva il proprio retry e mostra `saving` quando invia una posizione nuova; un errore precedente resta visibile fino a una risposta di salvataggio riuscita, senza promettere persistibilità quando il server non l'ha confermata. Nessuna modifica agli endpoint backend, ai timer del playback video, ai quiz o all'azione manuale `lesson.complete`.

Il programma e le classi CSS legacy rimangono disponibili negli override. Il nuovo test statico `tests/frontend-progress-feedback.py` non sostituisce le prove browser e rete del [QA_TESTBOOK.md](QA_TESTBOOK.md). Restano da valutare in una successiva tranche i feedback di altre azioni didattiche non coperte da questi due adapter.

## FE-05 — Prima vista autonoma: I miei corsi

La voce menu Joomla `mycourses` è ora una vista privata autonoma, raggiungibile tramite `index.php?option=com_jlms&view=mycourses`, con configurazione in `component/site/tmpl/mycourses/default.xml`. Il controller impone `Cache-Control: private, no-store` e `X-Robots-Tag: noindex, nofollow`; il documento HTML imposta `robots=noindex,nofollow`.

Il modello `MycoursesModel` estende il modello `DashboardModel`, riutilizzando **gli stessi servizi di autorizzazione, filtro e storico personale**, senza scrivere query proprie. Le schede corsi sono renderizzate una sola volta dal partial PHP `component/site/tmpl/dashboard/_courses.php`, incluso sia nella dashboard esistente sia nella nuova vista autonoma. Restano stabili `#jlms-dashboard`, `#jlms-my-courses`, `.jlms-my-course`, gli ID delle card, i link resume e i controlli progresso. La nuova vista riusa il CSS `com_jlms.site`, senza un secondo stile.

Gli override Joomla possono sovrascrivere `templates/NOME/html/com_jlms/mycourses/default.php` e i layout originali `dashboard/default.php`; il partial incluso dal template installato è condiviso, quindi per personalizzazioni profonde è preferibile usare un override della vista autonoma e un CSS scoped del template, senza alterare le verifiche ACL del modello. L'area personale resta protetta; la dashboard precedente continua a funzionare, con un collegamento contestuale a **I miei corsi**. La separazione di attestati e preferenze e la trasformazione della dashboard in riepilogo saranno tranche successive.

Test statico `python3 tests/frontend-my-courses.py`; collaudo Joomla richiesto in [QA_TESTBOOK.md](QA_TESTBOOK.md).

## Aula testo

Override nativo: `templates/NOME/html/com_jlms/classroom/default.php`. Regioni: `jlms-classroom`, `jlms-classroom-header`, `jlms-learning-progress`, `jlms-progress-meter`, `jlms-course-completed` (condizionale), `jlms-classroom-layout`, `jlms-classroom-outline`, `jlms-lesson-content`, `jlms-lesson-title`, `jlms-lesson-body`, `jlms-complete-form`, `jlms-classroom-back`. Ripetizioni: `jlms-classroom-module-{id}`, `jlms-classroom-lesson-{id}`. Classi omonime per layout/moduli/lezioni; `aria-current` indica la lezione aperta. Griglia a due colonne da desktop e una colonna sotto 48rem, senza dimensioni fisse del testo. Il pulsante `jlms-classroom-outline-toggle` è visibile solo su viewport mobile: controlla `jlms-classroom-outline` tramite `aria-controls` e `aria-expanded`; il modulo `classroom-outline.js` aggiorna label e visibilità. In assenza di JavaScript l’indice resta visibile. Gli override devono conservare questi ID, attributi e label tradotte. Usare le variabili WAM già documentate. Conservare token, key, ID di parent e filtro HTML negli override.

Scheda pubblica: `jlms-course-login` per ospiti, `jlms-start-form` per accesso automatico autorizzato. Il bottone non è mostrato quando manca il livello o l’account è bloccato.

## Materiali protetti

Regioni: `jlms-course-materials` nella scheda appare solo quando esistono allegati di anteprima pubblica espliciti; descrizione e metadati sono limitati a quei file. `jlms-classroom-course-materials` e `jlms-lesson-resources` nell’aula, raggruppate da `jlms-material-groups`. Ripetizioni `jlms-material-{attachmentId}` e classi `.jlms-material`, `.jlms-material__title`, `.jlms-material__meta`, `.jlms-material__action`, `.jlms-material-list`. Ogni ambito ha paginazione distinta (`course_material_page`, `lesson_material_page`). Override `course/default.php` e `classroom/default.php` nativi; mantenere i link del componente con enrollment_id, mai un URL filesystem/storage diretto. Le classi e variabili WAM rimangono compatibili con 0.4.

## Player video

L'override `classroom/default.php` distingue testo e video: solo il testo include `jlms-complete-form`. Il player si apre secondo la politica video del sito. Il video usa `jlms-video-region`, `jlms-player-status` e `jlms-player`; classe `.jlms-player`. Le lezioni audio usano `jlms-audio-region` e `jlms-audio-player`; il player nativo è fluido fino a 48rem e resta entro la larghezza della colonna. Preservare questi ID unici, pulsanti type=button, stato aria-live e le script options/WAM `com_jlms.video`. Il contenitore player ha aspect ratio16/9 soltanto quando non è vuoto. Gli stili possono cambiare senza inserire iframe remoti nel markup o esporre sorgenti. Vedere `VIDEO.md` per consenso/CMP e lifecycle.

## Cloudflare Stream

Riutilizza le regioni del player0.6 senza introdurre ID duplicati o stili globali. `jlms-player-status` annuncia anche il rinnovo; dopo la ricreazione dell'iframe la posizione viene ripristinata e la riproduzione rimane in pausa. Gli override mantengono WAM, script options, controlli di consenso e stato aria-live. Non inserire token o credenziali nei layout: il descrittore temporaneo arriva da POST autorizzato.

## HTML5 e form video

Il player locale riutilizza tutte le regioni aula0.6: `jlms-player` contiene un elemento video nativo con controlli, playsinline e preload metadata dopo caricamento esplicito. CSS `.jlms-player` e `#jlms-player video` mantengono il rapporto16/9 senza selettori globali. Gli override non inseriscono URL filesystem o video pubblici.

Nel backend `curriculum/default.php`: `jlms-video-file-{lessonId}`, `jlms-video-upload-{lessonId}`, `jlms-video-input-{lessonId}`, classe `.jlms-video-file`. Il form upload esiste solo in bozza; quello di rescan può essere disponibile su una lezione pubblicata per lo stato di sicurezza. Conservare enctype, nomi campo, parent, revisione e token Joomla; nessun ID è duplicato. Non rendere modificabili durata e asset del video locale nel form lezione: sono server-owned.

## Authoring quiz

Override backend nativi `questionbank/default.php`, `question/default.php` e `quiz/default.php`. Regioni: `jlms-question-list`, `jlms-question-form`, `jlms-question-lock-status`, `jlms-quiz-editor`, `jlms-quiz-form`, `jlms-quiz-delete-form`. La lista è separata dal form. Sono ID unici; righe/opzioni ripetute usano il layout subform Joomla. Non aggiungere risposte corrette ai layout pubblici né spostare logica di permesso nei template. Preservare token, nomi dei campi, revisioni e parent; gli override devono conservare i controlli del flusso e non pubblicare chiavi o spiegazioni protette. Nessun nuovo CSS globale.

## Quiz studenti

Override nativo `com_jlms/quiz/default.php`. ID unici `jlms-quiz`, `jlms-quiz-header`, `jlms-quiz-rules`, `jlms-quiz-availability`, `jlms-quiz-status`, `jlms-quiz-timer`, `jlms-quiz-result`, `jlms-quiz-form`, `jlms-quiz-questions`, `jlms-quiz-actions`, `jlms-quiz-save`, `jlms-quiz-submit`, `jlms-quiz-start`, `jlms-quiz-history`, `jlms-quiz-back`. Regioni condizionali possono mancare. Righe `jlms-quiz-question-{snapshotId}`, opzioni `jlms-quiz-option-{snapshotId}-{optionId}`; classi `.jlms-quiz-question`, `__prompt`, `__option`, `__feedback`, `.jlms-quiz-clear`. Nell’aula `jlms-lesson-quiz` collega la pagina dedicata.

Gli script dipendono da ID form/status/timer/azioni e da `data-question-id`, input/valori, fieldset e `.jlms-quiz-clear`: conservarli o sostituire anche l’adattatore client. Le regole e gli snapshot sono verificati sul server; non derivare autorizzazioni dallo stato DOM. Il DTO rilascia correzioni solo quando ammesse; un override non deve leggere la banca direttamente. CSS limitato alla regione quiz.

## Tempo individuale e avvisi

Frontend: `jlms-quiz-time-allowance` mostra tempo individuale del tentativo e durata del prossimo; `jlms-quiz-warning` è la regione `role=alert` per avvisi a cinque/un minuto. Conservare ruolo e `aria-live`, senza unire questa regione alla conferma del salvataggio. La durata visualizzata non autorizza una deadline differente sul server.

Backend: override `quiztime/default.php`, ID `jlms-quiz-time-editor`, `jlms-quiz-time-policy`, `jlms-quiz-time-enrollments`, `jlms-quiz-time-active`, `jlms-quiz-time-form`, `jlms-quiz-time-history`; nell’editor quiz `jlms-quiz-time-link`. Non pubblicare motivazioni operative nei template del sito. Preservare parent, revisione, UUID e token del form. Nessuna nuova classe CSS globale.

## Annullamento tentativi

Frontend: `jlms-quiz-invalidated` spiega che il tentativo è annullato e il punteggio eventualmente mostrato è storico. Preservare il controllo sul campo `valid`/stato del DTO: non presentare un superamento annullato come efficace né mostrare correzioni non rilasciate.

Backend: override `quizreview/default.php`; ID `jlms-quiz-review`, `jlms-quiz-review-policy`, `jlms-quiz-review-enrollments`, `jlms-quiz-review-attempts`, `jlms-quiz-review-preview`, `jlms-quiz-review-blocked`, `jlms-quiz-review-form`, `jlms-quiz-review-record`. `jlms-quiz-review-link` collega dalla gestione tempo. Conservare parent, revisioni, scelta di restituzione, hash, UUID, conferma e token. I link di anteprima non eseguono mutazioni; non trasformarli in annullamenti automatici. Nessuna nuova classe CSS globale.

## Proroga del tentativo

Frontend: `jlms-quiz-duration` contiene i secondi effettivi e viene aggiornato dalla sincronizzazione; `jlms-quiz-extension` annuncia la proroga con `role=status`, distinto da salvataggio e avvisi. Conservare ID e regioni live negli override o sostituire anche l’adattatore client. La sincronizzazione non deve sostituire le risposte locali né allentare i controlli server.

Backend: override `quizextend/default.php`, ID `jlms-quiz-extend`, `jlms-quiz-extend-policy`, `jlms-quiz-extend-preview-form`, `jlms-quiz-extend-minutes`, `jlms-quiz-extend-impact`, `jlms-quiz-extend-blocked`, `jlms-quiz-extend-form`, `jlms-quiz-extend-history`; collegamento `jlms-quiz-extend-link` dalla revisione del tentativo. Conservare parent, revisioni, minuti, hash, UUID, conferma e CSRF. L’anteprima GET non esegue proroghe. Nessuna nuova classe CSS globale.

## Lingua

Il cambio lingua dei corsi usa il modulo core `mod_languages`: stile e override seguono il contratto del modulo Joomla. I contenitori pubblici `jlms-course` e `jlms-courses` restano gli stessi nelle traduzioni. Nel backend `jlms-course-associations` contiene i campi nativi delle traduzioni. Vedere MULTILINGUAL.md per fallback e perimetro.

## Categorie

Override nativi `categories/default.php` e `category/default.php`. Nuove regioni:

| Vista | ID stabili | Ripetizioni |
|---|---|---|
| Elenco | `jlms-categories`, `jlms-categories-header`, `jlms-categories-description`, `jlms-categories-list`, `jlms-categories-pagination`, `jlms-categories-empty`, `jlms-categories-parent`, `jlms-categories-catalog` | `jlms-category-card-{id}`, classi `jlms-category-card`, `jlms-category-card__title`, `jlms-category-card__description` |
| Scheda | `jlms-category`, `jlms-category-header`, `jlms-category-title`, `jlms-category-description`, `jlms-subcategories`, `jlms-subcategories-list`, `jlms-subcategories-empty`, `jlms-subcategories-pagination`, `jlms-category-courses`, `jlms-category-course-grid`, `jlms-category-courses-empty`, `jlms-category-courses-pagination`, `jlms-category-back` | `jlms-subcategory-{id}`, `jlms-course-card-{id}` |

Le regioni vuote/di ritorno sono condizionali. Il catalogo aggiunge `jlms-catalog-categories`; i precedenti link categoria mantengono ID/classi e aprono la nuova scheda. Griglia responsive `.jlms-category-grid` con variabili CSS esistenti e focus visibile. Nessun ID dipende dal titolo o dalla lingua; nessun ID è duplicato nella stessa pagina. Le paginazioni corsi e figli devono conservare parametri distinti. Pathway e selettore lingua sono resi dai moduli core, personalizzabili tramite i loro override Joomla.

## Area personale

Override `templates/NOME/html/com_jlms/dashboard/default.php`. Regioni: `jlms-dashboard`, `jlms-dashboard-header`, `jlms-dashboard-filters`, `jlms-my-courses`, `jlms-dashboard-not-started`, `jlms-dashboard-in-progress`, `jlms-dashboard-completed`, `jlms-dashboard-empty`, `jlms-dashboard-pagination`, `jlms-dashboard-catalog`. Gruppi e stato vuoto sono condizionali.

Ripetizioni `jlms-my-course-{enrollmentId}`, `jlms-my-course-progress-{enrollmentId}`, `jlms-my-course-resume-{enrollmentId}`; classi `.jlms-my-course-grid`, `.jlms-my-course`, `.jlms-my-course__title`, `.jlms-my-course__access`, `.jlms-my-course__progress`, `.jlms-my-course__action`. Nessun ID dipende dal titolo o dalla lingua. Griglia responsive e focus con le variabili esistenti. Il form filtra tramite `filter[progress]`; conservare menu/router, intestazioni private, escaping e distinzione fra avanzamento e disponibilità. Il link di ritorno nell'aula è `jlms-classroom-dashboard`.

## Backend iscritti

La vista amministrativa usa ID e override descritti in [ENROLLMENT_ROSTER.md](ENROLLMENT_ROSTER.md). Tabella responsive e form adottano gli stili core del template amministrativo. Nessun nuovo stile frontend.

## Backend stato iscrizione

Gli ID/override del flusso anteprima/conferma sono documentati in [ENROLLMENT_STATE.md](ENROLLMENT_STATE.md). Form, alert e tabella responsive usano gli stili core del template amministrativo; nessun CSS pubblico aggiunto.

## Backend iscrizione manuale

ID, form e override descritti in [MANUAL_ENROLLMENT.md](MANUAL_ENROLLMENT.md). Stili del template amministrativo nativi; nessun CSS globale frontend aggiunto.

## Avviso preassegnazione

Il layout amministrativo manualenrollment aggiunge `#jlms-manual-access-warning` (`alert alert-warning`, `role=status`) quando manca l’accesso corrente. [PREASSIGNMENT.md](PREASSIGNMENT.md) descrive campo opzionale e override da aggiornare; nessun nuovo CSS pubblico.

## Backend pubblicazione

Override amministrativi `course/edit.php` e `curriculum/default.php`. Nuovi ID: `jlms-course-publication-help`, `jlms-course-publication-heading`, `jlms-course-programme-link` (solo corso salvato), `jlms-publication-status`, `jlms-publication-heading`, `jlms-publication-notice` (solo impedimento), `jlms-publication-submit` (azione disponibile). Preservare avvisi, token, ACL e transizione nativa. Stili alert/btn del template amministrativo, nessun CSS pubblico aggiunto. Gli override esistenti vanno confrontati per ricevere navigazione e diagnostica.

## Presentazione corso

Editor nativo: ID e pannelli documentati in COURSE_EDITOR.md. Nuove regioni nella scheda pubblica (override `templates/NOME/html/com_jlms/course/default.php`): `jlms-course-description`, `jlms-course-objectives`, `jlms-course-audience`, `jlms-course-prerequisite-text`, `jlms-course-facts`, `jlms-course-difficulty`, `jlms-course-estimated-duration`. Sono condizionali, presenti una sola volta e indipendenti da lingua/titolo. Classi `.jlms-course-description`, `.jlms-course-presentation-section`; nessuna nuova regola CSS globale. Usare le variabili esistenti e il CSS del template per margini/tipografia.

La descrizione è HTML con allowlist già applicata; gli altri testi sono escapati. Conservare entrambi i comportamenti nell'override. Non leggere params_json del corso o bozze per popolare queste regioni: la sorgente è la proiezione editoriale corrente autorizzata in `$this->item['presentation']`; lo storico didattico mantiene i propri snapshot.

## Copertina e metadati

`#jlms-course-cover` e `.jlms-course-cover` avvolgono l’immagine pubblica opzionale in `course/default.php`. Lo stile limita la larghezza al contenitore mantenendo le proporzioni. Esempio override CSS: `.jlms-course-cover img { max-height: 24rem; width: 100%; object-fit: contain; }`. Conservare l’escaping del percorso/alt e `alt=""` per immagini decorative; non usare campi di bozza o URL media didattici. Il layout codifica i segmenti del percorso e usa la base Joomla, anche con installazione in sottocartella. Il titolo visibile resta `#jlms-course-title`; titolo/descrizione SEO appartengono alla head nativa dopo il controllo di accesso. Il tab amministrativo `#jlms-course-metadata` usa `renderFieldset('metadata')`; gli override devono conservarlo insieme al campo media nativo e alla validazione server.

## Docenti

`#jlms-admin-teachers` e `#jlms-teacher-editor` nelle viste amministrative native; sezione Docenti nella scheda Corso `#jlms-course-teacher-fields`, insieme a copertina e dati principali. Frontend: `#jlms-course-teachers` opzionale, `#jlms-course-teacher-{id}` e `.jlms-course-teacher` per ciascun profilo. Foto con larghezza massima10rem e proporzioni conservate; esempio `.jlms-course-teacher { padding: 1rem; border: 1px solid currentColor; }`. Conservare escaping di nome/bio/alt/link, alt vuoto per foto decorative e proiezione già autorizzata `$this->item['teachers']`. La scheda pubblica usa la proiezione corrente autorizzata; lo storico didattico mantiene gli snapshot. Non aggiungere query dirette al profilo o all’account negli override. Form amministrative e selezione multipla devono conservare token/revisioni, stato e controlli nativi.

## Calendario

Editor: tab `#jlms-course-calendar`, fieldset calendar con controlli nativi. Frontend: sezione opzionale `#jlms-course-calendar`, elenco definizioni con date editoriali correnti, fuso e offset; la disponibilità continua a usare le finestre dell’edizione assegnata. `#jlms-course-window-closed` comunica la finestra corrente non aperta per chi possiede il livello. Conservare `$this->canStartWindow` insieme ai controlli di identità/accesso nei rami del pulsante; gli endpoint ricontrollano sempre la disponibilità. Esempio stile: `#jlms-course-calendar dl { display: grid; gap: .5rem; }`. Nessun valore di bozza, data dal browser o clock client deve autorizzare l’avvio.

## Editor e permessi

Tab amministrativo `#jlms-course-permissions`, regione `#jlms-course-effective-rights`, link condizionale `#jlms-course-options-link` alle opzioni native. Il riepilogo usa un elenco di definizioni; esempio `#jlms-course-effective-rights dl { display: grid; gap: .5rem; }`. Negli override mantenere readOnly/checkedOutByOther, disabilitazione dei campi e toolbar coerente. Il selettore Coursecategory estende il campo Category nativo; non rimpiazzarlo con un elenco privo di controllo ACL. Nessuna modifica del contratto frontend studente.

Nel layout amministrativo Programma, `#jlms-video-setup` identifica il riepilogo dei prerequisiti video vicino al form della lezione.

L’anteprima amministrativa usa `coursepreview/default.php`, con regioni `#jlms-course-preview` e `#jlms-course-preview-lesson`. Il Programma espone il collegamento `#jlms-course-preview-link`.

Nella scheda Permessi dell’editor, `#jlms-course-rules` contiene il campo ACL nativo Joomla e il messaggio sul salvataggio immediato. Gli override devono conservare token, asset effettivo, campo Rules e controlli di autorizzazione.

Nella0.31 la pagina docente usa `teacher/default.php` e il layout `teacher/courses.php`, con `#jlms-teacher-profile`, `#jlms-teacher-presentation`, `#jlms-teacher-fields` e `#jlms-teacher-courses`. Il form frontend del profilo usa `teacheredit/default.php` e `#jlms-teacher-profile-form`. Il Programma pubblicato offre `#jlms-edit-programme-form`. Conservare autorizzazioni, token e campi di revisione negli override.

## Amministrazione — duplicazioni

Nel template administrator/tmpl/curriculum/default.php, #jlms-duplicate-course contiene riepilogo e form della copia corso, #jlms-copy-course-title il titolo. Le azioni di copia modulo/lezione sono nel menu contestuale Azioni e aprono il form selezionato; non assumere form di copia ripetuti sotto ogni elemento. Nessun nuovo asset frontend o stile globale; mantenere task, token, ID di sorgente/destinazione e revisioni negli override. I comandi di modulo/lezione sono visibili solo nel programma di lavoro, quello del corso solo con creazione autorizzata nella categoria.

## Backend — elenco ed editor

administrator/tmpl/courses/default.php: #jlms-admin-courses, #jlms-course-list, #jlms-courses-empty; righe .jlms-admin-course con data-course-id, .jlms-admin-course-title e .jlms-admin-course-actions. Search Tools/grid/multiselect/toolbar e classi responsive del template Joomla; nessun foglio globale aggiunto. administrator/tmpl/course/edit.php: #jlms-course-editor-layout, #jlms-course-editor-sidebar, #jlms-course-programme, #jlms-course-programme-summary, #jlms-course-options. ID precedenti presentazione/accessi/metadati/calendario/permessi/associazioni conservati come regioni, anche se raccolti in meno schede. Adattare gli override che assumono la vecchia gerarchia. Il controllo Salva e apri programma usa il web component toolbar Joomla e il task course.programme, non JavaScript o validazione paralleli.

## Protezione delle modifiche non salvate

Gli override del programma devono mantenere `.jlms-focused-editor` con il form di modifica. Lo script dipende dal form e dai suoi controlli, non da un archivio locale dei contenuti. L'avviso usa la conferma tradotta del browser; richiede JavaScript e interazione utente. Per il comportamento d'uso vedere [AUTHORING.md](AUTHORING.md), per le prove [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

## Editor corso: campi Joomla

La scheda condizionale `jlms-course-fields-tab` contiene la regione `jlms-course-custom-fields` e il gruppo nativo `com_fields`. Gli override del form corso devono renderizzare i fieldset restituiti da Joomla, preservando nomi, validazioni e token; non creare campi paralleli o inviare valori disabilitati. Nessun nuovo stile globale.

## Riepilogo di pubblicazione amministrativo

Override `administrator/templates/NOME/html/com_jlms/publicationreview/default.php`. Regioni: `jlms-publication-review`, tabella `jlms-publication-changes`, form `jlms-publication-confirm-form`, checkbox `jlms-publication-confirm`. Preservare scope, ticket, token Joomla e conferma richiesta. La tabella usa caption/intestazioni e contenitore responsive nativi; nessun nuovo CSS globale o script. Non mostrare corpi, sorgenti video, risposte o nominativi nelle differenze.

### Recupero del programma storico

Nel contenitore amministrativo esistente: `#jlms-restore-programme` è il disclosure nativo, `#jlms-restore-programme-form` il form POST, `#jlms-restore-confirm` il checkbox con label e conferma obbligatoria. Si usano classi Joomla esistenti, senza stili globali nuovi. Gli override della vista curriculum devono conservare token, campi di scope/revisione e conferma; le versioni storiche non mostrano il comando che copia il programma attivo.

### Regole didattiche

La scheda corso è `#jlms-course-teaching-rules`; il programma usa `#jlms-teaching-rules`, heading `#jlms-teaching-rules-heading`, form `#jlms-teaching-rules-form` e campo `#jlms_rules_threshold`. Il form riusa `.jlms-focused-editor` e l’avviso modifiche non salvate; il riordino JS rimane sospeso durante l’editor. Il confronto usa `#jlms-publication-threshold-change`. Nessun nuovo stile globale. Gli override devono conservare task, scope, revisione, token e campo con label; sulle versioni immutabili non renderizzare un form di scrittura.

### Metadati video nell’authoring

Il form lezione contiene `#jlms-video-metadata` (stato aria-live) e `#jlms-video-metadata-preview` (player visibile senza autoplay, nessun progresso studente). Classi Bootstrap ratio esistenti, nessuno stile globale nuovo. Gli override devono renderizzare i campi nativi duration_mode/duration_ticket/sdk_duration_ms, la regione di stato e il pulsante Rileva di nuovo; non reintrodurre la conferma obbligatoria. Il modulo WAM `com_jlms.videoMetadata` opera solo nel form lezione e rispetta consenso, annullamento e cambio sorgente.

### Navigazione amministrativa 0.40

Ingresso diretto nella vista courses esistente (`jlms-admin-courses`); nessun nuovo CSS o menu HTML parallelo. Il submenu è reso da mod_menu/Atum e segue gli override nativi Joomla. Il collegamento Plugin video apre com_plugins e usa markup/override Joomla; non rende la vecchia dashboard.

### Campi corso pubblici (0.41)

I contenitori opzionali `jlms-course-fields-after-title`, `jlms-course-fields-before-content` e `jlms-course-fields-after-content` condividono la classe `jlms-course-fields`. I singoli elementi usano markup/classi dei layout com_fields (`fields-container`, `field-entry` e plugin di tipo), senza CSS globale aggiunto. Gli override nativi sono html/layouts/com_jlms/fields/render.php e html/layouts/com_jlms/field/render.php, con fallback com_fields; il layout corso rimane sovrascrivibile da com_jlms/course/default.php. Non rimuovere i controlli di accesso o trasformare valori editoriali correnti in regole didattiche storiche.


### FE-09 — Catalogo docenti (PR #522 integrata)

La nuova vista `teachers` usa `#jlms-teachers`, `#jlms-teacher-grid`, `#jlms-teachers-pagination` e le classi condivise `.jlms-catalog`, `.jlms-course-grid`, `.jlms-card`, `.jlms-course-card`. Gli hook specifici sono `.jlms-teacher-card`, `__cover`, `__content`, `__title`, `__summary`. Nessun JavaScript obbligatorio, nessuna classe globale nuova. Override Joomla: `templates/NOME/html/com_jlms/teachers/default.php`. Il CSS è ereditato dalla griglia condivisa; verificare su un template Joomla con colonna laterale e zoom 200%. La pubblicazione è server-side via `TeacherProfiles::available`, mai via CSS.


### FE-11 — Opzioni menu senza nuove classi

La pagina `category` usa le medesime classi/ID pubblici già documentati; visibilità del form `#jlms-category-catalog-filters` e del selettore di ordinamento controllata server-side dai parametri menu/globali, senza JS o CSS `display:none` per bloccare i filtri. La gestione delle sezioni `#jlms-subcategories` e `#jlms-category-courses` conserva rendering e paginazioni indipendenti. Nessun hook frontend mutato.

### SCR-01 — ordinamento per data di pubblicazione

Nel catalogo corsi, `filter[order]=published` ordina soltanto le versioni pubblicate e visibili secondo `v.published_at DESC, c.id DESC`: le bozze non cambiano la data pubblica; l'ID stabilizza l'ordine a parità di timestamp e durante la paginazione. Il selettore è localizzato IT/EN, ed è disponibile come valore predefinito nella configurazione globale e nei menu Corsi/Categoria. L'ordinamento ricevuto dal GET è ignorato quando i filtri o il selettore ordinamento non sono abilitati. Si preservano le restrizioni ACL e la query canonica del catalogo. Collaudo reale in `docs/QA_TESTBOOK.md`, test strutturale in `tests/frontend-catalog-options.py`.
