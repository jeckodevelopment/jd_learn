# CORE-08–11 — ricognizione e correzione cache scheda docente

## Esito implementativo del lotto (26 novembre 2026)

- **CORE-08**: il perimetro privacy è già coperto da plugin e servizi dedicati; la policy di hosting, conservazione e backup richiede un'accettazione distinta.
- **CORE-09**: le associazioni ai tag sono già basate su com_tags nei contesti previsti. Il collaudo dei contesti e filtri reali resta distinto.
- **CORE-10**: la pagina pubblica di un docente usa un modello elenco dei corsi. La query usa docente, lingua e diritti Joomla attuali, ma il suo `getStoreId()` non incorporava questi parametri; l'aggiunta evita collisioni di cache tra profili, identità o lingue all'interno del modello.
- **CORE-11**: gli eventi content prepare e la sanitizzazione delle descrizioni pubbliche sono già previsti; resta la verifica dei plugin installati e degli editor reali.

## Correzione CORE-10

`component/site/src/Model/TeacherModel.php` ora incorpora l'ID del docente richiesto, l'ID Joomla e la lingua attiva nella chiave nativa della cache `ListModel`. Non cambia il rendering dei campi personalizzati né il filtro ACL della query, che resta in `CatalogService`.

`tests/joomla-teachers.php` confronta in un ambiente Joomla installato la stessa chiave per docenti diversi, per guest e utente autorizzato, e per il ritorno all'identità originale. Un cambiamento non autorizzato della lingua non è simulato artificialmente nella fixture CLI; resta il confronto manuale sul sito con lingua IT/EN.

## Collaudo manuale separato (NON ESEGUITO)

Con Joomla 6.2.1, creare due profili docente con corsi pubblici e riservati distinti, aprire i profili alternativamente da guest e utente autorizzato e ripetere in IT/EN, SEF on/off e cache Joomla attiva. Verificare che schede, campi e corsi non migrino fra docente e lingua. Registrare gli esiti nel testbook; non modificare le percentuali finché le verifiche canoniche non risultano complete.
