# MAIL-G — equità batch avvisi ai responsabili

## Difetto corretto

Il task Scheduler selezionava richieste di iscrizione `pending` entro la finestra temporale e applicava il limite prima di ridiscovere quali richieste avessero già generato avvisi. La chiave evento impediva duplicati nella coda, ma le richieste più vecchie restavano comunque candidate e potevano occupare ogni batch, ritardando le successive.

La query ora esclude, prima del limite, le richieste per cui è già presente nella coda un evento `com_jlms.enrollment.manager_request` con la chiave deterministica della coppia richiesta/responsabile. La logica di autorizzazione per responsabile, la preferenza per corso, la finestra di due giorni e la validazione al dispatch non cambiano.

Il controllo di regressione `tests/secdev02-core19-mail-batch-bound.py` richiede che il filtro sia presente prima del `LIMIT` e include fixture negative per l'assenza del filtro, il discriminatore evento, la chiave hash e il limite.

## Verifica e confini

- CI Joomla e gate CSV da eseguire sulla PR.
- La matrice del requisito NOT-01 e il criterio NOT-01/delivery non vengono promossi: la consegna SMTP e la copertura end-to-end completa restano da accettare.
- Collaudo Joomla manuale: **In attesa**.

## Collaudo manuale

Con più di un batch di richieste `pending` e responsabili autorizzati, avviare il task due volte. Verificare che ogni responsabile riceva al massimo un evento per richiesta e che le richieste successive al primo batch vengano accodate nei run successivi. Ripetere con preferenza disabilitata, responsabile rimosso/bloccato e richiesta approvata prima dell'invio: nessuna email non autorizzata deve essere consegnata.
