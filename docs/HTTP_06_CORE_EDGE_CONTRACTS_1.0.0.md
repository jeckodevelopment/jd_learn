# HTTP-06 — registro delle prove dei contratti core Joomla 1.0.0

**Perimetro approvato:** `docs/RELEASE_SCOPE_MATRIX.md`. Il catalogo `specifications/Catalogo_endpoint_LMS_Joomla_1.0.csv` descrive task *proposti*: non è un elenco di API effettivamente esposte. Le route runtime e i controller sono i riferimenti tecnici effettivi.

## Casi limite su HTTP Joomla reale

| Caso | Route effettiva | Evidenza test | Aspettativa | Stato |
| --- | --- | --- | --- | --- |
| HTTP06-01 | `enrollment.start` GET | `tests/learning-http.py` | 405 senza iscrizione | CI, non accettato manualmente |
| HTTP06-02 | `enrollment.request` GET | `tests/learning-http.py` | 405 senza richiesta | CI, non accettato manualmente |
| HTTP06-03 | `lesson.complete` GET | `tests/learning-http.py` | 405 senza completamento | CI, non accettato manualmente |
| HTTP06-04 | `lesson.begin` GET | `tests/learning-http.py` | 405 senza sessione iniziata | CI, non accettato manualmente |
| HTTP06-05 | `enrollment.start/request`, `lesson.complete` POST senza CSRF | `tests/learning-http.py` | 403; nessuna mutazione | CI, non accettato manualmente |
| HTTP06-06 | `lesson.begin` POST senza CSRF | `tests/learning-http.py` | 403, errore JSON applicativo | CI, non accettato manualmente |
| HTTP06-07 | `enrollment.unrecognised` e `lesson.unrecognised` | `tests/learning-http.py` | 404 | CI, non accettato manualmente |
| HTTP06-08 | `video.playback/save/renew/close` GET | `tests/video-http.py` | 405, JSON di errore, senza dati provider | CI, non accettato manualmente |
| HTTP06-09 | `video.playback/save/renew/close` POST senza CSRF | `tests/video-http.py` | 403, JSON di errore | CI, non accettato manualmente |
| HTTP06-10 | `video.unknown` | `tests/video-http.py` | 404 | CI, non accettato manualmente |
| HTTP06-11 | `enrollment.start` replay con stessa chiave | `tests/learning-http.py` | stessa iscrizione, nessun doppione | CI preesistente, non accettato manualmente |
| HTTP06-12 | `video.save` replay, revisione errata, limite payload, chiusura | `tests/video-http.py` | idempotenza, 409, 413, 409 dopo close | CI preesistente, non accettato manualmente |

Le prove sopra si eseguono nelle due combinazioni Joomla/PHP/database della CI con dati usa-e-getta. La colonna «CI» significa scenario collegato a un test *da validare su un run specifico*; soltanto un run verde permette di descriverlo come superato. Il registro non equivale all'intero elenco dei casi limite della baseline, né a prova di penetrazione o collaudo Joomla su hosting reale.

## Prove ulteriori necessarie

Per completare **HTTP-06/criterion_01** occorre l'inventario trasversale dell'intero sottoinsieme di route core realmente distribuite, con prove positive/negative per oggetto, ruolo, lingua e stato. Per **HTTP-06/criterion_03** occorre associare ciascun caso limite core di `RELEASE_SCOPE_MATRIX.md` e dei cataloghi AC a un esito/versione/build verificato o a una motivazione di non applicabilità, senza assorbire i casi di fase 2. Il test di alcune route non è un criterio completo: le due righe restano rispettivamente `unverifiable` e `missing` nel registro canonico.

**Collaudo su sito Joomla:** verificare che le richieste negate non producano side effect nel database, stato iscrizione, progresso, code o sessioni; ripetere con anonimo, studente, docente e operatore secondo i permessi. Non includere URL del sito di prova o identificativi di persone nelle prove pubbliche.
