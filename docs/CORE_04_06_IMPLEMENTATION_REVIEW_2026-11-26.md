# CORE-04–06 — verifica residui e hardening locale (26 novembre 2026)

## Ricognizione implementativa

Il riuso dei componenti Joomla non deve produrre implementazioni duplicate.

- **CORE-04:** i controlli per record/docente, le transazioni batch con rollback su diniego e le prove di sessione/checkout sono presenti in `TeacherModel`, `TeacherProfiles` e `tests/joomla-teachers.php`. Le combinazioni complete di ruoli e editor sono collaudo separato; non è stato individuato in questo lotto un difetto certo da correggere.
- **CORE-05:** è già disponibile l'inventario delle 46 viste MVC con il gate `tests/secdev02-core05-overrides-inventory.py`. La prova di override con template installati sul sito rimane da eseguire separatamente.
- **CORE-06:** il gate `tests/secdev02-core06-locale-integrity.py` controlla già coppie IT/EN, chiavi duplicate e segnaposto. È stato rilevato un difetto del parser del gate: valori con virgolette interne non escape potevano passare perché venivano controllate soltanto le virgolette iniziale e finale.

## Correzione effettiva CORE-06

`parse()` ora rifiuta valori INI malformati per virgolette non escape o escape troncate e preserva la possibilità di usare virgolette escape valide. Le fixture negative coprono virgolette interne e testo dopo la chiusura; la positiva verifica un valore con virgolette escape.

Questo è un **miglioramento del controllo di qualità delle traduzioni**, non una nuova funzione utente e non una certificazione di tutte le interfacce o email.

## Stato e collaudo

La matrice requisiti mantiene CORE-04, CORE-05 e CORE-06 **Parziale**. Nessun criterio canonico è promosso a `implemented` e la percentuale globale non varia.

**Collaudo Joomla manuale: NON ESEGUITO.** Installare il package con IT/EN, provare gli override lingua nativi per menu e frontend/backend, messaggi d'errore e email effettivamente configurate; registrare gli esiti nel testbook. Per CORE-04 provare selezioni miste autorizzate/negate e revoche; per CORE-05 due personalizzazioni indipendenti con template override reali.
