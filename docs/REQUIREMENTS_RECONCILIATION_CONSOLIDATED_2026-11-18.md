# Riconciliazione unificata JD Learn 1.0.0 — 18 novembre 2026

## Perimetro e metodologia

**214/214 requisiti** di scope 1.0.0 censiti, **757 criteri atomici**. Distribuzione corrente: **233 implementati con riferimento a evidenze**, **3 mancanti con riscontro specifico**, **521 non ancora verificati**. Il totale della matrice CSV è 249, di cui 35 esclusi perché fase 2.

La riconciliazione **non è conclusa sul merito tecnico**: l'inventario è concluso, non lo è l'audit delle 521 verifiche residue. La metrica globale resta volutamente `null`: assegnare `missing` a un requisito solo perché non è stato ancora verificato sarebbe scorretto. Il limite matematico inferiore del grado di implementazione attestato da evidenze di file è **35.69%** (peso uguale per requisito, non è una stima del completamento reale); quello superiore è **99.77%** e non ha valore predittivo perché gli elementi non verificati potrebbero non essere implementati.

## Anomalie del registro storico

**11 requisiti** sono etichettati `Completata` nel CSV storico pur avendo complessivamente **32 criteri `unassessed`**. La dicitura legacy non costituisce evidenza sufficiente per cambiare stato nella matrice verificata. ID: `PRO-03`, `CERT-06`, `CERT-07`, `MAIL-11`, `QUI-02`, `REP-02`, `ACL-DET-03`, `SCR-04`, `SCR-14`, `EDGE-04`, `PBASE-01`.

## Gap implementativi dimostrati

- `LCM-02/criterion_01` — **missing**: la vista amministrativa `component/administrator/src/View/Courses/HtmlView.php` offre creazione, modifica e programma ma non cestinazione/ripristino nel toolbar.
- `LCM-02/criterion_02` — **missing**: in `component/administrator/src/Model/CoursesModel.php`, il filtro di stato accetta solo `0` e `1`, senza gestire `2` archiviato e `-2` cestinato, e il badge della lista riduce i valori a pubblicato/non pubblicato.
- `LCM-02/criterion_04` — **missing**: non è esposta una transizione di ripristino dal cestino nella UI dei corsi; il workflow `PublicationService` supporta il ripristino di corsi **archiviati**, non lo svuotamento del cestino.
- `LCM-02/criterion_05` — **implemented, conservativo**: `CourseModel::delete()` nega sempre la cancellazione fisica, quindi la nega anche in presenza di iscritti o dipendenze. Questo **non** risolve l'obbligo futuro di consentire l'eliminazione sicura degli oggetti privi di dipendenze.

Una criticità già documentata resta il ripristino `archive -> publish` con potenziale riapertura dell'accesso: non promossa a `missing` senza verificare le combinazioni dei flag `admissions_open` e `delivery_enabled`.

## Registro completo dei 214 requisiti

Per ogni requisito, `Implementati` conta i criteri con evidenza citata, `Mancanti` solo gap verificati e `Da verificare` il resto. Il registro dettagliato per singolo criterio è `docs/FUNCTIONAL_CRITERIA_1.0.0.json`. La vecchia etichetta è riportata **solo** per confronto.

| Requisito | Stato CSV legacy | Criteri | Implementati | Mancanti | Da verificare |
|---|---|---:|---:|---:|---:|
| LCM-03 | Parziale | 5 | 4 | 0 | 1 |
| LCM-04 | Parziale | 4 | 3 | 0 | 1 |
| LCM-05 | Parziale | 7 | 4 | 0 | 3 |
| CAT-01 | Parziale | 5 | 3 | 0 | 2 |
| CAT-02 | Parziale | 5 | 3 | 0 | 2 |
| CAT-03 | Completata | 4 | 4 | 0 | 0 |
| CAT-04 | Parziale | 5 | 2 | 0 | 3 |
| AUT-01 | Parziale | 6 | 5 | 0 | 1 |
| AUT-02 | Completata | 4 | 4 | 0 | 0 |
| AUT-03 | Parziale | 5 | 4 | 0 | 1 |
| AUT-04 | Parziale | 5 | 3 | 0 | 2 |
| AUT-05 | Completata | 3 | 3 | 0 | 0 |
| ISC-01 | Parziale | 4 | 3 | 0 | 1 |
| ISC-02 | Parziale | 3 | 2 | 0 | 1 |
| ISC-04 | Parziale | 4 | 2 | 0 | 2 |
| ISC-05 | Parziale | 3 | 2 | 0 | 1 |
| ISC-06 | Completata | 2 | 2 | 0 | 0 |
| ISC-07 | Parziale | 3 | 1 | 0 | 2 |
| ISC-08 | Parziale | 3 | 2 | 0 | 1 |
| STU-01 | Parziale | 4 | 3 | 0 | 1 |
| STU-02 | Parziale | 3 | 2 | 0 | 1 |
| STU-03 | Parziale | 3 | 2 | 0 | 1 |
| STU-04 | Parziale | 3 | 1 | 0 | 2 |
| PRO-01 | Completata | 2 | 2 | 0 | 0 |
| PRO-02 | Completata | 3 | 3 | 0 | 0 |
| PRO-03 | Completata | 3 | 2 | 0 | 1 |
| PRO-06 | Parziale | 4 | 2 | 0 | 2 |
| LES-01 | Parziale | 4 | 3 | 0 | 1 |
| LES-02 | Parziale | 3 | 1 | 0 | 2 |
| LES-03 | Parziale | 3 | 2 | 0 | 1 |
| LES-04 | Parziale | 2 | 1 | 0 | 1 |
| VID-01 | Parziale | 3 | 2 | 0 | 1 |
| VID-02 | Parziale | 4 | 3 | 0 | 1 |
| VID-03 | Parziale | 4 | 3 | 0 | 1 |
| VID-04 | Parziale | 4 | 2 | 0 | 2 |
| VID-05 | Parziale | 3 | 2 | 0 | 1 |
| VID-06 | Parziale | 4 | 2 | 0 | 2 |
| VID-07 | Parziale | 3 | 2 | 0 | 1 |
| VID-08 | Parziale | 3 | 2 | 0 | 1 |
| MAT-01 | Parziale | 3 | 2 | 0 | 1 |
| MAT-02 | Parziale | 4 | 3 | 0 | 1 |
| MAT-03 | Parziale | 4 | 3 | 0 | 1 |
| MAT-04 | Parziale | 3 | 2 | 0 | 1 |
| MAT-05 | Parziale | 3 | 2 | 0 | 1 |
| MAT-06 | Parziale | 4 | 2 | 0 | 2 |
| MAT-07 | Parziale | 3 | 2 | 0 | 1 |
| MAT-08 | Parziale | 4 | 3 | 0 | 1 |
| NOT-01 | Parziale | 4 | 2 | 0 | 2 |
| NOT-02 | Parziale | 4 | 2 | 0 | 2 |
| CERT-01 | Parziale | 2 | 1 | 0 | 1 |
| CERT-02 | Parziale | 2 | 1 | 0 | 1 |
| CERT-03 | Parziale | 3 | 1 | 0 | 2 |
| CERT-04 | Parziale | 2 | 1 | 0 | 1 |
| CERT-05 | Parziale | 3 | 1 | 0 | 2 |
| CERT-06 | Completata | 2 | 1 | 0 | 1 |
| CERT-07 | Completata | 2 | 1 | 0 | 1 |
| CERT-08 | Parziale | 3 | 1 | 0 | 2 |
| MAIL-01 | Parziale | 2 | 1 | 0 | 1 |
| MAIL-02 | Parziale | 2 | 1 | 0 | 1 |
| MAIL-03 | Parziale | 3 | 1 | 0 | 2 |
| MAIL-04 | Parziale | 2 | 1 | 0 | 1 |
| MAIL-05 | Parziale | 2 | 1 | 0 | 1 |
| MAIL-06 | Parziale | 2 | 1 | 0 | 1 |
| MAIL-07 | Parziale | 2 | 1 | 0 | 1 |
| MAIL-08 | Parziale | 2 | 1 | 0 | 1 |
| MAIL-09 | Parziale | 3 | 2 | 0 | 1 |
| MAIL-10 | Parziale | 2 | 1 | 0 | 1 |
| MAIL-11 | Completata | 2 | 1 | 0 | 1 |
| JOO-01 | Parziale | 3 | 2 | 0 | 1 |
| JOO-02 | Parziale | 2 | 1 | 0 | 1 |
| JOO-03 | Parziale | 2 | 1 | 0 | 1 |
| JOO-04 | Parziale | 2 | 1 | 0 | 1 |
| JOO-05 | Parziale | 2 | 1 | 0 | 1 |
| SIC-01 | Parziale | 4 | 2 | 0 | 2 |
| SIC-02 | Parziale | 3 | 2 | 0 | 1 |
| PRI-01 | Parziale | 3 | 1 | 0 | 2 |
| PRI-02 | Parziale | 3 | 1 | 0 | 2 |
| PRI-03 | Parziale | 3 | 1 | 0 | 2 |
| CFG-01 | Parziale | 2 | 1 | 0 | 1 |
| CFG-02 | Parziale | 2 | 1 | 0 | 1 |
| CFG-03 | Parziale | 2 | 1 | 0 | 1 |
| CFG-04 | Parziale | 2 | 1 | 0 | 1 |
| CFG-05 | Parziale | 2 | 1 | 0 | 1 |
| CFG-06 | Parziale | 2 | 1 | 0 | 1 |
| CORE-01 | Parziale | 2 | 1 | 0 | 1 |
| CORE-02 | Parziale | 2 | 1 | 0 | 1 |
| CORE-03 | Parziale | 2 | 1 | 0 | 1 |
| CORE-04 | Parziale | 3 | 1 | 0 | 2 |
| CORE-05 | Parziale | 2 | 1 | 0 | 1 |
| CORE-06 | Parziale | 2 | 1 | 0 | 1 |
| CORE-07 | Parziale | 2 | 1 | 0 | 1 |
| CORE-08 | Parziale | 2 | 1 | 0 | 1 |
| CORE-09 | Parziale | 2 | 1 | 0 | 1 |
| CORE-10 | Parziale | 2 | 1 | 0 | 1 |
| CORE-11 | Parziale | 2 | 1 | 0 | 1 |
| CORE-12 | Parziale | 2 | 1 | 0 | 1 |
| CORE-13 | Parziale | 2 | 1 | 0 | 1 |
| CORE-14 | Parziale | 2 | 1 | 0 | 1 |
| CORE-15 | Parziale | 2 | 1 | 0 | 1 |
| CORE-16 | Parziale | 2 | 1 | 0 | 1 |
| CORE-17 | Parziale | 2 | 1 | 0 | 1 |
| CORE-18 | Parziale | 2 | 1 | 0 | 1 |
| CORE-19 | Parziale | 2 | 1 | 0 | 1 |
| CORE-20 | Parziale | 2 | 1 | 0 | 1 |
| CORE-21 | Parziale | 2 | 1 | 0 | 1 |
| CORE-22 | Parziale | 2 | 1 | 0 | 1 |
| CORE-23 | Parziale | 2 | 1 | 0 | 1 |
| STU-05 | Parziale | 2 | 1 | 0 | 1 |
| PRO-04 | Parziale | 3 | 2 | 0 | 1 |
| PRO-05 | Parziale | 2 | 1 | 0 | 1 |
| ACC-01 | Parziale | 4 | 1 | 0 | 3 |
| UI-01 | Parziale | 2 | 1 | 0 | 1 |
| UI-02 | Parziale | 2 | 1 | 0 | 1 |
| UI-03 | Parziale | 2 | 1 | 0 | 1 |
| UI-04 | Parziale | 2 | 1 | 0 | 1 |
| UI-05 | Parziale | 2 | 1 | 0 | 1 |
| UI-06 | Parziale | 2 | 1 | 0 | 1 |
| UI-07 | Parziale | 2 | 1 | 0 | 1 |
| PERF-01 | Non iniziata | 2 | 0 | 0 | 2 |
| PERF-02 | Non iniziata | 2 | 0 | 0 | 2 |
| PERF-03 | Non iniziata | 2 | 0 | 0 | 2 |
| PERF-04 | Parziale | 2 | 1 | 0 | 1 |
| PERF-05 | Parziale | 2 | 1 | 0 | 1 |
| PERF-06 | Parziale | 2 | 1 | 0 | 1 |
| PERF-07 | Parziale | 3 | 2 | 0 | 1 |
| PERF-08 | Non iniziata | 3 | 1 | 0 | 2 |
| PERF-09 | Parziale | 2 | 1 | 0 | 1 |
| PERF-10 | Non iniziata | 2 | 0 | 0 | 2 |
| PERF-11 | Parziale | 2 | 1 | 0 | 1 |
| QUI-02 | Completata | 5 | 0 | 0 | 5 |
| REP-02 | Completata | 4 | 0 | 0 | 4 |
| ACL-DET-01 | Parziale | 4 | 1 | 0 | 3 |
| ACL-DET-02 | Parziale | 4 | 1 | 0 | 3 |
| ACL-DET-03 | Completata | 3 | 0 | 0 | 3 |
| ACL-DET-04 | Parziale | 4 | 1 | 0 | 3 |
| STA-01 | Parziale | 4 | 0 | 0 | 4 |
| STA-02 | Parziale | 4 | 0 | 0 | 4 |
| STA-03 | Parziale | 4 | 0 | 0 | 4 |
| SCR-01 | Parziale | 6 | 0 | 0 | 6 |
| SCR-02 | Parziale | 4 | 2 | 0 | 2 |
| SCR-03 | Parziale | 5 | 2 | 0 | 3 |
| SCR-04 | Completata | 5 | 3 | 0 | 2 |
| SCR-05 | Parziale | 5 | 2 | 0 | 3 |
| SCR-06 | Parziale | 7 | 1 | 0 | 6 |
| SCR-07 | Parziale | 6 | 0 | 0 | 6 |
| SCR-08 | Parziale | 5 | 0 | 0 | 5 |
| SCR-13 | Parziale | 4 | 0 | 0 | 4 |
| SCR-14 | Completata | 3 | 0 | 0 | 3 |
| SCR-15 | Parziale | 4 | 0 | 0 | 4 |
| SCR-16 | Parziale | 5 | 0 | 0 | 5 |
| LIFE-01 | Parziale | 4 | 2 | 0 | 2 |
| LIFE-02 | Parziale | 5 | 0 | 0 | 5 |
| LIFE-03 | Parziale | 3 | 0 | 0 | 3 |
| LIFE-09 | Parziale | 3 | 0 | 0 | 3 |
| EDGE-01 | Parziale | 8 | 0 | 0 | 8 |
| EDGE-02 | Parziale | 7 | 0 | 0 | 7 |
| EDGE-03 | Parziale | 7 | 0 | 0 | 7 |
| EDGE-04 | Completata | 9 | 0 | 0 | 9 |
| EDGE-05 | Parziale | 8 | 0 | 0 | 8 |
| EDGE-08 | Parziale | 8 | 0 | 0 | 8 |
| EDGE-09 | Parziale | 9 | 0 | 0 | 9 |
| EDGE-11 | Parziale | 9 | 0 | 0 | 9 |
| EDGE-12 | Parziale | 9 | 0 | 0 | 9 |
| EDGE-13 | Parziale | 7 | 0 | 0 | 7 |
| EDGE-14 | Parziale | 6 | 0 | 0 | 6 |
| DAT-01 | Parziale | 10 | 0 | 0 | 10 |
| DAT-02 | Parziale | 2 | 0 | 0 | 2 |
| DAT-04 | Parziale | 3 | 0 | 0 | 3 |
| DAT-08 | Parziale | 4 | 0 | 0 | 4 |
| REL-01 | Parziale | 6 | 0 | 0 | 6 |
| REL-02 | Parziale | 4 | 0 | 0 | 4 |
| REL-03 | Parziale | 3 | 0 | 0 | 3 |
| REL-04 | Parziale | 6 | 0 | 0 | 6 |
| PVD-01 | Parziale | 3 | 3 | 0 | 0 |
| PVD-02 | Parziale | 8 | 6 | 0 | 2 |
| PVD-03 | Parziale | 6 | 0 | 0 | 6 |
| PVD-04 | Parziale | 5 | 0 | 0 | 5 |
| PVD-05 | Parziale | 3 | 0 | 0 | 3 |
| PVD-06 | Parziale | 3 | 1 | 0 | 2 |
| HTTP-01 | Parziale | 3 | 0 | 0 | 3 |
| HTTP-02 | Parziale | 4 | 0 | 0 | 4 |
| HTTP-03 | Parziale | 6 | 0 | 0 | 6 |
| HTTP-04 | Parziale | 3 | 0 | 0 | 3 |
| HTTP-05 | Parziale | 3 | 0 | 0 | 3 |
| HTTP-06 | Parziale | 3 | 0 | 0 | 3 |
| SECDEV-01 | Parziale | 4 | 0 | 0 | 4 |
| SECDEV-02 | Parziale | 4 | 0 | 0 | 4 |
| SECDEV-03 | Parziale | 4 | 0 | 0 | 4 |
| SECDEV-04 | Parziale | 5 | 1 | 0 | 4 |
| SECDEV-05 | Parziale | 5 | 0 | 0 | 5 |
| SECDEV-06 | Parziale | 4 | 0 | 0 | 4 |
| SECDEV-07 | Parziale | 5 | 0 | 0 | 5 |
| SECDEV-08 | Parziale | 5 | 0 | 0 | 5 |
| PBASE-01 | Completata | 5 | 3 | 0 | 2 |
| PBASE-02 | Parziale | 3 | 0 | 0 | 3 |
| PBASE-03 | Parziale | 6 | 0 | 0 | 6 |
| PBASE-04 | Parziale | 4 | 0 | 0 | 4 |
| DOC-01 | Parziale | 4 | 0 | 0 | 4 |
| DOC-02 | Parziale | 4 | 0 | 0 | 4 |
| DOC-03 | Parziale | 4 | 0 | 0 | 4 |
| DOC-04 | Parziale | 4 | 0 | 0 | 4 |
| DOC-05 | Non iniziata | 3 | 0 | 0 | 3 |
| DOC-06 | Parziale | 3 | 0 | 0 | 3 |
| DOC-07 | Non iniziata | 3 | 0 | 0 | 3 |
| DOC-08 | Non iniziata | 3 | 0 | 0 | 3 |
| DOC-09 | Non iniziata | 4 | 0 | 0 | 4 |
| DOC-10 | Parziale | 4 | 0 | 0 | 4 |
| SEC-P1 | Parziale | 2 | 0 | 0 | 2 |
| SEC-P2 | Parziale | 2 | 0 | 0 | 2 |
| SEC-P3 | Parziale | 2 | 0 | 0 | 2 |
| SEC-P4 | Parziale | 4 | 0 | 0 | 4 |
| SEC-P5 | Non iniziata | 4 | 0 | 0 | 4 |
| LCM-01 | Non iniziata | 3 | 0 | 0 | 3 |
| LCM-02 | Non iniziata | 6 | 1 | 3 | 2 |

## Chiusura richiesta per 1.0.0

I criteri `unassessed` devono ricevere verifica nel runtime/nel codice e nelle fixture pertinenti; i criteri implementati richiedono verifica delle evidenze rispetto all'intero significato del requisito, non solo la presenza del file. Solo alla fine si aggiorneranno la percentuale funzionale e lo stato legacy del CSV. L'accettazione Joomla reale e i gate di rilascio sono assi separati.

Non è stata modificata la versione SemVer e non è stato eseguito un collaudo su un'installazione Joomla esterna.

## Comportamenti da testare

- `tools/functional_progress.py` continua a emettere `functional_implementation_percent = null`; i conteggi dei criteri e i file evidence sono validi.
- La negazione della cancellazione diretta e la mancata disponibilità della toolbar del cestino restano testabili sulle fixture native; la presente PR non modifica runtime.
- Lo storico `Completata` non genera implementazioni automatiche né appiattisce differenze tra fase 1 e fase 2.
