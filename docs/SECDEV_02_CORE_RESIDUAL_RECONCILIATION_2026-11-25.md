# SECDEV-02 — Riconciliazione dei 23 criteri CORE residui

**Data:** 25 novembre 2026 · **Perimetro:** CORE-01–23 · **Stato:** sviluppo/CI separati dall'accettazione manuale.

## Sintesi delle evidenze

- Il registro canonico conta **23 servizi CORE**, con **47 criteri granulari**: **24 implemented** e **23 unverifiable**. I criteri residui sono distribuiti su **22 servizi**, perché **CORE-17 ha già entrambi i criteri implementati**.
- Dei **23 residui**, **17** richiedono ancora lavoro tecnico: verifiche di implementazione, eventuali correzioni e ampliamento della prova automatica/matrice end-to-end; **6** dipendono soprattutto da ambiente Joomla, servizi esterni, policy o accettazione sul sito.
- Il lotto corregge **un difetto di codice trasversale** (rilevante per **CORE-18** e **CORE-19**): il default globale email disabilitato non può impedire l'opt-in esplicito del corso. La query MariaDB/MySQL è provata contro `MailPreferences::enabled` per 5 preferenze × 2 stati globali e mantiene deduplicazione pre-LIMIT.
- **Nessuna promozione automatica** di `unverifiable` e nessun collaudo manuale dichiarato svolto. I 17 casi non pienamente dimostrati costituiscono **lavoro ancora a carico dello sviluppo/CI**, non collaudi da delegare all'utente.

Il documento e il JSON associato sono una **riconciliazione operativa**: non sostituiscono `docs/FUNCTIONAL_CRITERIA_1.0.0.json`, che resta la fonte di verità. Il gate `tests/secdev02-core-residual-split.py` impedisce omissioni, duplicazioni e incoerenze.

## Matrice esatta: sviluppo/CI distinto dai collaudi manuali

| Criterio residuo | Stato della prova | Intervento o prova a carico dello sviluppo | Collaudo dell'utente (NON ESEGUITO) |
|---|---|---|---|
| `CORE-01/all_hierarchy` | Sviluppo/CI da completare | Ampliare le prove automatizzate su permessi, lingua, ordinamento e antenati delle categorie in ogni entità editoriale. | Collaudare manualmente gerarchia e ricollocazioni con ruoli e lingue distinti. |
| `CORE-02/auth_variants` | Sito/esterno | Nessuna reimplementazione di com_users/MFA; integrare solo eventuali difetti riproducibili in flussi JD Learn. | Provare login, recupero account, blocchi, MFA e provider effettivamente configurati. |
| `CORE-03/cross_context` | Sviluppo/CI da completare | Estendere una matrice CI a revoca ruoli/livelli durante iscrizione, aula, catalogo e navigazione personale. | Verificare revoca sessione e livelli di accesso con differenti gruppi Joomla. |
| `CORE-04/record_acl` | Sviluppo/CI da completare | Prove native di batch con permessi misti, checkout concorrente e rollback per record/docente. | Provare selezioni multiple con un solo record vietato e ruoli delegati. |
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
| `CORE-14/index_acl` | Sviluppo/CI da completare | Prove di reindicizzazione Finder con cambio categoria/lingua/stato/ACL e lettura indice reale. | Ricostruire e consultare Smart Search con articoli protetti e cambio permessi. |
| `CORE-15/native_workflow` | Sviluppo/CI da completare | Estendere transizioni/ACL native a lezioni, docenti, percorsi e selezioni multiple. | Provare permessi/toolbar e blocchi di transizione nei flussi editoriali. |
| `CORE-16/all_entities` | Sviluppo/CI da completare | Prove di history/checkout concorrente, restore per entità e immutabilità delle versioni assegnate. | Provare check-in/out, ripristini storici e lezioni assegnate con due editor. |
| `CORE-18/smtp` | Sito/esterno | Correzione in questa PR: il promemoria scadenza rispetta l'opt-in per corso anche con default globale off; la consegna SMTP resta prova esterna. | Provare SMTP e modelli IT/EN, errori, retry e opt-in/opt-out per corso. |
| `CORE-19/idempotent_tasks` | Sviluppo/CI da completare | Correzione in questa PR: selezione SQL e MailPreferences concordano per default globale e override; ampliare il test di retry scheduler multi-batch. | Eseguire task su database realistico, disabilitazioni e ripetizioni senza duplicati. |
| `CORE-20/native_completeness` | Sviluppo/CI da completare | Inventariare toolbar, filtri, batch, errori e recupero input e aggiungere test HTTP per i casi non coperti. | Provare tutte le azioni dell'amministrazione in più ruoli e browser. |
| `CORE-21/private_cache` | Sviluppo/CI da completare | Estendere manifesto HTTP a nuove route personali e verificare header/errori con sessioni isolate. | Verificare cache e sessione fra account differenti anche dietro CDN/proxy. |
| `CORE-22/release_updates` | Sito/esterno | I workflow di rilascio/upgrade sono già implementati; rollback e migrazioni cross-hosting richiedono prove ambientali. | Provare update server e ripristino backup/migrazione su hosting di destinazione. |
| `CORE-23/event_contracts` | Sito/esterno | Contratto provider e test esempio presenti; eventuale ampliamento eventi va giustificato da consumer/plugin terzi reali. | Provare plugin terzi, sottoscrizione eventi e payload minimi su installazione reale. |

## Criterio di chiusura

Un criterio può diventare `implemented` nella matrice **solo quando** la parte tecnica e la prova pertinente risultano documentate. Il completamento del codice/CI **non equivale** all'accettazione manuale sul sito. I criteri con dipendenze esterne richiedono invece prove con configurazioni, provider e hosting effettivi; in mancanza di tali prove restano `unverifiable`, senza generare automaticamente un nuovo requisito software.

**Collaudi manuali:** `docs/QA_TESTBOOK.md` (stato NON ESEGUITO).
**Evidenze di implementazione e CI:** `docs/SECDEV_02_CORE_SERVICE_DECISIONS_1.0.0.json`, `tests/secdev02-core-ledger.py`, `tests/secdev02-core-ci-wiring.py`, `tests/secdev02-core19-expiry-reminders.py`, `tests/joomla-smoke.php`.

### Difetto corretto in questo lotto: preferenza promemoria email

Prima, `CourseExpiryReminderService::enqueueDue()` terminava subito quando `mail_expiry_enabled` era false, malgrado un corso potesse avere `mail_preferences.expiry=enabled`. Ora seleziona solo gli opt-in espliciti in questo scenario, oppure gli override abilitati e quelli ereditati se il default globale è true. Le righe già accodate e quelle disabilitate vengono escluse **prima del LIMIT**. Le combinazioni valide e invalide vengono confrontate con la funzione di dominio durante i test nativi su MySQL 8.4 e MariaDB 10.6.
