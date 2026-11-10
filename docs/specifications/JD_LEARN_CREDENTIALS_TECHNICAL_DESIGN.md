# JD Learn Credentials — Progettazione tecnica preliminare

**Versione 0.1 · 9 novembre 2026 · Decisioni architetturali approvate T-001–T-007**  
**Stato:** progettazione preliminare, nessun codice implementato. **Rilascio: successivo alla 1.0.0.**  
**Specifica funzionale:** [JD_LEARN_CREDENTIALS_FUNCTIONAL_SPEC.md](JD_LEARN_CREDENTIALS_FUNCTIONAL_SPEC.md)  
**Decisioni aggiuntive approvate:** [T-008–T-015](../CREDENTIALS_DECISIONS_T008_T015.md) — badge multipli, percorsi, pubblicazione, emittenti e continuità.

> Questa progettazione non modifica il perimetro o il calcolo di avanzamento di JD Learn 1.0.0, né i suoi 210 requisiti della baseline. I dettagli delle interfacce crittografiche, degli schema fisici e delle suite vanno verificati con un PoC prima di essere considerati implementativi definitivi.

## 1. Decisioni approvate

| ID | Decisione | Conseguenza |
|---|---|---|
| **T-001** | **Unico componente Joomla `com_jlms`**: Credentials è un sottosistema nativo integrato, non un componente/prodotto autonomo. | Un'unica area amministrativa, ACL, rotte, distribuzione e API applicative; separazione logica in servizi e namespace interni. |
| **T-002** | Ogni sito Joomla JD Learn è emittente/verificatore autonomo, senza server Jecko obbligatorio. | URL, identità, chiavi e status sul dominio dell'installazione. Più profili emittente per installazione, se configurati. |
| **T-003** | Open Badges 3.0, JSON-LD e **EdDSA** come impostazione preferita. | Suite candidata **`eddsa-rdfc-2022`**, da validare con PoC e conformità 1EdTech; non costruire algoritmi crittografici in casa. |
| **T-004** | Tabelle autonome ma nel namespace DB **`#__jlms_`**, senza scrivere i badge nelle tabelle didattiche. | Migrazioni del solo componente `com_jlms`, integrità e versionamento separati. |
| **T-005** | Emissione asincrona/idempotente tramite job persistenti e **Scheduler Joomla**, criteri configurabili per badge e corso/percorso. | Motore regole estensibile, snapshot e vincoli anti-duplicati, retry con gestione degli errori. |
| **T-006** | Endpoint distinti per pagina HTML, credenziale firmata machine-readable, emittente e stato. | Verifica sul dominio proprio, controllo della pubblicazione separato da verifica di firma/stato. |
| **T-007** | Credentials incluso nello **stesso pacchetto ufficiale `pkg_jlms`** a partire da una release successiva alla 1.0.0. | Nessun componente o pacchetto aggiuntivo da installare; eventuali task plugin indispensabili sono forniti e aggiornati nel pacchetto originale. |

**Regola:** non introdurre `com_jlmscredentials`, `pkg_jlmscredentials` o tabelle `#__jlmsc_*`; la sigla «JD Learn Credentials» è esclusivamente un nome funzionale.

## 2. Topologia Joomla e modularità applicativa

**Componente unico:** UI backend «Credenziali» sotto JD Learn, UI studente «I miei badge», rotte pubbliche e servizi del componente. La gestione delle credenziali non va collocata principalmente nei plugin: modello dati, emissione, firma, verifica, revoca e API sono servizi del core `com_jlms`.

**Contratti interni proposti** (nomi PHP indicativi, da armonizzare con DI, namespace e convenzioni del codice esistente):

- `CredentialIssuerService`: profili emittente e chiavi pubbliche;
- `CredentialTemplateService`: modelli/achievement/versioni;
- `CredentialEligibilityService`: combinazione di criteri e snapshot;
- `CredentialIssuanceService`: orchestrazione e idempotenza;
- `CredentialProofService`: firma tramite libreria standard e verifica;
- `CredentialStatusService`: stato/revoca/sostituzione;
- `CredentialPublicationService`: URL/visibilità/linked data;
- `CredentialJobService`: outbox, leasing, retry, audit.

Librerie condivise opzionali nel package solo quando utili a evitare duplicazione; mantenere il dominio Credentials all'interno di `com_jlms`.

**Plugin Joomla:** usare task plugin di gruppo `task` per Scheduler (incluso automaticamente nel pacchetto), integrandosi con lo scheduler già presente nel progetto; plugin di criteri esterni soltanto quando serve espandibilità reale. I criteri standard non devono richiedere plugin separati. Gli eventi del core JD Learn devono poter essere consumati da servizi interni e plugin autorizzati senza dipendenza circolare. Un task non garantisce esecuzione puntuale senza scheduler/cron effettivamente operativo: documentare e rendere diagnosticabile la configurazione.

**Configurazione:** switch emissione nuovi badge per installazione; mantenere verifica pubblica e stato delle credenziali pregresse attivi quando si sospende l'emissione. Spegnere il modulo non cancella tabelle, prove, chiavi o revoche.

## 3. Motore dei criteri configurabili

Gli amministratori scelgono per ciascun modello/associazione didattica quando rilasciare il badge. Supporto progettato per:

- **Completamento corso:** stato finale consolidato, versione didattica e criteri del corso verificati lato server.
- **Completamento percorso:** tutti i corsi obbligatori oppure regola definita sul percorso, con versione/snapshot delle associazioni.
- **Quiz/valutazione:** punteggio o esito validato, quando disponibile il motore pertinente.
- **Attività specifiche:** risultato didattico stabile e auditabile.
- **Combinazioni** AND/OR esplicitamente configurate; definire nesting e limiti di complessità in fase esecutiva.
- **Manuale:** emittente/operatore autorizzato, motivazione ed evidenza; non modifica artificiosamente progressi o tentativi.

L'engine deve essere **deterministico** rispetto a uno snapshot congelato e non dipendere dallo stato volatile di vista/browser. API interne proposte:

```php
interface CredentialCriterionInterface
{
    public function getType(): string;
    public function evaluate(
        CredentialContext $context,
        array $configuration
    ): CriterionResult;
}
```

`CriterionResult` rappresenta idoneo / non idoneo / non valutabile, con riferimenti alle evidenze e motivazioni amministrative (non rese pubbliche automaticamente). Ogni criterio registra tipo, versione e configurazione; su mutazione di corso/percorso non si rivalutano retroattivamente i badge firmati. Criteri non implementati non appaiono selezionabili. La prima delivery C1 realizzerà almeno corso e manuale; il percorso dovrà essere pianificato come primo criterio aggiuntivo in C1/C2 se il suo completamento è stabilizzato. Altri tipi non bloccano il primo rilascio Credentials finché non attivati.

**Interfaccia amministrativa**: associazione di uno o più modelli a corso/percorso, selezione delle regole per ognuno, emittente, modalità, data di scadenza, riedizione/re-emissione, anteprima della regola. L'attestato PDF rimane indipendente.

## 4. Eventi, coda e semantica di emissione

Flusso:
1. Il servizio didattico JD Learn registra in transazione il completamento (corso/percorso/attività).
2. Nella stessa unità di persistenza, oppure tramite rilevazione recuperabile, registra un evento/outbox con ID idempotente; non fa rete né firma nella transazione didattica.
3. Un task Scheduler elabora batch limitati con leasing/lock, verifica idoneità e ACL del contesto, individua la regola/versione e applica un vincolo univoco di idempotenza.
4. Prepara OpenBadgeCredential Open Badges 3.0, firma, salva il payload immutabile e registra ID, hash, `verificationMethod`, stato e audit.
5. Accoda notifica separata. Retry/backoff, limite tentativi e diagnostica amministrativa per errori recuperabili/finali.

Idempotenza fondata su chiave deterministica (destinatario, achievement, contesto corso/percorso+versione, regola/versione e politica di riemissione); calcolo e vincolo univoco DB verificati in concorrenza. Conservare separatamente gli **stati del job** (queued/running/retry/failed/done) dagli **stati della credenziale** (valid/revoked/expired/not-yet-valid/non-verifiable). La revoca non cancella il payload storico.

**Affidabilità:** recuperare crash tra firma e persistenza senza emettere doppie credenziali. Un job email fallito non invalida il badge. Pianificare test con due worker paralleli e restart.

## 5. Modello dati proposto

Tabelle con prefisso reale Joomla `#__jlms_`:

| Tabella | Chiavi e responsabilità |
|---|---|
| `#__jlms_credential_issuers` | `id`, `public_id`, dominio, profilo, stato, lingua, metadati |
| `#__jlms_credential_keys` | `id`, `issuer_id`, `verification_method`, key reference protetta, date e rotazione |
| `#__jlms_credential_templates` | `id`, `issuer_id`, titolo, stato, versione corrente |
| `#__jlms_credential_template_versions` | `template_id`, `version`, snapshot achievement/criteri/immagine |
| `#__jlms_credential_rules` | `id`, `template_version_id`, contesto corso/percorso, DSL criteri versionata |
| `#__jlms_credentials` | `id`, ID pubblico opaco univoco, soggetto, snapshot, payload firmato immutabile, hash, stato, chiave idempotenza univoca |
| `#__jlms_credential_status_lists` | issuer, URI, formato/status list, versione, pubblicazione |
| `#__jlms_credential_jobs` | tipologia, stato, tentativi, lease, next_run, correlation/idempotency |
| `#__jlms_credential_audit` | evento, attore, timestamp, target, esito e motivazione |
| `#__jlms_credential_publications` | credential, visibilità, metadati pubblicabili, modifica/storia |

Definire nel progetto esecutivo tipi esatti, FK compatibili con il DB corrente, indici, record di status associati alle credenziali, vincoli di consistenza, migration up/down non distruttive e politiche di retention. Il DB **non deve contenere chiavi private in chiaro**. La firma di badge già emessi non deve mutare in seguito a edit di corsi, modelli o anagrafiche.

## 6. Open Badges 3.0 e gestione delle chiavi

Profilo di emissione preferito: **Open Badges 3.0 in JSON-LD**, proof W3C Data Integrity EdDSA con suite candidata **`eddsa-rdfc-2022`**. Confermare versione esatta della suite e metodo di prova nell'elenco corrente dei test 1EdTech e con un verificatore indipendente: non presumere interoperabilità per il solo uso di Ed25519. Occorre canonicalizzazione JSON-LD corretta, context sicuri, gestione di document loader e controlli su risorse esterne. Scartare librerie non manutenute e implementazioni artigianali della suite.

Un emittente mantiene una o più coppie di chiavi versionate. Chiave privata fuori dal webroot e, idealmente, fuori dal database (file con permessi stretti o secret manager); configurare percorso/riferimento protetto e backup/rotazione. Servire chiave pubblica e identificatore del `verificationMethod` via HTTPS, persistenti per le credenziali storiche. Il design definitivo di issuer ID (URL o DID), suite/proof e status list è subordinato al PoC.

**PoC obbligatorio prima di C1:** emettere un badge realistico con soggetto pseudonimo, firmarlo con PHP 8.3+ e verificarlo con uno strumento indipendente, poi alterare il payload, simulare scadenza e revoca, ruotare la chiave e verificare la cronologia. Registrare suite, versioni librerie, test vector, risultati e limitazioni; nessuna certificazione 1EdTech implicita.

## 7. Routing, API e verifica pubblica

Endpoint indicativi, soggetti al router Joomla/SEF:

| Endpoint | Funzione |
|---|---|
| `/badges/verify/{public-id}` | HTML accessibile, stato e informazioni pubblicabili |
| `/badges/credential/{public-id}` | JSON-LD firmato solo se consentito dalla policy di pubblicazione |
| `/badges/issuers/{issuer-id}` | Profilo emittente e metodi di verifica pubblici |
| `/badges/status/{list-id}` | Risorsa stato interoperabile necessaria ai verifier |
 
In HTML distinguere valido, scaduto, revocato, non ancora valido, firma alterata, non verificabile e non trovato. **Nessun falso esito positivo** quando status list o chiave pubblica non raggiungibili. Un link alla verifica su LinkedIn non prova validazione da parte di LinkedIn. URL stabili, ID non sequenziali, redirect su cambio dominio, revoche e chiavi storiche disponibili anche con nuove emissioni disattivate. I dettagli di cache/CDN, status list e revoche richiedono particolare attenzione a TTL e revoca tempestiva.

## 8. Privacy, sicurezza e integrazioni Joomla

Dati studente e metadati in credenziali condivise sono distinti. Predefinire visibilità nominativa pubblica **disattivata**, salvo valida decisione di pubblicazione dell'organizzazione/studente. Non incorporare email private o ID Joomla in URL. Limitare dati nelle pagine HTML, negli Open Graph e nei payload accessibili pubblicamente. Consentire export/privacy request Joomla, oscuramento dei dati pubblici e retention compatibile con finalità e vincoli dell'emittente; non garantire cancellazione di file già esportati a terzi.

ACL in tutte le mutazioni, token CSRF, SQL parametrico, escaping, protezione anti-enumerazione/rate limit, upload validato, fetch remoti con allowlist, safe JSON-LD document loader, audit senza segreti. Template override, ID/classi CSS e i18n da documentare nelle opzioni/guide del componente.

**LinkedIn**: azione Add to Profile con campi copiabilli (nome, emittente, data, ID, URL) e azione separata per condividere un post. Nessuna dipendenza da OAuth/API LinkedIn non accessibili; LinkedIn punta alla verifica ospitata da JD Learn.

## 9. Sequenza, test e rilascio

- **C0 — PoC e progettazione esecutiva:** suite, chiavi, document loader, status interoperabile; migrazioni/schema; API/eventi e criterio `course_completed`/ `pathway_completed` verificati con struttura runtime; threat model.
- **C1 — Emittenti, modelli ed emissione:** UI e ACL nel componente, criterio corso/manuale, firma e export; aggiungere percorso quando la semantica di completamento è stabile.
- **C2 — Verifica e revoca:** route pubbliche, status list, expiry, chiavi, revoca/sostituzione, privacy, test indipendenti.
- **C3 — Area studente e LinkedIn:** I miei badge, pubblicazione controllata, share/add to profile, email, UX accessibile e traduzioni.
- **C4 — Collaudo e rilascio:** test end-to-end, prova interoperabilità indipendente, hardening, upgrade e pacchetto unico.
- **C5 — Opzionale:** estensioni criteri, API Badge Connect, wallet, microcredenziali.

Test obbligatori: unità (criterion/snapshot/idempotenza), concorrenza, integration DB install/upgrade, ACL e URL diretti, firma valida/tampered/canonicalization, status list/revoca in verificatore esterno, key rotation/restore, privacy, scheduled task disabilitato/cron inattivo, browser Joomla reale IT/EN. Ogni PR include «Comportamenti da testare». La prima release funzionale Credentials richiede C0+C1+C2+C3+C4; C5 non è bloccante. Il dettaglio delle PR e delle milestone è nel [piano di implementazione](../CREDENTIALS_IMPLEMENTATION_PLAN.md).

La CI attuale e l'installabile JD Learn 1.0.0 rimangono senza nuove dipendenze Credentials fino a una successiva decisione di sviluppo. In quella release il codice Credentials e il plugin Scheduler saranno **inclusi nel pacchetto `pkg_jlms`** senza installazioni aggiuntive.

## 10. Fonti e verifiche

- [1EdTech Open Badges 3.0](https://standards.1edtech.org/open-badges/specifications/standards/v3p0)
- [1EdTech Implementation Guide](https://standards.1edtech.org/open-badges/guides/standards/v3p0/impl)
- [1EdTech Conformance and Certification](https://standards.1edtech.org/open-badges/specifications/standards/v3p0/cert)
- [Joomla Scheduled Tasks](https://guide.joomla.org/user-guide/scheduled-tasks)
- [W3C Data Integrity EdDSA Cryptosuites](https://www.w3.org/TR/vc-di-eddsa/)
- [W3C Bitstring Status List](https://www.w3.org/TR/vc-bitstring-status-list/)

**Decisione formale:** JD Learn Credentials è **parte nativa di JD Learn**, non un componente Joomla autonomo e non un prodotto separato. L'implementazione avverrà **dopo JD Learn 1.0.0**.
