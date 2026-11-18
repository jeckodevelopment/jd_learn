# Audit probatorio lifecycle corsi — 18 novembre 2026

## Esito della verifica

Due criteri di **LIFE-01** passano da `unassessed` a `implemented`, con riferimenti verificati nel codice e in un test Joomla CI.

- `LIFE-01/criterion_01`: `CourseModel::delete()` nega sempre la cancellazione editoriale diretta, e quindi impedisce anche la cancellazione di un corso con iscrizioni/tentativi/attestati. È una garanzia *conservativa*, non equivale a un'implementazione di purga sicura.
- `LIFE-01/criterion_02`: l'archiviazione/ripristino avvengono tramite transizioni della `PublicationService`, mentre il comando editoriale `delete()` rimane disabilitato. Le transizioni sono distinte dalla cancellazione fisica e la fixture `tests/joomla-publication-review.php` verifica la conservazione della versione pubblicata.

## Criticità e residui concreti

1. **LCM-02 non ancora dimostrato**: non risultano nei percorsi controllati operazioni complete di cestinazione e ripristino dei corsi; il metodo `CourseModel::delete()` è sempre bloccato. Il filtro amministrativo `CoursesModel::getListQuery()` accetta per `filter.published` solo `0` e `1`, non `2` (archiviati) o `-2` (cestinati). Non marcare quindi `LCM-02` completo né dedurre che il cestino sia disponibile.
2. **LCM-01 resta da verificare in tutti i punti**: `tests/joomla-publication-review.php` dimostra che `state=2` nasconde il catalogo e nega la consultazione del corso archiviato, conservando `current_version_id`. Non dimostra ancora il divieto di nuove iscrizioni e di ogni endpoint diretto, la permanenza di tutti i record (attestati inclusi), né la regola di riattivazione.
3. **Ripristino da confrontare con specifica**: il test `joomla-publication-review.php` afferma e verifica esplicitamente che il ripristino riporta `state=1` e lo stage `JPUBLISHED` sulla medesima versione immutabile. Verificare se ciò riapra la fruizione/iscrizione quando i flag `delivery_enabled` e `admissions_open` erano attivi prima dell'archiviazione. La regola `LCM-01` richiede riattivazione senza riapertura automatica: possibile conflitto che necessita intervento funzionale, senza dichiararlo risolto.
4. **LIFE-01/criterion_03 e 04**, **LIFE-02**, **LIFE-03**, **LIFE-09**: permessi specifici per una purga futura, dipendenze esterne, assenza di cascata non dichiarata, piena coerenza delle operazioni dirette e preservazione di tutti gli storici richiedono ricognizione/implementazione supplementare. `CategoryReassignmentService` offre una ricollocazione esplicita, transazionale e protetta da ACL, ma la sola presenza del servizio non prova tutta la policy di eliminazione categoria.

## Comportamenti da testare

- Validazione del registro criteri, percorsi `evidence` esistenti e percentuale globale ancora non misurabile.
- Test CI di transizioni `tests/joomla-publication-review.php` e struttura.
- In futura verifica Joomla: corso con iscritti/attestati, archiviazione, tentativi di accesso a ogni task, storico invariato, ripristino senza riaprire automaticamente ammissioni o frequenza, gestione del cestino e ACL dei ruoli ridotti.

La presente PR è **solo documentale**, non introduce modifiche al runtime, non conclude l'accettazione Joomla e non modifica il SemVer.
