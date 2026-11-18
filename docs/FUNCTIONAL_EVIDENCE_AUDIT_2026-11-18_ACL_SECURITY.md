# Audit probatorio JD Learn 1.0.0 — ACL e confini di sicurezza (18 novembre 2026)

## Evidenze confermate

Questa tranche verifica **quattro criteri** della matrice `docs/FUNCTIONAL_CRITERIA_1.0.0.json` con controlli reali di codice e fixture CI:

| Requisito / criterio | Riscontro |
| --- | --- |
| `ACL-DET-01/direct_actions` | `CoursesController::programme` richiede POST, token CSRF, identità Joomla corrente e permesso `core.edit` o `core.edit.own` sull'oggetto. `tests/courses-ui-http.py` e `tests/joomla-editor-access.php` verificano richieste manipolate e negazioni per record. |
| `ACL-DET-02/admin_gate` | `CourseEditorAccess::fresh` rifiuta identità assenti, bloccate o prive di `core.manage` sul componente; `CoursesModel` usa lo stesso filtro per le liste amministrative. La fixture copre il diniego delle liste a un utente anonimo. **Non implica audit completo di ogni task backend**. |
| `ACL-DET-04/direct_url` | I test HTTP rifiutano GET su task di modifica, POST senza CSRF e parametri manipolati; `tests/learning-http.py` verifica l'accesso diretto all'aula negato agli anonimi e ad altri iscritti, oltre alla revoca del gruppo in sessione attiva. Sono **esempi verificati**, non una matrice esaustiva dei ruoli. |
| `SECDEV-04/criterion_04` | `CoursesController::programme` verifica **separatamente** il token CSRF e il permesso sull'oggetto: possedere un token valido non attribuisce diritti editoriali. Le fixture verificano il modello di autorizzazione granulare. |

Le evidenze nella matrice indicano file sorgente e test esistenti nel repository; non sono dichiarazioni di collaudo manuale.

## Limiti e residui

- **ACL-DET-01**: capacità distinte per pubblicazione, lettura risultati e cancellazione, e separazione del docente dai privilegi amministrativi richiedono verifica trasversale.
- **ACL-DET-02**: gestione completa delle credenziali, masking nei form e assenza di leak in cache/log restano `unassessed`; la presenza di `EnvironmentSecretProvider` da sola non prova un hardening complessivo.
- **ACL-DET-03**: anteprima amministrativa, assenza di progressi e normale iscrizione dell'operatore devono essere provati separatamente.
- **ACL-DET-04**: copertura completa query, download, ruoli differenti e risorse protette è ancora da eseguire.
- **SECDEV-03/04, SEC-P1 e HTTP-03**: i criteri descrivono invarianti globali su molti endpoint. I test di pochi controller non ne dimostrano ancora l'implementazione uniforme; nessuno di tali requisiti viene chiuso per analogia.
- I problemi funzionali sul lifecycle corsi già riportati nel precedente audit non vengono considerati risolti.

La percentuale funzionale globale resta `null` finché non è completata l'analisi dei criteri, senza sovrapporre codice, CI e accettazione Joomla. Nessun incremento SemVer per una sola modifica documentale.

## Comportamenti da testare

- Check della struttura JSON, evidenze per tutti i criteri marcati `implemented`, numero 214 requisiti in scope e assenza di percentuale globale prematura.
- Test delle fixture `tests/courses-ui-http.py`, `tests/joomla-editor-access.php` e `tests/learning-http.py` nelle esecuzioni CI di integrazione appropriate (non necessariamente eseguite in ogni PR documentale).
- Collaudo Joomla dedicato: sessioni e ruoli limitati, tentativi diretti di modificare un corso altrui, download protetti, recupero e revoca dei permessi, csrf e accesso ai video/materiali. Esito non ancora acquisito.
