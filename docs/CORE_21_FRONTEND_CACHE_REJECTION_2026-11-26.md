# CORE-21 — cache delle route frontend rifiutate (26 novembre 2026)

## Difetto e intervento

In `DisplayController::display()` la validazione della vista poteva sollevare
un'eccezione 404 prima di impostare `Cache-Control: private, no-store`.
Il controller ora imposta l'header prima di leggere e validare la vista:
anche le risposte rifiutate sono coperte dal contratto applicativo.

Il gate esistente `tests/secdev02-core21-private-dispatch.py` verifica
ora anche l'ordine tra impostazione dell'header e parsing della vista.
Rimangono in vigore la lista di viste ammesse, il divieto di cache sul
rendering Joomla e `X-Robots-Tag` per le viste riservate.

## Limiti e accettazione

Questa è una correzione implementativa di CORE-21/private_cache, non
un'attestazione di chiusura dell'intero criterio. Le prove del comportamento
di proxy/CDN, reverse proxy e sessioni differenti richiedono ancora
un collaudo sul sito Joomla 6.2.1 con account guest e autenticato.

1. Richiedere `index.php?option=com_jlms&view=inesistente`; verificare
   HTTP 404 e `Cache-Control: private, no-store`.
2. Aprire catalogo e dashboard autorizzata; verificare rendering e header.
3. Ripetere via proxy in due sessioni: nessun contenuto privato condiviso.

L'esito del collaudo è **NON ESEGUITO**. CORE-21 e SECDEV-02 restano
**Parziale** nel registro canonico; nessuna percentuale viene promossa
senza evidenze nuove e riconciliazione complessiva.
