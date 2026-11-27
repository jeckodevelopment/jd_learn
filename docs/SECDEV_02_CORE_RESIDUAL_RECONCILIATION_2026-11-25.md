# SECDEV-02 — Riconciliazione dei 23 criteri CORE residui

**Data:** 25 novembre 2026 · **Perimetro:** CORE-01–23 · **Stato:** sviluppo/CI separati dall'accettazione manuale.

## Sintesi delle evidenze

**Aggiornamento 27/11/2026 — PR #1091 candidata:** il test `tests/joomla-teachers.php` copre ora lo snapshot effettivo dei tag nativi, inclusa la lista vuota, e il relativo ripristino per un docente assegnato. Questo chiude solo una combinazione concreta del residuo CORE-16; i casi cross-entity, checkout concorrente e restore delle altre relazioni restano sviluppo/CI aperti. Accettazione manuale ancora in attesa.

**Aggiornamento 27/11/2026 — CORE-14/index_acl ([PR #1099 integrata](https://github.com/jeckodevelopment/jd_learn/pull/1099), merge `9f1a88dc59ab3ec0b3f7329cb08458e2e5345c2a`):** i test attraversano gli eventi nativi Finder di salvataggio corso/categoria, cambio stato e rimozione e verificano l’effettiva presenza/rimozione dei link persistiti. Le variazioni di ACL, lingua e pubblicazione devono togliere gli indici non più pubblici; il ripristino li ricrea. Accettazione manuale Smart Search sul sito resta pendente. CI #4948 4/4 e gate CSV #2299 superati; il criterio esce dai residui sviluppo/CI, mentre l’accettazione manuale Smart Search resta pendente. Residui correnti: 12 criteri tecnici e 6 esterni/manuali.

**Aggiornamento 27/11/2026 — CORE-03/cross_context, PR #1097 integrata:** la matrice CI verifica revoca indipendente del viewing access level tra aula, progressi/replay iscrizione, cronologia, ripresa, scheda e catalogo, con restore nella stessa sessione. CI #4939 4/4 e gate CSV #2297 superati. Residui correnti: 13 criteri tecnici e 6 esterni/manuali; CORE-03 esce dai residui sviluppo/CI ma resta accettazione manuale pendente.

**Aggiornamento 27/11/2026 — CORE-04/record_acl:** la CI candidata aggiunge al test editoriale corsi la matrice dei docenti: `core.edit.own` senza `core.edit`, transizione singola autorizzata e batch misto con diniego del secondo record e rollback del primo. Percorsi già coperti da prova analoga di ACL per record e batch atomico. `TeacherModel::changeStates()` ora condivide `TeacherProfiles::canEdit()` con l’editor singolo, conservando il controllo `core.edit.state` e checkout. Registro: CORE-04/record_acl `implemented`; CORE-04/teacher_edit resta `unverifiable`; accettazione Joomla non eseguita. Conteggio CORE corrente 29 implemented / 18 unverifiable.


- Fotografia al **25 novembre 2026**: il registro contava **23 servizi CORE**, con **47 criteri granulari**, **24 implemented** e **23 unverifiable**. I criteri residui erano distribuiti su **22 servizi**, perché **CORE-17 aveva già entrambi i criteri implementati**.
- Nella stessa fotografia del 25 novembre, dei **23 residui**, **17** richiedevano ancora lavoro tecnico e **6** dipendevano soprattutto da ambiente Joomla, servizi esterni, policy o accettazione sul sito. Questi conteggi sono storici; la fotografia corrente è **27 implemented / 20 unverifiable** dopo le riconciliazioni successive.
- Il lotto corregge **un difetto di codice trasversale** (rilevante per **CORE-18** e **CORE-19**): il default globale email disabilitato non può impedire l'opt-in esplicito del corso. La query MariaDB/MySQL è provata contro `MailPreferences::enabled` per 5 preferenze × 2 stati globali e mantiene deduplicazione pre-LIMIT.
- **Nessuna promozione automatica** di `unverifiable` e nessun collaudo manuale dichiarato svolto. I 17 casi non pienamente dimostrati costituiscono **lavoro ancora a carico dello sviluppo/CI**, non collaudi da delegare all'utente.

Il documento e il JSON associato sono una **riconciliazione operativa**: non sostituiscono `docs/FUNCTIONAL_CRITERIA_1.0.0.json`, che resta la fonte di verità. Il gate `tests/secdev02-core-residual-split.py` impedisce omissioni, duplicazioni e incoerenze.

## Matrice esatta: sviluppo/CI distinto dai collaudi manuali

| Criterio residuo | Stato della prova | Intervento o prova a carico dello sviluppo | Collaudo dell'utente (NON ESEGUITO) |
|---|---|---|---|
| `CORE-01/all_hierarchy` | Sviluppo/CI da completare | Ampliare le prove automatizzate su permessi, lingua, ordinamento e antenati delle categorie in ogni entità editoriale. | Collaudare manualmente gerarchia e ricollocazioni con ruoli e lingue distinti. |
| `CORE-02/auth_variants` | Sito/esterno | Nessuna reimplementazione di com_users/MFA; integrare solo eventuali difetti riproducibili in flussi JD Learn. | Provare login, recupero account, blocchi, MFA e provider effettivamente configurati. |
| `CORE-04/record_acl` | Implementazione software provata in CI | Corsi, percorsi e docenti hanno test di permesso per record e selezioni miste con rollback; i docenti coprono anche `core.edit.own` senza `core.edit`. | Accettazione manuale da eseguire con ruoli delegati e selezioni miste. |
| `CORE-04/teacher_edit` | Sviluppo/CI da completare | Prove di salvataggio forgiato e modifica profilo proprio, pubblicazione, categoria, lingua e tag. | Testare profilo proprio e deleghe con operazioni edit, publish e denied. |
| `CORE-05/layout_coverage` | Sviluppo/CI da completare | Censire ogni vista e modulo distribuito, esercitare override e documentare i contratti DOM/CSS. | Collaudare override su template e viewport desktop/mobile. |
| `CORE-06/strings` | Sviluppo/CI da completare | Ampliare scanner e fixture runtime per chiavi dinamiche, notifiche, messaggi di errore e email IT/EN. | Provare language overrides e interfacce con IT/EN, anche per email personalizzate. |
| `CORE-07/menu_matrix` | Sviluppo/CI da completare | Parametrizzare menu e associazioni per lingua, accesso, SEF e contesti didattici. | Collaudare menu IT/EN, URL diretti e fallback multilingua. |
| `CORE-08/scope` | Sito/esterno | Attendere policy di conservazione per classe dati; verificare export/rimozione solo dopo perimetro approvato. | Validare la policy del titolare, scadenze, download/rimozione su hosting reale. |
| `CORE-09/tags_context` | Sviluppo/CI da completare | Esercitare persistenza nativa tag corso/docente e chiarire dalla specifica le eventuali altre entità previste, prima di aggiungere contesti. | Provare aggiunta, cambio e rimozione tag nelle entità approvate con ACL. |
| `CORE-10/teacher_scoping` | Sviluppo/CI da completare | Prove combinatorie su campi docente/lezione con ACL, lingua, gruppi e stato del contenuto. | Verificare field groups, lingua e visualizzazione per guest/docente/studente. |
| `CORE-11/content_events` | Sviluppo/CI da completare | Coprire i plugin content nei punti editor di corso, lezione e docente; controllare sanitizzazione pre/post evento. | Provare plugin content installati, editor nativo e filtri nei contesti previsti. |
| `CORE-12/media_providers` | Sito/esterno | I provider hanno un contratto implementato; nuove correzioni di codice richiedono riproduzione di fallimenti concreti. | Collaudare provider esterni configurati e consegna privata di allegati attraverso hosting/proxy. |
| `CORE-13/all_routes` | Sviluppo/CI da completare | Completare matrice di route dirette, breadcrumb, SEF, alias modificati, lingua e ACL per tutte le viste. | Verificare link diretti e breadcrumb con ruoli, menu e lingue. |
| `CORE-15/native_workflow` | Sviluppo/CI da completare | Provare con CI il blocco delle scritture su programma/lezioni durante la revisione nativa del corso; completare la matrice di transizioni e ACL per docenti e percorsi, incluse le selezioni multiple. Le lezioni ereditano il workflow del corso perché appartengono a una versione didattica immutabile. | Provare permessi/toolbar e blocchi di transizione nei flussi editoriali. |
| `CORE-16/all_entities` | Sviluppo/CI da completare | PR #1091 aggiunge snapshot taggato/vuoto e restore dei tag Joomla nel modello docente, verificando che la versione assegnata non cambi; restano prove cross-entity di history/checkout concorrente e restore di tutte le relazioni. | Provare check-in/out, ripristini storici e lezioni assegnate con due editor. |
| `CORE-18/smtp` | Sito/esterno | Correzione in questa PR: il promemoria scadenza rispetta l'opt-in per corso anche con default globale off; la consegna SMTP resta prova esterna. | Provare SMTP e modelli IT/EN, errori, retry e opt-in/opt-out per corso. |
| `CORE-19/idempotent_tasks` | Sviluppo/CI da completare | Correzione in questa PR: selezione SQL e MailPreferences concordano per default globale e override; ampliare il test di retry scheduler multi-batch. | Eseguire task su database realistico, disabilitazioni e ripetizioni senza duplicati. |
| `CORE-20/native_completeness` | Sviluppo/CI da completare | Inventariare toolbar, filtri, batch, errori e recupero input e aggiungere test HTTP per i casi non coperti. | Provare tutte le azioni dell'amministrazione in più ruoli e browser. |
| `CORE-21/private_cache` | Sviluppo/CI da completare | Estendere manifesto HTTP a nuove route personali e verificare header/errori con sessioni isolate. | Verificare cache e sessione fra account differenti anche dietro CDN/proxy. |
| `CORE-22/release_updates` | Sito/esterno | I workflow di rilascio/upgrade sono già implementati; rollback e migrazioni cross-hosting richiedono prove ambientali. | Provare update server e ripristino backup/migrazione su hosting di destinazione. |
| `CORE-23/event_contracts` | Sito/esterno | Contratto provider e test esempio presenti; eventuale ampliamento eventi va giustificato da consumer/plugin terzi reali. | Provare plugin terzi, sottoscrizione eventi e payload minimi su installazione reale. |

## Aggiornamento 27 novembre 2026 — CORE-03/cross_context

La nuova regressione copre la revoca indipendente del viewing access level Joomla mentre l’utente resta nel gruppo: il test servizio blocca aula, completamento e replay iscrizione; il test HTTP blocca ripresa, aula e corpi privati in scheda/catalogo, preserva lo storico e verifica il ripristino nella stessa sessione. `CORE-03/cross_context` passa a `implemented` nel perimetro dei contesti core coperti. Restano accettazione manuale su ruoli ulteriori, URL SEF e installazione di destinazione. Il residuo tecnico scende da 14 a 13 criteri; le verifiche esterne restano 6.

## Criterio di chiusura

Un criterio può diventare `implemented` nella matrice **solo quando** la parte tecnica e la prova pertinente risultano documentate. Il completamento del codice/CI **non equivale** all'accettazione manuale sul sito. I criteri con dipendenze esterne richiedono invece prove con configurazioni, provider e hosting effettivi; in mancanza di tali prove restano `unverifiable`, senza generare automaticamente un nuovo requisito software.

**Collaudi manuali:** `docs/QA_TESTBOOK.md` (stato NON ESEGUITO).
**Evidenze di implementazione e CI:** `docs/SECDEV_02_CORE_SERVICE_DECISIONS_1.0.0.json`, `tests/secdev02-core-ledger.py`, `tests/secdev02-core-ci-wiring.py`, `tests/secdev02-core19-expiry-reminders.py`, `tests/joomla-smoke.php`.

### Difetto corretto in questo lotto: preferenza promemoria email

Prima, `CourseExpiryReminderService::enqueueDue()` terminava subito quando `mail_expiry_enabled` era false, malgrado un corso potesse avere `mail_preferences.expiry=enabled`. Ora seleziona solo gli opt-in espliciti in questo scenario, oppure gli override abilitati e quelli ereditati se il default globale è true. Le righe già accodate e quelle disabilitate vengono escluse **prima del LIMIT**. Le combinazioni valide e invalide vengono confrontate con la funzione di dominio durante i test nativi su MySQL 8.4 e MariaDB 10.6.


## Aggiornamento 26 novembre 2026 — residui dopo la copertura CORE-21

La PR #1078 riconcilia `CORE-21/private_cache` come implementato sul perimetro del codice e della CI: le route private censite sono sottoposte ai controlli fail-closed e la suite HTTP verifica tre identità. Il criterio esce quindi dai residui di sviluppo/CI. Restano **16 criteri tecnici** e **6 verifiche ambientali/esterne**; il servizio CORE-21 rimane Parziale per invalidazione multilivello dei metadati e per prove hosting proxy/CDN, che non sono state dichiarate svolte.
