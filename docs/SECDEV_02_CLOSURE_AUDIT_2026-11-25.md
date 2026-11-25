# SECDEV-02 — Audit di chiusura (25 novembre 2026)

## Decisione di accettazione

**Non chiudere ancora SECDEV-02.** Dopo le PR #1027–1028, la verifica PHP_CodeSniffer 4.0.4 con PSR12 ha **0 finding** e la CI su Joomla 6.2.0 è verde, senza plugin compat6. Questi risultati sono reali e conservati.

Il registro canonico contiene **23 servizi CORE**, **47 criteri CORE** (24 `implemented`, 23 `unverifiable`) e **4 criteri SECDEV-02 ancora `unverifiable`**. L'audit di tracciabilità dei servizi non è una certificazione dei criteri funzionali. Per aggiornare `Parziale` a `Completato` occorrono evidenze adeguate anche per gli scoperti e il collaudo manuale applicabile.

### Aggiornamento obbligatorio del target Joomla

Il **24 novembre 2026** è stata pubblicata **Joomla 6.2.1** come release di sicurezza e bugfix. Fonti: [annuncio Joomla](https://www.joomla.org/announcements/release-news/joomla-6-2-1-security-bugfix-release.html), [download e SHA ufficiale](https://downloads.joomla.org/cms/joomla6/6-2-1), [release GitHub e SHA-256](https://github.com/joomla/joomla-cms/releases/tag/6.2.1). Questa PR aggiorna la fixture CI alla patch 6.2.1, conserva il controllo `compat6` disabilitato e la verifica dell'archivio con SHA-256 ufficiale. **Il risultato è da accettare con la CI sull'HEAD definitivo**, senza assumere che il sito reale sia aggiornato.

## Inventario residui CORE-01–23

Fonte unica di stato: `docs/FUNCTIONAL_CRITERIA_1.0.0.json`. Per servizio, le decisioni di uso nativo e le motivazioni sono nel registro `docs/SECDEV_02_CORE_SERVICE_DECISIONS_1.0.0.json`. Le righe qui sotto servono a indirizzare le verifiche, non costituiscono una seconda matrice autoritativa.

| Servizio | Criteri implementati | Criteri da verificare | Verifica residua documentata |
|---|---:|---|---|
| CORE-01 | 1/2 | all_hierarchy | Gerarchia, lingua, permessi e riordino end-to-end |
| CORE-02 | 1/2 | auth_variants | MFA e provider esterni reali |
| CORE-03 | 1/2 | cross_context | Rimozione accessi tra tutti i contesti |
| CORE-04 | 1/3 | record_acl, teacher_edit | Matrice ruoli e protezioni su tutti i record |
| CORE-05 | 1/2 | layout_coverage | Override di ogni vista e modulo distribuito |
| CORE-06 | 1/2 | strings | Copertura totale stringhe e messaggi |
| CORE-07 | 1/2 | menu_matrix | Combinazioni menu, filtri e fallback |
| CORE-08 | 1/2 | scope | Classi dati, export, cancellazione e conservazione su hosting |
| CORE-09 | 1/2 | tags_context | Tutti i contesti e filtri previsti |
| CORE-10 | 1/2 | teacher_scoping | ACL docente e lezione, lingua e accesso |
| CORE-11 | 1/2 | content_events | Eventi plugin e filtro HTML per ogni campo |
| CORE-12 | 1/2 | media_providers | Provider media e assenza di fuga allegati |
| CORE-13 | 1/2 | all_routes | URL diretti, breadcrumb e menu multilingua |
| CORE-14 | 1/2 | index_acl | Invalidazione indice per ACL, stato e lingua |
| CORE-15 | 1/2 | native_workflow | Transizioni e autorizzazioni per tutte le entità |
| CORE-16 | 1/2 | all_entities | Ripristini concorrenti e non mutazione assegnazioni |
| CORE-17 | 2/2 | — | Completezza eventi e politiche retention |
| CORE-18 | 1/2 | smtp | SMTP e template multilingua in ambiente |
| CORE-19 | 1/2 | idempotent_tasks | Ripetizioni, errori e notifiche duplicate |
| CORE-20 | 1/2 | native_completeness | Batch, errori e recupero input nei percorsi residui |
| CORE-21 | 1/2 | private_cache | Cache cross-account su tutti gli endpoint |
| CORE-22 | 1/2 | release_updates | Aggiornamenti e rollback su ambienti target |
| CORE-23 | 1/2 | event_contracts | Payload degli eventi e compatibilità plugin terzi |

La registrazione di `unverifiable` **non prova** che la funzione manchi; significa che manca una verifica integrale del criterio nella sua formulazione originale. Non creare artificiosamente funzionalità già coperte dal core Joomla.

## API Joomla: evidenza reale e limiti dell'inventario

La CI di PR #1028 ha verificato **351 file PHP distribuiti**, **113 simboli Joomla**, **60 chiamate statiche**, e **3 chiamate su istanze direttamente costruite**, senza problemi riscontrati, su Joomla 6.2.0 con compat6 disabilitato. Restano **10.311 siti di chiamata su oggetti bisognosi di analisi di flusso** e **98 chiamate statiche dinamiche non risolte**: questi sono siti di codice censiti, non 10.409 difetti né 10.409 API rimosse. Lo scanner lessicale, per progettazione, **non prova la compatibilità di ogni chiamata dinamica**.

La nuova verifica sulla patch 6.2.1 deve essere acquisita da un run CI successivo. Le prove di conformità PHP non sostituiscono le prove Joomla, ACL, CSRF o di esercizio dei provider terzi.

## Condizioni di chiusura verificabili

1. Verificare l'esito reale della CI Joomla **6.2.1** sulle due matrici PHP/database, pacchetto ZIP, Chromium, compat6 disabilitato, controlli Joomla API e gate PSR12.
2. Riconciliare i 23 criteri CORE ancora `unverifiable`, indicando per ognuno test, configurazioni, motivi di eventuale non pertinenza e risultato. **Non** promuoverli con una dichiarazione collettiva o per il solo PASS di un gate statico.
3. Rendere tracciabile per `SECDEV-02/criterion_01–04` ogni evidenza di soddisfacimento e test, con la distinzione fra runtime verificato e configurazione reale dell'hosting.
4. Registrare separatamente in `docs/QA_TESTBOOK.md` i collaudi manuali pertinenti su Joomla, tra cui permessi editoriali per record, MFA, privacy/retention, SMTP, Task Scheduler, Finder, multilingua e upgrade. Non dichiararli eseguiti senza risultati osservati.
5. Solo quando le prove sono complete, aggiornare lo stato nel registro JSON, il CSV canonico, le percentuali e il report finale. Fino ad allora il requisito resta **Parziale**.

## Controlli negativi

`python3 tests/secdev02-closure-readiness.py --self-test` verifica che una falsa dichiarazione `Completato`, un CORE mancante o una patch CI incoerente non siano accettati. La modalità senza argomenti espone l'inventario ma non scambia `unverifiable` per un errore di esecuzione; `--require-complete` fallisce finché esistono criteri senza prove. Questo controllo aggiuntivo è di **accettazione**, non un sostituto dei test funzionali esistenti.

## Comportamenti da testare

- Installazione e upgrade pacchetto su Joomla 6.2.1, stesso schema/ACL e nessuna perdita dati.
- Rendering corso, categoria, docente, quiz e dashboard nelle due matrici PHP/DB.
- Blocco per compat6 attivo, simboli Joomla rimossi o downgrade della patch.
- Integrità requisiti e inventario CORE; impossibilità di promuovere SECDEV-02 se i residui rimangono.
- Collaudi manuali Joomla, da registrare in un'iterazione di accettazione separata.
