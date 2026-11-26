# CORE-17–23 — riconciliazione e hardening della coda mail (26/11/2026)

## Esito ricognizione

- CORE-17: già 2/2 nella matrice tecnica; collaudo amministrativo distinto.
- CORE-18: template e invii tramite infrastruttura Joomla; prova SMTP reale separata.
- CORE-19: trovato un difetto concreto di concorrenza nelle transizioni `skipped`.
- CORE-20: toolbar, filtri e operazioni native già implementati per il perimetro coperto, accettazione completa ancora pendente.
- CORE-21: header no-store e prove sulle risposte riservate già implementati; configurazioni di caching reali da verificare.
- CORE-22: aggiornamento, artefatti, update server e prove installazione CI presenti; upgrade su hosting esterni da collaudare.
- CORE-23: contratti/eventi nativi presenti; integrazione con plugin terzi da verificare.

## Correzione CORE-19

`MailQueue::dispatch()` identifica e acquisisce una riga con una transizione atomica verso `sending`. Sei percorsi di esclusione per eleggibilità, consenso o variazioni di contesto usavano tuttavia un `UPDATE` verso `skipped` senza rivalidare lo stato corrente. Ora **tutti e sette** i percorsi richiedono `state = 'sending'` per non sovrascrivere una transizione concorrente del record. Rimangono invariati SMTP, retentativi, deduplicazione e limiti dei batch.

La regressione `tests/secdev02-core19-mail-batch-bound.py` verifica il controllo per tutte le transizioni e include una fixture negativa che elimina il controllo da un ramo.

## Verifiche separate

Nessuno dei criteri CORE-17–23 viene promosso a completato sulla sola modifica. `CORE-19` rimane **Parziale** fino alla prova di dispatch/retry concorrenti in un'installazione reale. Collaudo manuale **NON ESEGUITO**: simulare revoche account/consenso e cambi di stato della coda durante la consegna, verificando assenza di duplicati, perdita di notifica, disclosure e alterazioni dello stato. Le evidenze su SMTP, cache reverse proxy, hosting e plugin restano separate nel testbook.

Nota operativa: il controllo di stato riguarda l'UPDATE della riga, non garantisce da solo l'assenza di un invio SMTP dopo una revoca concorrente; questa condizione richiede le prove end-to-end previste nel testbook.
