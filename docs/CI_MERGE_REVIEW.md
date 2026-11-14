# Revisione CI e merge

Questo documento registra decisioni di merge rilevanti quando un esito CI non è verde. La regola operativa è semplice: **una PR non si fonde finché ogni controllo richiesto non è verde**. Un controllo fallito va diagnosticato e corretto; se il test è obsoleto, si aggiorna il test e si rilancia la pipeline completa prima del merge.

## PR #375 — collegamento ai Modelli email nella toolbar

La PR [#375](https://github.com/jeckodevelopment/jd_learn/pull/375) ha implementato l’issue #375: spostare il collegamento ai Modelli email dalla guida alle variabili alla toolbar, mantenendo filtro `com_jlms` e autorizzazioni `core.manage` e `core.edit` su `com_mails`.

Il run CI [43635371073](https://github.com/jeckodevelopment/jd_learn/actions/runs/43635371073) è terminato **con errore**. I job PHP 8.3/MySQL e PHP 8.4/MariaDB hanno fallito nel controllo `mail-template-variables.php`; entrambi si sono fermati sull’asserzione:

`Guide navigation and native editor ACL must be enforced.`

La causa era un’asserzione non aggiornata: dopo lo spostamento del collegamento, il controllo ACL si trovava nella vista che genera la toolbar, mentre il test lo cercava ancora nel template della pagina. Per questo il controllo automatico era diventato incoerente con il nuovo punto d’implementazione. Il fallimento non dimostrava una regressione del comportamento utente, ma restava un **gate CI fallito**.

La PR #375 è stata comunque fusa nel commit `2d0ffac98bf2dc6ce85dd90e8a9c6026084f5633`. Questo merge è stato prematuro: il test andava corretto e l’intera CI rilanciata prima dell’integrazione.

## PR #377 — correzione del test e modifica distinta alla vista Docenti

La PR [#377](https://github.com/jeckodevelopment/jd_learn/pull/377) ha implementato l’issue #377, indipendente: uniformare alla UI Joomla l’icona dello stato e il conteggio corsi nella lista Docenti. Ha inoltre aggiornato l’asserzione di `mail-template-variables.php` per verificare l’ACL nella vista della toolbar.

La CI della #377 è risultata verde: [run 43636264130](https://github.com/jeckodevelopment/jd_learn/actions/runs/43636264130), 4/4 job. La #377 non ha corretto una regressione funzionale introdotta dalla #375; ha corretto un test diventato obsoleto a seguito della #375 e ha consegnato una modifica UX separata.

## Azione di processo

Per le PR successive, compresa la #365, il merge avverrà solo dopo aver verificato l’esito conclusivo verde di tutti i job richiesti sulla revisione candidata. Un test fallito non sarà trattato come irrilevante finché la causa non sarà identificata, corretta e verificata con una nuova CI completa.
