# JD Learn — note della candidata 1.0.0 (bozza controllata)

**Stato:** bozza preparatoria, **non** note di una release 1.0.0 pubblicata o approvata. La build corrente va sempre letta da `packages/pkg_jlms.xml`: non sostituire automaticamente il numero della build di sviluppo con `1.0.0`. Ultimo aggiornamento redazionale: 22 novembre 2026.

## Novità nel perimetro approvato

La prima versione stabile punta a fornire amministrazione di corsi e lezioni, edizioni/versioni didattiche, catalogo e percorsi, iscrizioni e tracciamento essenziale, provider video approvati, notifiche tramite Mail Templates Joomla e attestati PDF essenziali. La disponibilità effettiva e il grado di implementazione di ciascun requisito sono descritti in [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv) e nell'[audit funzionale](FUNCTIONAL_AUDIT_CURRENT.md): **questo elenco non certifica l'accettazione**.

## Compatibilità dichiarata e verifiche aperte

- I manifest del pacchetto e del componente sono la fonte autorevole per la versione; la matrice tecnica di riferimento è Joomla 6.x con PHP almeno 8.3, **da convalidare per il rilascio stabile**. Non estendere il supporto a Joomla/PHP/database non provati.
- Una installazione o un upgrade della candidata deve usare un solo `pkg_jlms-VERSION-dev.zip` estratto dall'artefatto GitHub Actions; l'archivio esterno di Actions **non è** il package Joomla da installare.
- Il feed Joomla e il rilascio stabile sono subordinati ai gate e alle autorizzazioni descritti in [RELEASE_PROCEDURE.md](RELEASE_PROCEDURE.md). Questa bozza non abilita, esegue né autorizza una pubblicazione.

## Incompatibilità e limitazioni note

- Prima della 1.0.0 le versioni `0.x` sono di sviluppo; la compatibilità pubblica delle API non è dichiarata stabile. In caso di upgrade usare backup coerente di file e database e seguire la procedura di ripristino, senza assumere rollback automatici.
- Il programma didattico e le notifiche hanno dipendenze operative da configurazione Joomla, permessi, Mail Templates e Scheduler. La sola installazione del pacchetto non prova che email e task vengano eseguiti nel sito.
- Il player video dipende anche da autorizzazioni di embedding e requisiti dei provider; la CI non sostituisce la verifica di riproduzione e completamento nell'ambiente Joomla di destinazione.
- L'emissione attestati PDF è soggetta alle regole della versione didattica e alle impostazioni di rilascio. Non assumere che ogni completamento produca automaticamente un attestato.
- Quiz avanzati, reportistica KPI avanzata, import/export massivi, quinto provider video, newsletter generaliste e Open Badges restano **fuori** dal perimetro 1.0.0.
- L'accettazione manuale Joomla, i test di carico e accessibilità, il ripristino con dati reali e il gate di sicurezza complessivo rimangono separati dai risultati ordinari della CI. Le lacune confermate sono censite nell'audit funzionale.

## Pacchetto, checksum e corrispondenza versione

Per ogni **candidata effettiva** compilare, con risultati verificati dello stesso workflow GitHub Actions:

| Campo | Evidenza obbligatoria |
|---|---|
| Versione manifest | `packages/pkg_jlms.xml` e `component/jlms.xml` concordi |
| Commit sorgente | SHA completo del commit candidato |
| Workflow e artefatto | URL del run CI e dell'artefatto non scaduto |
| ZIP Joomla interno | Nome `pkg_jlms-VERSION-dev.zip` o `pkg_jlms-1.0.0.zip` solo se il workflow di release ha costruito quest'ultimo |
| SHA-256 | Digest **dello ZIP Joomla interno**, verificato indipendentemente dal digest del contenitore Actions |
| Esiti | Test automatici, difetti noti, prove manuali e decisione di accettazione, con riferimenti distinti |

**Nessuna versione e nessun checksum sono precompilati qui**: una nota di rilascio non può riferirsi a un pacchetto di una PR diversa o a una build superata. Per ottenere il package di sviluppo vedere [RELEASE_PROCEDURE.md](RELEASE_PROCEDURE.md), per il changelog puntuale vedere [CHANGELOG.md](../CHANGELOG.md), per lo stato dei requisiti [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

## Gate prima di pubblicare le note definitive

1. Rileggere versione, changelog, dipendenze, compatibilità e limiti sulla **stessa** candidata assemblata.
2. Registrare SHA, run, artefatto, checksum interno ed esiti del testbook; non trasformare una prova automatica in accettazione manuale.
3. Verificare rilascio e rollback su Joomla con i dati previsti dal piano; documentare gli eventuali difetti residui.
4. Pubblicare note definitive solo con decisione esplicita del gate 1.0.0, tenendo distinta la release stabile dal canale `-dev`.
