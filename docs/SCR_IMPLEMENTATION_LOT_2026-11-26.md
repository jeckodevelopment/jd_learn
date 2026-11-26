# SCR — lotto integrato interfacce, categorie e sicurezza risultati (26/11/2026)

## Perimetro

Ricognizione sulla baseline 1.0.0, requisiti **SCR-01–08, SCR-13–16**. In `docs/FUNCTIONAL_CRITERIA_1.0.0.json` sono presenti **6 criteri `unverifiable`**, mentre SCR-03, SCR-04, SCR-05, SCR-07, SCR-14, SCR-15 e SCR-16 sono già interamente documentati come `implemented`. Il lotto non ricrea funzionalità esistenti e non considera la CI una prova di accettazione manuale.

## Implementazione e prove aggiunte

| Perimetro | Intervento | Evidenza |
| --- | --- | --- |
| SCR-01/acl_results | Regressioni native sul risultato SQL pubblico: esclude categoria con ACL ristretta, lingua diversa e ricerca che conosce il titolo di un corso nascosto | `tests/joomla-categories.php`; `CatalogService::query` |
| SCR-02/dependencies | Il riepilogo amministrativo distingue **riferimenti diretti** e **riferimenti nel ramo sottocategorie** (corsi, percorsi, docenti, domande), conta le sottocategorie e segnala che la ricollocazione agisce solo sui diretti | `CategoryReassignmentService::inspect`, `component/administrator/tmpl/categoryreassignment/default.php`, `tests/joomla-categories.php` |
| SCR-06/criterion_01 | Il POST HTML **Segna come completata** mostra uno stato **in salvataggio** fino al nuovo caricamento con risposta server. Blocca doppio invio e il receipt concorrente `lesson.begin` non può mostrare erroneamente «Salvato» durante la conferma manuale. Il ritorno dalla BFCache ripristina il pulsante senza dichiarare un salvataggio | `component/media/js/lesson-progress.js`, `tests/frontend-progress-feedback.mjs` |
| SCR-06/criterion_04 | Conservata la regola server-side di copertura video: nessun completamento manuale implicito viene introdotto | `VideoProgressService`, `tests/joomla-video.php` già esistenti |
| SCR-08/criterion_05 | L'implementazione non introduce fallback né link esterni che aggirino consenso e accesso. L'alternativa formativa richiede una scelta editoriale esplicita e un collaudo sui contenuti del sito; non viene simulata automaticamente | `component/media/js/video.js`, `docs/VIDEO.md` |
| SCR-13/criterion_01 | Il controllo di tutti gli scenari editoriali AC-129–AC-134 è **accettazione manuale**: la CI copre il programma contestuale e gli editor nativi, ma non ogni viewport/role workflow reale | `tests/joomla-curriculum-builder.php`, `docs/QA_TESTBOOK.md` |

### Scelte e limiti

- La schermata delle dipendenze è **amministrativa**, richiede il permesso corrente della categoria e non rende pubblico il conteggio di record inaccessibili. La query di conteggio sul sottoalbero non modifica record, categorie Joomla o versioni.
- La ricollocazione resta atomica sui riferimenti diretti, con validazione ACL e checkout; **i discendenti non sono ricollocati automaticamente**. La modifica delle strutture gerarchiche rimane nel gestore nativo Joomla.
- Lo stato del salvataggio manuale è «in corso» fino alla navigazione di ritorno: la conferma vera è emessa dal server dopo l'operazione. Una richiesta fallita non viene dichiarata salvata.
- I sei criteri `unverifiable` restano nell'audit finché l'intera formulazione non dispone di prova sufficiente; non vengono promossi per solo aggiornamento dei test o della documentazione. Per SCR-01/02/06 l'evidenza tecnica è stata ampliata, senza spacciare per completi i casi ancora non verificati.
- I collaudi Joomla e di plugin terzi non sono stati eseguiti dall'utente in questo lotto. `docs/QA_TESTBOOK.md` è la fonte dei casi da provare.

## Requisiti e rilascio

Il riepilogo funzionale globale continua a derivare dal registro canonico con `tools/functional_audit.py --check`. Nessun progresso percentuale è anticipato senza nuovi criteri marcati `implemented` sulla base di evidenze esaustive. Pacchetto e CI a due combinazioni PHP/database richiesti sul commit candidato.
