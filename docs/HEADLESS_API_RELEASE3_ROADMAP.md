# Roadmap futura — JD Learn Web Services API e Headless LMS

**Stato: obiettivo architetturale proposto per il rilascio 3, non implementazione né impegno sul perimetro 1.0.0.** La baseline `docs/specifications/Specifica_funzionale_LMS_Joomla_1.0.md` (HTTP-01/02) e ADR-008 della progettazione tecnica rinviano esplicitamente le API web esterne al rilascio 3, usando i Web Services nativi Joomla. Questo documento chiarisce il caso d'uso senza anticipare PR runtime, costi o scadenze.

## Caso d'uso guida

Una scuola dispone di una app mobile/portale e desidera mostrare catalogo, iscrizioni e formazione nella propria esperienza utente. Joomla e JD Learn gestiscono account mappati, autorizzazioni, iscrizioni, lezioni, progressi, completamenti e attestati; l'app utilizza API documentate, **senza incorporare pagine del sito Joomla**. Il caso d'uso è un criterio di progettazione futura, non una capacità già garantita.

## Suddivisione incrementale del rilascio 3

| Blocco | Priorità relativa | Obiettivo | Vincoli |
|---|---|---|---|
| API-01 — fondamenta | Primo | Architettura Web Services Joomla, endpoint versionati, documentazione OpenAPI, errori e paginazione, throttling e audit | Nessun endpoint MVC browser dichiarato pubblico per semplice esposizione |
| API-02 — identità e ACL | Primo | Autenticazione delegata delle applicazioni, mappatura sicura utente Joomla, scope e autorizzazioni oggetto | Valutare OAuth 2.0 / OIDC con provider/estensione adeguata; niente password o token amministratore nell'app e nessun trust di ID arbitrari |
| API-03 — catalogo e iscrizioni | MVP | Lettura corsi pubblici/visibili, programmi e disponibilità, corsi dell'utente, iscrizione/richiesta/stato e idempotenza | Stesse regole di accesso, versioni e disponibilità dell'interfaccia Joomla |
| API-04 — aula headless | Dopo MVP | Lettura lezioni testo, risorse autorizzate, disponibilità, prerequisiti e ripresa; completamento consentito dal dominio | Niente link a file privati senza autorizzazione a ogni richiesta; nessuna attestazione arbitraria di completamento |
| API-05 — player/progressi | Dopo API-04 | Contratti di riproduzione video/audio e raccolta progressi per client esterni | Verifica SDK e TOS dei singoli provider, sessioni, eventi, anti-replay, nessuna progressione autodeclarata; fallback e limiti espliciti |
| API-06 — quiz e risultati | Solo quando scope chiarito | Sessioni quiz con regole, tempi, tentativi, risposte e valutazione lato server | Non esporre chiavi di risposta, punteggi o timer attendibili al client; test abusi/concorrenza |
| API-07 — eventi ed evidenze | Successivo | Attestati, credenziali quando disponibili, eventi e eventuali webhook firmati | Privacy e retention, idempotenza, revoche, nessuna promessa sulle future funzioni Credentials |

Il **primo risultato del rilascio 3** può essere un MVP di catalogo + iscrizione, distinto dalla **fruizione headless completa**. Non dichiarare headless completo finché aula, progressi e i tipi di lezione inclusi non sono supportati e collaudati su client esterni reali. Evitare una singola mega-PR; stimare e approvare PR e priorità soltanto all'avvio del rilascio 3.

## Principi da preservare già nella 1.0.0 (nessuna nuova funzionalità)

- Servizi applicativi e policy del dominio separati dai controller, template e trasporto; riutilizzo degli stessi servizi nei futuri Web Services.
- ACL sempre server-side e controllo oggetto/utente correnti in ogni percorso, non soltanto in UI.
- Transazioni, idempotenza, coerenza dei progressi, versioni dei corsi e audit indipendenti dal client; niente accoppiamento del risultato didattico a eventi/browser.
- Interfacce plugin/provider documentate, ma non promettere che i player desktop web siano automaticamente disponibili in app native.
- Nessuna architettura di login parallela, nessun bypass della sessione/CSRF degli endpoint browser attuali; l'autenticazione delle API esterne deve avere un modello dedicato e sicuro.

## Accettazione futura

Dimostrazione con almeno un client esterno distinto da Joomla che realizzi l'intero flusso **discovery → autenticazione/identità → iscrizione → lezione autorizzata → progresso → completamento**, per i tipi didattici espressamente approvati. Test di revoca iscrizione, autorizzazioni trasversali, scadenza token, concorrenza, retry, privacy/export, rate limiting, compatibilità provider e versionamento dei contratti. Documentazione OpenAPI, esempi e guida integrazione disponibili prima del rilascio delle API.

## Decisione e limiti

Non inserire API pubbliche o headless come requisito di accettazione della 1.0.0; non modificare il piano `docs/STABLE_1.0.0_PR_PLAN.md`. L'obiettivo va mantenuto nel backlog del **rilascio 3**, con approvazione dei requisiti esecutivi e delle PR soltanto dopo il rilascio stabile e una nuova valutazione di priorità.
