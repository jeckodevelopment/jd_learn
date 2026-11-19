# Inventario contratti frontend — FE-10 (ricognizione 16 novembre 2026)

> **Fonte tecnica dei selettori già implementati.** Questa guida non equivale alla specifica UX futura ([FRONTEND_UX_SPECIFICATION.md](FRONTEND_UX_SPECIFICATION.md)) e non dichiara collaudo Joomla. Il contratto CSS pubblico dettagliato resta in [FRONTEND_CSS.md](FRONTEND_CSS.md). Prima di rimuovere/renominare un ID, classe o data-hook, cercarne l'uso in markup, JS, CSS, test e override.

## Viste pubbliche: contratto e override

| Vista | Layout PHP nativo | Radice DOM corrente | Componenti condivisi | Stato |
|---|---|---|---|---|
| Catalogo corsi | `courses/default.php` | `#jlms-courses` / `.jlms-catalog` | `.jlms-course-grid`, `.jlms-card.jlms-course-card` | Implementato |
| Scheda corso | `course/default.php` | `#jlms-course` | `.jlms-module`, `.jlms-lesson`, `.jlms-material` | Implementato |
| Catalogo percorsi | `pathways/default.php` | `#jlms-pathways` | `.jlms-course-grid`, `.jlms-card.jlms-course-card` | Implementato, stile percorso dedicato in FE-07 |
| Dettaglio percorso | `pathway/default.php` | `#jlms-pathway` | `.jlms-course-list__item` | Implementato FE-07; collaudo Joomla pendente |
| Categorie | `categories/default.php` | `#jlms-categories` | `.jlms-category-grid`, `.jlms-card.jlms-category-card` | Implementato |
| Dettaglio categoria | `category/default.php` | `#jlms-category` | `.jlms-course-grid`, `.jlms-course-card` | Implementato |
| La mia formazione | `dashboard/default.php` | `#jlms-dashboard`, `#jlms-dashboard-summary` | `.jlms-summary-stats`, `.jlms-card.jlms-my-course` | Quarta tranche FE-05 candidata: sola sintesi, collaudo pendente |
| I miei corsi | `mycourses/default.php` | `#jlms-dashboard` / `.jlms-my-courses-view` | `dashboard/_courses.php` | Implementato; collaudo Joomla pendente |
| I miei attestati | `mycertificates/default.php` | `#jlms-my-certificates-view` / `.jlms-my-certificates-view` | `dashboard/_certificates.php` | PR #510; collaudo Joomla pendente |
| Preferenze personali | `preferences/default.php` | `#jlms-preferences-view` | `dashboard/_preferences.php` e servizio consenso esistente | Candidata FE-05; collaudo pendente |
| Aula | `classroom/default.php` | `#jlms-classroom` | `.jlms-classroom-layout`, `.jlms-classroom-lesson`, `.jlms-material` | Implementato, redesign FE-03/04 |
| Quiz | `quiz/default.php` | `#jlms-quiz` | `.jlms-quiz-question`, `.jlms-quiz-clear` | Implementato, flusso stabilizzato senza estensioni quiz |
| Docente | `teacher/default.php` | `#jlms-teacher-profile` | `#jlms-teacher-presentation`, `#jlms-teacher-courses` | Implementato |
| Modifica docente | `teacheredit/default.php` | `#jlms-teacher-profile-form` | Campi Joomla native | Implementato |
| Elenco docenti | `teachers/default.php` | `#jlms-teachers` | `.jlms-course-grid`, `.jlms-teacher-card` | Implementato FE-09; collaudo Joomla pendente |

I template nativi sono sotto `component/site/tmpl/`; gli override si trovano in `templates/NOME/html/com_jlms/VISTA/default.php`. I CSS vengono distribuiti via Web Asset Manager (`component/media/joomla.asset.json`): `com_jlms.site` punta a `component/media/css/site.css`. Non richiedere modifiche ai file del core Joomla.

## Viste aggiuntive distribuite

| Vista | Layout PHP | Radice DOM | Contratti da preservare |
|---|---|---|---|
| Anteprima pubblica di lezione | `lessonpreview/default.php` | `#jlms-public-lesson-preview` | `.jlms-lesson__body`, `.jlms-public-preview-materials`, `data-course-id` |
| Revoca consenso email | `mailconsentrevoke/default.php` | `#jlms-mail-consent-revoke` | `#jlms-mail-consent-revoke-form`, `#jlms-mail-consent-link-completed`, `#jlms-mail-consent-link-invalid` |

I tre stati della revoca sono alternativi, non simultanei. L'inventario non implica accettazione manuale ne' consente di aggirare ACL, token o protezioni server.

## Contratti JavaScript indispensabili

| Modulo JS | Hook DOM / contratto | Layout | Conseguenza se rimosso |
|---|---|---|---|
| `classroom-outline.js` | `#jlms-classroom-outline-toggle`, `#jlms-classroom-outline`, `aria-expanded`, `data-show-label`, `data-hide-label` | `classroom` | Toggle non funzionante |
| `lesson-progress.js` | `#jlms-lesson-progress-status`, `#jlms-classroom-lesson-{id} > span` | `classroom` | Stato aggiornato non visibile |
| `audio-progress.js` | `#jlms-audio-player`, `#jlms-audio-progress-status` | `classroom` | Progressi audio/stato non funzionanti |
| `video.js` | `#jlms-player`, `#jlms-player-status`, `#jlms-video-region`, `#jlms-video-speed[-control]`, `#jlms-video-language[-control]`, `#jlms-progress-meter` | `classroom` | Player/controlli/tracciamento compromessi |
| `quiz.js` | `#jlms-quiz`, `#jlms-quiz-form`, `#jlms-quiz-status`, `#jlms-quiz-warning`, `#jlms-quiz-extension`, `#jlms-quiz-duration`, `#jlms-quiz-save`, `#jlms-quiz-start`, `#jlms-quiz-timer`, `.jlms-quiz-clear`, `[data-question-id]` | `quiz` | Risposte, timer, salvataggio e feedback compromessi |

Questa tabella è una **mappa delle dipendenze**, non autorizza a rinominare unilateralmente le stringhe. Attributi `data-` già esistenti come `data-enrollment-id`, `data-course-id`, `data-question-id` e `data-layout` restano contratti di compatibilità. La convenzione desiderata `data-jlms-*` è per **nuovi** hook; la migrazione dei legacy va fatta atomicamente insieme agli script e ai test.

## CSS: baseline da preservare e debito tecnico

- Token esistenti: `--jlms-color-primary`, `--jlms-color-text`, `--jlms-color-surface`, `--jlms-space-unit`, `--jlms-radius`, `--jlms-content-width`.
- Attualmente le definizioni **sono in `:root`**: possono essere ereditate anche da elementi fuori da JD Learn. Non riscrivere il default in un selettore più specifico senza prima verificare le personalizzazioni template già basate su `:root`; la migrazione è un intervento separato con test cascata, fallback e override.
- Tutte le nuove regole visive devono essere limitate alle radici JD Learn; `.jlms-card` è una **classe grafica**, non un selettore usato dal JS.
- Le griglie `.jlms-course-grid`, `.jlms-category-grid`, `.jlms-my-course-grid` e gli ID di pagina hanno già stili; non moltiplicare versioni di uno stesso componente. Unificazione ulteriore da discutere nei rispettivi FE senza cambiare selettori di terze parti.
- Il frontend oggi usa nel catalogo anche un selettore `data-layout` e la variante `.jlms-course-grid--list`: la UX approvata preferisce la griglia, ma rimuovere la vista elenco richiede una decisione di compatibilità, non è parte di FE-10.

## Criteri di compatibilità per ogni PR frontend

1. **Rilevare:** consultare `FRONTEND_CSS.md`, questo inventario e `FRONTEND_UX_SPECIFICATION.md`, poi cercare ogni ID/classe nei layout/JS/CSS.
2. **Modificare:** aggiungere nuove classi stilistiche senza rimuovere le precedenti; mantenere ID univoci e i data-hook esistenti. Non fare DOM accesso/POST tramite classi decorative.
3. **Testare:** eseguire `python3 tests/frontend-css-contract.py` e `python3 tests/frontend-contract-hooks.py`, oltre alla CI completa. Gli script verificano la corrispondenza dei contratti *statici*: non sostituiscono browser reale.
4. **Collaudare in Joomla:** CSS personalizzato sul template, override precedenti, SEF, IT/EN, ACL, categorie con contenuti riservati, video/audio/quiz e vista stretta con moduli laterali.
5. **Tracciare:** per ogni PR runtime, aggiornare `REQUIREMENTS_STATUS.csv`, changelog, testbook e contratto CSS; distinguere il progresso implementativo dall'accettazione.

**Residuo FE-10 dopo questa ricognizione:** valutazione della cascata e dei token CSS globali, audit degli hook JS non frontend e verifica visuale su più template Joomla; le funzionalità FE-01–FE-09 restano pianificate separatamente.


FE-06 candidata: partial site/tmpl/dashboard/_personal_navigation.php condiviso dalle quattro viste personali; #jlms-personal-navigation, .jlms-personal-navigation__list, .jlms-personal-navigation__link e .jlms-personal-navigation__current; impostazione menu show_personal_navigation=0 per default; senza JavaScript. Collaudo Joomla pendente.


FE-07 candidata: catalogo percorsi `pathways/default.php` con #jlms-pathways e #jlms-pathway-grid, `.jlms-pathway-card__cover`, `.jlms-pathway-card__title`. Dettaglio `pathway/default.php`: #jlms-pathway, #jlms-pathway-cover, #jlms-pathway-sequence, #jlms-pathway-courses.jlms-pathway-timeline, `.jlms-pathway-timeline__item`, `.jlms-pathway-timeline__card`, `.jlms-pathway-timeline__step`; ID stabili per indice. Immagine facoltativa validata nel backend e proiettata dal servizio read-only; progresso soltanto autenticato. Nuovo menu di dettaglio. Collaudo Joomla pendente.


FE-08 integrata e FE-11 aggiornata: `category/default.php` conserva `#jlms-category`, `#jlms-subcategories` e `#jlms-category-courses` con paginazioni distinte. Le sezioni e i rispettivi ID sono condizionali; sottocategorie `.jlms-card.jlms-category-card.jlms-subcategory`, corsi `.jlms-card.jlms-course-card`. Due opzioni indipendenti `category_show_subcategories` e `category_show_courses` con default globale 1, override menu Globale/Sì/No valutati da CategoryOptions solo per la categoria corrispondente. FE-11 ha uniformato i fallback; collaudo Joomla pendente. Collaudo Joomla pendente.


FE-12: il gate strutturale in `tests/frontend-release-gate.py` verifica alcuni invarianti del presente inventario. Il registro di accettazione visuale, funzionale e di sicurezza resta `docs/FRONTEND_FE12_ACCEPTANCE.md`; nessuna prova statica certifica WCAG o override su un sito reale.
