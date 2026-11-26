# CORE-10 — ripristino Content History dei campi docente

## Intervento del 26 novembre 2026

La verifica CORE-10 copriva il ripristino dei campi personalizzati dei corsi, ma non il profilo docente. Il test Joomla disposable `tests/joomla-editorial.php` ora crea un profilo con campo nativo `com_fields`, individua la revisione Content History che contiene il valore iniziale, modifica il valore e richiama `TeacherModel::loadHistory()`. La regressione controlla che il valore storico venga ripristinato tramite il salvataggio autorizzato del modello.

La prova si aggiunge ai controlli esistenti su editing docente, assegnazione profilo, ACL del campo e separazione fra modifica consentita e proprietà protette. Non amplia i privilegi e non modifica le versioni didattiche.

## Stato del requisito

CORE-10 resta **Parziale** e il criterio esteso `teacher_scoping` resta `unverifiable`: il nuovo test copre il ripristino di un campo docente in Joomla CI, non tutte le combinazioni di ACL, lingua, gruppi, tipi di campo e contesti corso/lezione/docente. Non viene attribuito alcun collaudo manuale né incremento percentuale sulla sola aggiunta della regressione.

**Collaudo Joomla manuale: NON ESEGUITO.** Restano le combinazioni previste dal testbook, inclusi lingua, gruppi campo, permessi record e verifica del profilo pubblico.
