# Stato effettivo della riconciliazione funzionale 1.0.0 — 18 novembre 2026

Questa fotografia descrive **la copertura dell'audit**, non l'avanzamento funzionale globale. Fonte: `docs/FUNCTIONAL_CRITERIA_1.0.0.json` su `main` al 18 novembre 2026. Ricalcolare i dati a ogni aggiornamento del registro.

- Requisiti in perimetro e scomposti: **214/214**.
- Criteri complessivi: **757**.
- Criteri marcati `implemented` con almeno un riferimento di evidenza: **238**. Il riferimento non equivale a collaudo manuale.
- Criteri marcati `unassessed`: **519**. Non interpretarli come `missing`.
- Requisiti con tutti i criteri classificati: **7/214**.
- **Percentuale funzionale globale: non ancora determinabile** secondo `tools/functional_progress.py`; la stima storica 79,56% non va presentata come corrente.

## Distribuzione dei criteri per famiglia

| Famiglia | Requisiti | Implemented | Unassessed |
|---|---:|---:|---:|
| EDGE | 11 | 0 | 87 |
| SCR | 12 | 10 | 49 |
| DOC | 10 | 0 | 36 |
| SECDEV | 8 | 1 | 35 |
| CORE | 23 | 23 | 24 |
| HTTP | 6 | 0 | 22 |
| DAT | 4 | 0 | 19 |
| REL | 4 | 0 | 19 |
| PVD | 6 | 10 | 18 |
| PERF | 11 | 8 | 16 |
| PBASE | 4 | 3 | 15 |
| SEC | 5 | 0 | 14 |
| LIFE | 4 | 2 | 13 |
| MAIL | 11 | 12 | 12 |
| ACL | 4 | 3 | 12 |
| STA | 3 | 0 | 12 |
| CERT | 8 | 8 | 11 |
| VID | 8 | 18 | 10 |
| MAT | 8 | 19 | 9 |
| LCM | 5 | 17 | 8 |
| ISC | 7 | 14 | 8 |
| CAT | 4 | 12 | 7 |
| UI | 7 | 7 | 7 |
| STU | 5 | 9 | 6 |
| PRI | 3 | 3 | 6 |
| CFG | 6 | 6 | 6 |
| PRO | 6 | 12 | 5 |
| LES | 4 | 7 | 5 |
| JOO | 5 | 6 | 5 |
| QUI | 1 | 0 | 5 |
| AUT | 5 | 19 | 4 |
| NOT | 2 | 4 | 4 |
| REP | 1 | 0 | 4 |
| SIC | 2 | 4 | 3 |
| ACC | 1 | 1 | 3 |

## Regole di chiusura

Ogni criterio `unassessed` deve ricevere esame puntuale del comportamento richiesto e del codice/test; poi `implemented` con prove pertinenti o `missing` se la lacuna è accertata. Mantenere separati implementazione, CI e accettazione manuale Joomla. La misurazione globale si attiva soltanto quando tutti i criteri risultano classificati. Una PR documentale di ricognizione non incrementa la versione software e non attribuisce nuove funzionalità.
