# LCM-04 — Contratto delle dipendenze esterne dei docenti

La cancellazione fisica di un docente è consentita solo per un profilo cestinato, privo di account Joomla associato, assegnazioni attuali, riferimenti nelle versioni pubblicate e associazioni multilingua, e autorizzato con `core.delete`.

## Integrazioni con chiave esterna dichiarata

Durante la transazione di purga, JD Learn consulta `information_schema.KEY_COLUMN_USAGE` e controlla le righe di ogni tabella che dichiara una FK verso `#__jlms_teachers.id`. Un riferimento esistente blocca la cancellazione, incluso il caso `ON DELETE CASCADE`; errori di interrogazione bloccano la purga in modo conservativo. Le prove automatiche eseguono il caso con FK CASCADE su un Joomla temporaneo.

## Limite del contratto

I riferimenti opachi in JSON, testo, sistemi esterni o tabelle senza FK non sono automaticamente rilevabili. Le integrazioni devono dichiarare una chiave esterna oppure applicare un proprio veto operativo. Non si dichiara protezione universale delle estensioni terze. La verifica delle integrazioni installate e il collaudo manuale Joomla restano separati.

## Chiusura implementativa LCM-04

Il criterio `external_references` è risolto **nel perimetro di integrazione formalmente dichiarato**: FK SQL verso il profilo docente, verifica transazionale e test di regressione su Joomla effimero. La verifica delle integrazioni installate e dei riferimenti privi di FK non è implicitamente attestata. Il requisito è chiuso dal punto di vista dell'implementazione core, senza assimilare test automatici ad accettazione Joomla.

## Collaudo

Verificare la purga negata con profilo ancora collegato a un utente, una versione didattica, un'associazione multilingua o una FK terza; verificare che siano conservati record storici e account. Dopo la rimozione delle dipendenze, verificare la purga del solo profilo inutilizzato e cestinato, con ACL e CSRF validi.
Nota: l'esito del controllo automatico su una build non costituisce accettazione manuale delle integrazioni installate.
