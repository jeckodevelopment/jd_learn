# JD Learn Credentials — Specifica funzionale Open Badges 3.0

**Versione:** 1.0 · **Data:** 9 novembre 2026 · **Stato:** approvata come requisito di roadmap, non implementata  
**Destinazione:** rilascio successivo a **JD Learn 1.0.0**; numero versione da pianificare.  
**Riferimenti di perimetro:** [RELEASE_SCOPE](../RELEASE_SCOPE.md), [roadmap](../ROADMAP_TO_STABLE.md), [specifica generale](Specifica_funzionale_LMS_Joomla_1.0.md).

> Questo modulo è esplicitamente **fuori dal perimetro e dai criteri di accettazione della versione 1.0.0**. Non aggiungere i requisiti CRE-* alla matrice dei 210 requisiti della baseline né modificare la percentuale di avanzamento verso la prima stabile. Questa specifica definisce comportamenti attesi, non evidenze di sviluppo o collaudo.

**Progettazione tecnica collegata:** [JD_LEARN_CREDENTIALS_TECHNICAL_DESIGN.md](JD_LEARN_CREDENTIALS_TECHNICAL_DESIGN.md) (decisioni T-001–T-007 del 9 novembre 2026). La progettazione tecnica prevale sulle precedenti ipotesi di separazione in un secondo componente.

## 1. Scopo e principi

Realizzare **JD Learn Credentials**, sottosistema nativo di `com_jlms`, abilitabile e disabilitabile, distribuito nello stesso pacchetto `pkg_jlms` in una release futura, per emettere, conservare, esportare, pubblicare e verificare credenziali formative secondo **Open Badges 3.0**. Ogni organizzazione deve poter essere emittente sul **proprio dominio**, senza account o licenze obbligatorie di Credly o altri intermediari. Credly costituisce un riferimento *di esperienza utente*, non una dipendenza tecnica.

Principi: riusabilità indipendente da nome/dominio/brand, formato standard verificabile anche fuori dalla piattaforma, firma e gestione del ciclo di vita, ACL Joomla granulari, minimizzazione GDPR, frontend localizzabile e personalizzabile con CSS/override; nessuna modifica retroattiva a credenziali già firmate. In ogni installazione è possibile configurare più identità emittenti, con permessi e chiavi distinte, senza presupporre multi-tenancy tra installazioni.

## 2. Perimetro e relazioni con altri moduli

- Fonte dati: utenti Joomla, corsi e versioni didattiche, iscrizioni, completamento consolidato, audit, scheduler e notifiche; **non** dati arbitrari forniti dal browser.
- Attestati PDF e Open Badges rimangono oggetti distinti, eventualmente con criterio di idoneità condiviso. Configurazione per corso: nessuno / PDF / badge / entrambi. Il badge non richiede la generazione del PDF.
- Non inclusi nella prima release Credentials: wallet proprietario, blockchain, vendita badge, riconoscimento ufficiale LinkedIn, integrazione commerciale Credly, import universale, API Badge Connect completa, badge per micro-competenze e percorsi complessi, emissioni massive avanzate.
- Dipendenza: un evento server-side attendibile e idempotente di completamento corso, con snapshot della versione didattica. Per il primo incremento può bastare completamento corso o emissione manuale; quiz/punteggi diventano criteri configurabili solo quando il motore didattico li offre stabilmente.

## 3. Ruoli e ACL

**CRE-001** Definire azioni Joomla granulari: `credentials.manage`, `credentials.issuers.manage`, `credentials.templates.manage`, `credentials.issue`, `credentials.revoke`, `credentials.view`, `credentials.audit`, `credentials.keys.manage`. Implementare autorizzazioni lato server su liste, dettagli, API, file, download e azioni: nascondere un pulsante non basta.

**CRE-002** Super User configura la sicurezza; responsabili autorizzati gestiscono modelli/emissioni; docenti possono eventualmente consultare solo corsi assegnati; studenti accedono soltanto alle proprie credenziali; visitatori verificano soltanto i link pubblicati secondo le regole privacy. La gestione delle chiavi non è implicita nel permesso di emissione.

## 4. Emittenti

**CRE-010** Gestire uno o più profili: ID persistente, denominazione, sito web, descrizione, logo, lingua, email pubblica facoltativa, URL organizzazione LinkedIn facoltativo, stato e profilo predefinito.

**CRE-011** Per ciascun emittente gestire identificatore crittograficamente verificabile, riferimenti alla chiave e materiali di verifica. Separare profilo pubblico, segreti e politiche di pubblicazione.

**CRE-012** Snapshot di denominazione e proprietà rilevanti all'emissione; mantenere gli identificatori e le chiavi pubbliche storiche necessari alla verifica. Impedire cancellazioni distruttive di emittenti con badge attivi.

## 5. Modelli e criteri

**CRE-020** CRUD, duplicazione, pubblicazione e archivio di modelli: titolo, descrizione, immagine, lingua, emittente, achievement, criteri, eventuali competenze, durata validità e visibilità; caricamento immagini validato. Un editor grafico non è obbligatorio.

**CRE-021** Versionare modifiche sostanziali di modello/criteri; il badge emesso congela la versione e non cambia al modificarsi del modello.

**CRE-022** Sezione opzionale «Credenziali digitali» nell'editor corso: abilitazione, emittente, modello, modalità automatica/manuale, regola, scadenza, comportamento in caso di nuova versione del corso. Stato del corso e pubblicazione rimangono indipendenti dal badge.

**CRE-023** Motore di criteri configurabile per badge e associazione didattica: completamento di un corso, completamento di un percorso, eventuali quiz/punteggi, attività o combinazioni di condizioni, quando i relativi risultati didattici sono stabili e disponibili. L'idoneità è sempre valutata lato server su snapshot, senza dipendere da eventi browser. Nell'incremento iniziale il criterio corso e l'emissione manuale sono obbligatori; quello di percorso è la prima estensione pianificata al consolidamento della sua semantica di completamento; non mostrare criteri non ancora implementati.

## 6. Emissione e persistenza

**CRE-030** All'evento di completamento: verificare la regola, rilevare eventuale emissione esistente, creare ID univoco, snapshot di achievement/corso/versione/destinatario/emittente, generare Open Badges 3.0, firmare, persistere e notificare tramite outbox/job. L'operazione dev'essere idempotente e recuperabile dopo errori; invio email non condiziona validità della credenziale.

**CRE-031** Emissione manuale con permesso specifico, selezione destinatario/modello e motivazione obbligatoria; audit con autore, data, motivazione, esito. Le emissioni manuali non falsificano il tracciamento del corso.

**CRE-032** Stati operativi: predisposta, in lavorazione, emessa, errore recuperabile, errore definitivo; separati dagli stati di validità: valida, non ancora valida, scaduta, revocata, non verificabile, prova non valida e non trovata.

**CRE-033** ID credential pubblico opaco, stabile, non sequenziale e non enumerabile; chiave di idempotenza riferita a studente, risultato/edizione/versione pertinente, modello e politica di riemissione. Nessuna emissione duplicata per retry o eventi ripetuti.

**CRE-034** Il formato esportabile sarà una credenziale JSON-LD Open Badges 3.0 firmata con profilo di prova/suite conforme. Congelare payload firmato, riferimenti e metadati di versione. La scelta dettagliata di context, proof suite, canonicalizzazione, DID/verification method e librerie sarà motivata nella progettazione tecnica e verificata con strumenti indipendenti. Non dichiarare «conformità certificata 1EdTech» senza certificazione.

## 7. Crittografia e stato

**CRE-040** Generare, conservare e ruotare chiavi in modo sicuro, con accesso minimo; mai rendere le chiavi private disponibili in pagine, log, export, backup pubblici o tabelle leggibili da ruoli non autorizzati. Supportare recupero/backup protetti, identificazione chiave per firma e permanenza dei metodi pubblici storici.

**CRE-041** Verifica effettiva di schema/contesto, firma/integrità, coerenza emittente, date e stato; distinguere un fallimento crittografico da una indisponibilità temporanea di una fonte. La presenza di un record DB, da sola, non costituisce prova di autenticità.

**CRE-042** Revoca motivata, autorizzata e auditata; pubblicare stato tramite meccanismo interoperabile compatibile con il profilo adottato, valutando Bitstring Status List in progettazione tecnica e verificandone il comportamento presso verificatori indipendenti. Non applicare modifiche silenziose al payload firmato.

**CRE-043** Per correzione emettere nuova credenziale e mantenere collegamento con quella revocata/sostituita; assicurare che la precedente non risulti valida. Gestire la perdita o compromissione delle chiavi con procedura documentata.

## 8. Verifica pubblica autonoma

**CRE-050** Ogni sito deve poter ospitare URL persistenti, es. `https://example.org/badges/verify/{public-id}`, mediante routing Joomla e senza servizio centralizzato Jecko. Nessun identificativo Joomla o email privata nell'URL. Configurare redirect/migrazione dominio e conservare gli endpoint per credenziali rilasciate anche a corso archiviato.

**CRE-051** Pagina pubblica con immagine, titolo, achievement, emittente, date, criteri, stato, ID, eventuali evidenze pubblicabili e dati destinatario **solo secondo visibilità autorizzata**. Link a credenziale esportabile quando appropriato. I link diretti non aggirano ACL o privacy.

**CRE-052** Esiti: valida, scaduta, revocata, non ancora valida, non verificabile, non autentica e non trovata. Mostrare cause generiche senza esporre informazioni sensibili. Non etichettare «valida» una credenziale per la quale un controllo obbligatorio non è completato.

**CRE-053** Endpoint pubblici protetti da rate limit, anti-enumerazione, controlli cache e politiche di indicizzazione. Nessun indice pubblico nominativo di studenti o elenco credenziali.

**CRE-054** Verifica accessibile a terzi senza login nei limiti della pubblicazione autorizzata; firma e stato delle credenziali esportate verificabili anche indipendentemente dalla pagina JD Learn quando i relativi metodi pubblici restano disponibili.

## 9. Area studente e LinkedIn

**CRE-060** Sezione «I miei badge»: elenco e dettaglio per proprie credenziali con immagine, corso, emittente, data, stato, eventuale scadenza, download file firmato, link copia e apertura pagina di verifica. Mobile/keyboard/screen reader; localizzazione.

**CRE-061** «Aggiungi al profilo LinkedIn» utilizza soltanto flussi pubblici ufficialmente disponibili; mostra campi facilmente copiabili per la sezione Licenze e certificazioni: titolo, emittente, data, scadenza, ID e URL verifica. **Non assumere precompilazione automatica da Add to Profile** e non richiedere OAuth o API riservate per la prima release.

**CRE-062** Azione distinta «Condividi su LinkedIn» per aprire un flusso di condivisione del link; metadata Open Graph sul verificatore limitati ai dati autorizzati. Il collegamento «Mostra credenziale» del profilo punta al dominio dell'emittente.

**CRE-063** Non dichiarare che LinkedIn convalida crittograficamente o certifica il badge: LinkedIn mostra un link di verifica, mentre la verifica è affidata all'emittente e a software che supporta lo standard.

**CRE-064** Notifiche localizzate di emissione con link a area personale; fallimenti d'invio registrati e recuperabili senza duplicazione credenziale.

## 10. Privacy e GDPR

**CRE-070** Distinguere dati necessari all'emissione da dati pubblicati. Predefinire la pubblicazione nominativa come disattivata; consentire decisioni esplicite e registrate secondo base giuridica/politiche configurate dall'organizzazione. Non presupporre che il consenso sia l'unica base giuridica.

**CRE-071** Non esporre ID utenti, email, percorsi riservati, risultati o prove didattiche private. Separare metadati pubblici da evidenze protette. Considerare correlabilità degli identificatori permanenti.

**CRE-072** Integrare plugin Joomla Privacy per export/richieste di rimozione, retention configurabile, minimizzazione, registro accessi e procedure di rettifica/oscuramento; documentare l'equilibrio tra diritto alla cancellazione, obblighi di conservazione e verificabilità di una credenziale già distribuita.

**CRE-073** Supportare policy di indicizzazione e disattivazione della pagina pubblica nominativa senza falsificare lo stato tecnico della credenziale.

## 11. Backend e modello dati logico

**CRE-080** Interfacce amministrative Joomla: dashboard, emittenti, modelli, associazioni corso, emissioni, emissione manuale, revoche/sostituzioni, impostazioni chiavi, audit. Filtri: studente, corso, emittente, modello, data, stato e modalità. Toolbar, filtri e pagination nativi, ACL per record, niente azioni fittizie.

**CRE-081** Entità logiche minime (nomi fisici da fissare in fase tecnica): Issuer, SigningKeyReference, BadgeTemplate, BadgeTemplateVersion, CourseCredentialRule, IssuedCredential (payload firmato immutabile), CredentialStatus, CredentialJob, CredentialPublication, CredentialAudit.

**CRE-082** Proteggere con vincoli DB le identità e idempotenza; mantenere snapshot, impronte/checksum e prova firmata, storico sostituzioni, versioni, chiavi e riferimenti di verifica. Politiche esplicite per aggiornamento, backup, restore, migrazione e uninstall senza eliminazione implicita delle credenziali.

**CRE-083** Architettura a servizi interni distinti per eligibility, issuance, proof, verification, status, publication e notifications; consumo eventi didattici senza accoppiamento con controller del corso. **Decisione T-001/T-007: tutto integrato in `com_jlms` e distribuito in `pkg_jlms`; non esiste un componente Credentials separato.** Eventuali plugin di criteri estensivi e task Scheduler necessari sono inclusi nello stesso pacchetto. Se le nuove emissioni sono disabilitate, JD Learn continua a funzionare e le verifiche delle credenziali storiche rimangono disponibili.

## 12. API e interoperabilità futura

**CRE-090** Per la prima release: endpoint di verifica e download con controlli privacy/ACL, file esportabile standard; test positivo con almeno un verificatore esterno compatibile con la stessa suite.

**CRE-091** Badge Connect API, wallet, criteri avanzati per competenze, importazione di terze parti, sistemi esterni e batch massivi sono incrementi successivi, soggetti a specifiche proprie; nessuna promessa generica di compatibilità con *ogni* wallet.

## 13. Requisiti trasversali

**CRE-100** Joomla 6 e runtime supportati dal progetto; i18n, frontend personalizzabile con ID/classi/variabili CSS e override documentati, responsive, accessibile; consistenza ACL su controller, API e download.

**CRE-101** Protezione CSRF per mutazioni, validazione upload e input, SQL parametrico, escaping output, SSRF protection su fetch di contesti/risorse, rate limiting, audit senza segreti, librerie mantenute e dipendenze licenziate correttamente.

**CRE-102** Firma ed emissione eseguibili tramite job persistenti con retry, idempotenza e outbox; evitare invio mail nella transazione e carichi crittografici sincroni bloccanti per gli studenti.

**CRE-103** Verificare installazione, upgrade, rollback documentato, backup/restore (incluse chiavi), rotazione chiavi, migrazione dominio, disinstallazione conservativa e compatibilità con modifica/archiviazione dei corsi. Nessuna riduzione dei requisiti trasversali della baseline JD Learn.

## 14. Scenari di accettazione

| ID | Prova | Esito obbligatorio |
|---|---|---|
| AC-CRE-001 | Configura emittente | Identità/URL validati, ACL rispettate |
| AC-CRE-002 | Crea/versiona modello | Versioni storiche immutabili |
| AC-CRE-003 | Completa corso | Emette una credenziale firmata |
| AC-CRE-004 | Ripete evento/retry | Nessun duplicato |
| AC-CRE-005 | Emissione manuale | Autorizzazione e motivo registrati |
| AC-CRE-006 | Scarica JSON-LD | Prova verificabile da tool indipendente |
| AC-CRE-007 | Verifica firma valida | Esito valido |
| AC-CRE-008 | Modifica payload firmato | Esito non autentico |
| AC-CRE-009 | Scadenza/data futura | Stato temporale corretto |
| AC-CRE-010 | Revoca | Verifica interna ed esterna non attestano validità |
| AC-CRE-011 | Sostituzione | Nuovo ID, relazione e storico preservati |
| AC-CRE-012 | Verifica anonima pubblicata | Solo dati autorizzati |
| AC-CRE-013 | ID inesistente/enumerazione | Nessuna fuga di dati |
| AC-CRE-014 | Add to Profile | Campi e URL corretti senza API private |
| AC-CRE-015 | Condivisione social | Anteprima rispetta visibilità |
| AC-CRE-016 | Privacy/export/rimozione | Workflow Joomla e retention corretti |
| AC-CRE-017 | Backup/restore | Credenziali verificabili dopo recupero |
| AC-CRE-018 | Rotazione chiavi | Vecchie prove verificabili |
| AC-CRE-019 | Aggiorna/archivia corso | Credenziali storiche inalterate |
| AC-CRE-020 | Fallisce invio email | Emissione resta disponibile |
| AC-CRE-021 | Disabilita estensione | JD Learn core non degrada |
| AC-CRE-022 | Verificatore esterno | Conformità del profilo/suite provata |
| AC-CRE-023 | Fonte verifica indisponibile | Esito non verificabile, mai falso valido |
| AC-CRE-024 | Utente privo di ACL | Mutazione negata e auditata |
| AC-CRE-025 | Concorrenza 2 eventi | Singola emissione consistente |
| AC-CRE-026 | Migrazione di dominio | Link/redirect e verifica preservati |

Ogni PR implementativa dovrà indicare in «**Comportamenti da testare**» i casi pertinenti su Joomla reale; CI non sostituisce l'accettazione manuale. Per la prima release Credentials sono obbligatori il collaudo end-to-end, la verifica indipendente della credenziale firmata, la prova di revoca e nessun difetto bloccante di sicurezza.

## 15. Sequenza di sviluppo futura

| Incremento | Contenuto | Condizione di uscita |
|---|---|---|
| **C1 — Fondamenta** | Emittenti, modelli, associazione corsi, regole, emissione manuale/automatica, firma, download | Badge firmato e verificabile esternamente |
| **C2 — Verifica e ciclo di vita** | Verificatore sul dominio dell'emittente, revoca/scadenza, sostituzione, privacy, chiavi | Verifica completa e revoca interoperabile |
| **C3 — Esperienza studente e LinkedIn** | I miei badge, link e download, Add to Profile, post, anteprima OG, notifiche | Studente pubblica una credenziale con URL verificabile |
| **C4 — Collaudo e rilascio** | Test end-to-end, sicurezza, compatibilità e packaging | Release completa verificata su Joomla e con verificatore indipendente |
| **C5 — Evoluzioni** | Badge Connect, wallet, competenze, import/API avanzate | Nuova specifica e criteri dedicati |

La **prima release funzionalmente completa del modulo** richiede C0+C1+C2+C3+C4. C5 è un'estensione non bloccante. Nessuna data o numero di versione del rilascio futuro sono assegnati.

## 16. Governance e tracciabilità

- Requisiti `CRE-*`, scenari `AC-CRE-*` e avanzamento **separati** dalla baseline 1.0.0.
- Per ogni PR: requisiti, test automatizzati, verifiche di sicurezza, sezione «Comportamenti da testare», allineamento documentazione, URL PR/CI e successivamente pacchetto installabile e percentuale di avanzamento del modulo.
- La scelta della proof suite, dell'implementazione della status list e della strategia di continuità del verificatore richiede un progetto tecnico prima della codifica.
- La pubblicazione LinkedIn non costituisce validazione ufficiale da parte di LinkedIn e non va descritta come tale.

## 17. Riferimenti tecnici

- 1EdTech Open Badges 3.0: https://www.imsglobal.org/spec/ob/v3p0/
- 1EdTech Implementation Guide: https://www.imsglobal.org/spec/ob/v3p0/impl/
- W3C Verifiable Credentials: https://www.w3.org/TR/vc-data-model-2.0/
- W3C Bitstring Status List: https://www.w3.org/TR/vc-bitstring-status-list/
- LinkedIn Add to Profile FAQ: https://www.linkedin.com/help/linkedin/answer/a527099/

**Decisione approvata (9 novembre 2026):** JD Learn Credentials è una futura estensione opzionale di JD Learn, progettata per emettere Open Badges verificabili e condivisibili su LinkedIn, **non inclusa in JD Learn 1.0.0**.

**Pianificazione esecutiva:** [21 PR per la release completa, C0–C4, e 3 PR evolutive C5](../CREDENTIALS_IMPLEMENTATION_PLAN.md). La nomenclatura C0–C5 del piano esecutivo prevale sulle precedenti denominazioni degli incrementi della specifica.
