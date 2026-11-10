# JD Learn Credentials — Piano di implementazione e milestone GitHub

**Versione:** 1.0 · **Data:** 10 novembre 2026 · **Stato:** piano approvato come proposta operativa, sviluppo non iniziato
**Destinazione:** **successiva a JD Learn 1.0.0**, con versionamento di release Credentials ancora da determinare.

> Il piano contiene **21 PR principali** per la prima release completa (C0–C4) e **3 PR evolutive facoltative** (C5). Gli ID CRE-01…CRE-24 sono *identificativi di lavoro*, non numeri GitHub assegnati. Il conteggio è una stima, non un vincolo rigido o un avanzamento. Non modificare la matrice/percentuale dei **210 requisiti di JD Learn 1.0.0**.

## 1. Documenti sorgente

- [Specifica funzionale Open Badges 3.0](specifications/JD_LEARN_CREDENTIALS_FUNCTIONAL_SPEC.md)
- [Progettazione tecnica T-001–T-007](specifications/JD_LEARN_CREDENTIALS_TECHNICAL_DESIGN.md)
- [Perimetro JD Learn 1.0.0](RELEASE_SCOPE.md)
- [Politica CI](CI_POLICY.md)

**Vincolo architetturale:** Credentials è un sottosistema **nativo di `com_jlms`**, distribuito nel medesimo `pkg_jlms` senza un secondo componente né un secondo prodotto. Estensioni mediante servizi e plugin pertinenti inclusi nel pacchetto. Ogni installazione Joomla rimane un emittente/verificatore autonomo.

## 2. Milestone previste

| Milestone GitHub suggerita | PR | Criterio di chiusura | Dipendenza |
|---|---:|---|---|
| **Credentials C0 — Architettura e PoC** | 2 | Contratti ed EdDSA Open Badges 3.0 con verifica indipendente documentata | JD Learn 1.0.0 stabile |
| **Credentials C1 — Emissione** | 7 | Da corso/percorso idoneo a credenziale firmata, persistente, scaricabile | C0 |
| **Credentials C2 — Verifica e ciclo di vita** | 6 | Verificatore pubblico, revoca/status, scadenza, privacy e chiavi | C1 |
| **Credentials C3 — Studente e LinkedIn** | 4 | Area badge, share/Add to Profile, notifiche, UX accessibile | C2 |
| **Credentials C4 — Collaudo e release** | 2 | Tutti i casi obbligatori validati e pacchetto distribuibile | C3 |
| **Credentials C5 — Evoluzioni (opzionale)** | 3 | Integrazioni avanzate specificate/accettate separatamente | C4 |

**Nota GitHub:** le milestone servono a raggruppare future issue e PR; la loro creazione e l'associazione delle PR costituiscono operazioni distinte da questo documento. Non impostare scadenze fittizie. La milestone C5 non blocca la release completa.

## 3. Backlog ordinato di PR

Una PR è un incremento ragionevolmente integrabile, con codice, test, documentazione e collaudo descritti; la nomenclatura delle PR effettive dipenderà dai numeri assegnati da GitHub.

### C0 — Fondamenta tecniche (2 PR)

**Exit gate:** Contratti software e conformità crittografica dimostrata con verificatore indipendente.

| ID | PR prevista e deliverable | Req. principali | Test correlati | Dipendenze | Evidenza minima di accettazione |
|---|---|---|---|---|---|
| **CRE-01** | **Contratti core ed eventi** — Interfacce servizi Credentials, evento affidabile di completamento, boundary e feature flag (senza abilitare badge) | CRE-083,CRE-100 | AC-CRE-021 | JD Learn stabilizzato; convenzioni DI/eventi | Contract test e test regressione core senza Credentials |
| **CRE-02** | **PoC Open Badges 3.0 e EdDSA** — Generare/firma/verificare credenziale JSON-LD con suite conforme candidata; tampering, document loader sicuro, chiavi e status proof test-vector | CRE-034,CRE-040,CRE-041 | AC-CRE-006,AC-CRE-007,AC-CRE-008,AC-CRE-022 | CRE-01 | Verifica positiva con software indipendente; decision record su librerie/proof |

### C1 — Emissione credenziali (7 PR)

**Exit gate:** Da evento idoneo a credenziale firmata, persistente ed esportabile.

| ID | PR prevista e deliverable | Req. principali | Test correlati | Dipendenze | Evidenza minima di accettazione |
|---|---|---|---|---|---|
| **CRE-03** | **Schema DB e upgrade** — Migrazioni #__jlms_credential_*; vincoli DB, audit e install/upgrade conservativi | CRE-081,CRE-082,CRE-103 | AC-CRE-022 | CRE-01 | Test su MySQL/MariaDB, upgrade senza perdita dati e isolamento |
| **CRE-04** | **Emittenti e key management iniziale** — CRUD/ACL emittenti, riferimenti chiavi fuori webroot, identità pubblica stabile | CRE-010,CRE-011,CRE-012,CRE-040 | AC-CRE-001,AC-CRE-024 | CRE-02,CRE-03 | Profilo emittente verificabile e zero segreti esposti |
| **CRE-05** | **Modelli e versioni** — CRUD Joomla, media validati, snapshot immutabili, assegnazione al corso/percorso | CRE-020,CRE-021,CRE-022 | AC-CRE-002,AC-CRE-019 | CRE-03,CRE-04 | Template versionato senza alterare già emessi |
| **CRE-06** | **Motore di idoneità** — Interfaccia criteri, completamento corso e percorso come priorità, manuale e predisposizione regole avanzate; collegamento ai risultati consolidati | CRE-022,CRE-023 | AC-CRE-003,AC-CRE-019 | CRE-01,CRE-05; completamento percorsi deterministico | Prove corso/percorso e snapshot; se percorso non stabile, dipendenza da risolvere prima dell'accettazione C1 |
| **CRE-07** | **Scheduler, outbox e idempotenza** — Job transazionali persistenti, lease/concorrenza, retry/backoff, correlazione eventi e osservabilità | CRE-030,CRE-032,CRE-033,CRE-102 | AC-CRE-004,AC-CRE-020,AC-CRE-025 | CRE-03,CRE-06 | Nessun doppione anche con due worker e crash |
| **CRE-08** | **Emissione e firma produttiva** — Orchestrazione sicura, proof, JSON-LD immutabile, download per proprietario e errori recuperabili | CRE-030,CRE-034,CRE-041 | AC-CRE-003,AC-CRE-006,AC-CRE-007,AC-CRE-008 | CRE-02,CRE-04,CRE-05,CRE-07 | Verificatore esterno riconosce output reale da JD Learn |
| **CRE-09** | **Emissione manuale e audit** — Backend emissione autorizzata, motivazione e registri, controlli di duplicazione | CRE-031,CRE-080 | AC-CRE-005,AC-CRE-024 | CRE-08 | Emetti e consulta audit con ruoli distinti |

### C2 — Verifica e ciclo di vita (6 PR)

**Exit gate:** Verificatore pubblico autonomo e stato credenziale attendibile e interoperabile.

| ID | PR prevista e deliverable | Req. principali | Test correlati | Dipendenze | Evidenza minima di accettazione |
|---|---|---|---|---|---|
| **CRE-10** | **Rotte pubbliche e risorse emittente** — Routing Joomla SEF per verify, credential, issuers, status, ID opachi e stabilità link | CRE-050,CRE-053,CRE-054 | AC-CRE-012,AC-CRE-013,AC-CRE-026 | CRE-04,CRE-08 | Pagine navigabili in anonimo dove consentito, no enumerazione |
| **CRE-11** | **Verificatore credenziali** — Controlli schema, proof, emittente, date e stato; rappresentazione di esito non verificabile | CRE-041,CRE-052 | AC-CRE-007,AC-CRE-008,AC-CRE-009,AC-CRE-023 | CRE-10 | Payload manomesso o status irraggiungibile non è mai valido |
| **CRE-12** | **Revoca e status list** — Revoca motivata con ACL/audit, status list interoperabile, versioni e cache coerente | CRE-042,CRE-060,CRE-061 | AC-CRE-010,AC-CRE-024 | CRE-11 | Verificatore indipendente rileva revoca |
| **CRE-13** | **Scadenze, sostituzione e storico** — Scadenze, correzioni per nuova emissione, tracciamento vecchio/nuovo | CRE-043,CRE-062 | AC-CRE-009,AC-CRE-011,AC-CRE-019 | CRE-12 | Nessuna modifica del payload già firmato |
| **CRE-14** | **Pubblicazione e privacy** — Policy pubblicazione, privacy Joomla, download ACL, preview Open Graph filtrata e noindex | CRE-051,CRE-070,CRE-071,CRE-072,CRE-073 | AC-CRE-012,AC-CRE-013,AC-CRE-015,AC-CRE-016 | CRE-10,CRE-11 | Dati personali mai pubblici per impostazione iniziale |
| **CRE-15** | **Rotazione chiavi e continuità verifica** — Rotazione, backup restore, chiavi storiche, prova migrazione dominio, recupero | CRE-040,CRE-043,CRE-103 | AC-CRE-017,AC-CRE-018,AC-CRE-026 | CRE-12,CRE-14 | Badge vecchi verificabili dopo rotazione e ripristino |

### C3 — Esperienza studente e LinkedIn (4 PR)

**Exit gate:** Lo studente gestisce, scarica e condivide credenziali verificabili.

| ID | PR prevista e deliverable | Req. principali | Test correlati | Dipendenze | Evidenza minima di accettazione |
|---|---|---|---|---|---|
| **CRE-16** | **I miei badge** — Lista/dettaglio in frontend, download credenziali e URL, filtri/stati e ACL per studente | CRE-060,CRE-070 | AC-CRE-006,AC-CRE-015 | CRE-08,CRE-11,CRE-14 | Studente accede solo ai badge propri |
| **CRE-17** | **LinkedIn e condivisione** — Add to Profile con dati copiabili, Share LinkedIn, URL di verifica, OG limitato alla visibilità | CRE-061,CRE-062,CRE-063 | AC-CRE-014,AC-CRE-015 | CRE-10,CRE-14,CRE-16 | Flusso LinkedIn completabile senza API riservate |
| **CRE-18** | **Notifiche di emissione** — Mail Templates Joomla, outbox, i18n e retry senza duplicazioni | CRE-064,CRE-102 | AC-CRE-020 | CRE-07,CRE-08,CRE-16 | Errore email non invalida credenziale |
| **CRE-19** | **UX, lingue e override** — Revisione frontend/backend, accessibilità, IT/EN, CSS contract e override Joomla | CRE-080,CRE-100 | AC-CRE-012,AC-CRE-014,AC-CRE-015 | CRE-16,CRE-17,CRE-18 | Navigazione desktop/mobile, tastiera, lingue e permessi collaudati |

### C4 — Qualità e rilascio (2 PR)

**Exit gate:** Release completa di Credentials integrata nel pkg_jlms e collaudata.

| ID | PR prevista e deliverable | Req. principali | Test correlati | Dipendenze | Evidenza minima di accettazione |
|---|---|---|---|---|---|
| **CRE-20** | **Collaudo E2E e interoperabilità** — Copertura condizioni negative, test matrix, concorrenza, security review, verifica di terze parti, evidenze manuali Joomla | CRE-090,CRE-100,CRE-101,CRE-103 | AC-CRE-001–026 | CRE-01–19 | Rapporto test firmato, nessun blocco critico/alto pertinente |
| **CRE-21** | **Hardening, packaging e release** — Install/upgrade/disabilitazione, gestione retention e chiavi, docs runbook, pacchetto/versione, rollback, release checklist | CRE-082,CRE-083,CRE-103 | AC-CRE-017,AC-CRE-018,AC-CRE-021,AC-CRE-022 | CRE-20 | Pacchetto completo unico, deploy/restore e accettazione Joomla |

### C5 — Evoluzioni successive e non bloccanti (3 PR)

| ID | Evoluzione | Deliverable |
|---|---|---|
| **CRE-22** | Badge Connect API e wallet compatibili | Badge Connect e profili OAuth con test compatibilità reali |
| **CRE-23** | Criteri avanzati e competenze | Quiz, punteggi, attività e combinazioni di regole con versionamento |
| **CRE-24** | Importazione e interoperabilità esterna | Validazione e import credenziali esterne, sicurezza e provenienza |

## 4. Dipendenze e criticità da gestire prima della codifica

1. **Regole di completamento percorsi:** il contratto del risultato deve essere stabile e controllabile; non dichiarare completato CRE-06 senza il criterio percorso se è richiesto dalla prima release. In caso di indisponibilità, trattare il blocco come dipendenza della C1 invece di fingere il supporto.
2. **PoC crittografico:** blocco di avanzamento per C1. La suite candidata `eddsa-rdfc-2022` e le librerie effettive sono scelte subordinate a prova reale di conformità/interoperabilità; nessuna implementazione crittografica da zero.
3. **Verifica indipendente di revoca:** C2 richiede verificatore esterno che comprenda stato e status list; non basta lo stato letto da un DB JD Learn.
4. **Privacy vs URL pubblico:** separare ciò che compare nella pagina HTML, nei metadati social e nel payload VC; impostazione iniziale conservativa; l'emittente deve configurare policy lecite.
5. **LinkedIn:** Add to Profile e condivisione usano flussi pubblici disponibili; non si promette validazione nativa LinkedIn, autopopolazione o API privilegiate.
6. **Scheduler:** installare task nel package; assicurare diagnostica e documentazione su cron/trigger operativo nel sito del cliente. I job non devono rendere sincrono il completamento del corso.
7. **Compatibilità pacchetto:** mantenere un solo `com_jlms` e `pkg_jlms`; migrazioni additive/non distruttive, backup e test d'upgrade, disabilitazione delle nuove emissioni separata dalla verifica dei badge esistenti.

## 5. Regole di sviluppo, revisione e aggiornamento

- Per ciascuna PR: identificativo `CRE-XX`, requisiti `CRE-*` funzionali, test `AC-CRE-*`, dipendenze, cambi schema/API, valutazione sicurezza/privacy, aggiornamento alla documentazione e sezione **Comportamenti da testare** con passi Joomla concreti.
- Usare PR in bozza durante lo sviluppo; controlli leggeri nelle fasi documentali; CI di integrazione soltanto sul candidato pronto secondo la [CI_POLICY](CI_POLICY.md). Non fare merge di codice non verificato.
- Dopo il merge documentare URL PR, commit, CI e **link al pacchetto installabile** ove previsto; aggiornare uno stato separato per Credentials, senza alterare la percentuale della prima stabile.
- Stato delle PR: `Pianificata` → `In sviluppo` → `In verifica` → `Integrata` → `Collaudata su Joomla`. `Integrata` non significa `Collaudata`.
- Non usare il numero di PR integrate come sola percentuale di completamento. Calcolare **implementazione, collaudo e avanzamento ponderato** sui macro-requisiti, con criteri di pesatura dichiarati prima della prima PR runtime.
- A C4 eseguire almeno: installazione e upgrade; disabilitazione/re-enable; ACL accessi diretti; due worker concorrenti; fallimento mail; prove tampered/invalid/revoked/expired; recupero chiavi/backup; dati e visibilità privacy; route pubbliche e dominio; browser desktop/mobile e IT/EN; verificatore indipendente.

## 6. Stato iniziale e criterio di release

**24 attività pianificate, di cui 21 per la prima release completa e 3 evolutive.** Nessuna delle 21 PR runtime è avviata o accettata per effetto dell'approvazione di questo documento. La specifica e la progettazione tecnica esistenti sono documentazione preparatoria, non runtime funzionante.

**Criterio di successo C4:** un'installazione JD Learn autonoma su Joomla emette automaticamente una credenziale firmata al completamento di un corso/percorso supportato, consente emissioni manuali, download, revoca e verifica pubblica indipendente, e permette allo studente di aggiungerne i riferimenti a LinkedIn. Il tutto senza secondo componente e senza servizi commerciali obbligatori.

**Esclusione espressa:** C0–C5 e relative PR non sono prerequisiti né requisiti di accettazione per JD Learn **1.0.0**.