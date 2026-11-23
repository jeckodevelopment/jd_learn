# SECDEV-01 — confini MVC del pacchetto Joomla 1.0.0

La separazione fra controller, modelli, servizi e template è parte della qualità implementativa; **non è un collaudo manuale Joomla**.

## Evidenze software

- `component/administrator/services/provider.php` registra dispatcher Joomla, MVCFactory e servizi condivisi nel container.
- `tests/sec-p1-controller-gates.py` mantiene l'inventario dei 37 controller e blocca metodi/task pubblici nuovi senza revisione. I controller gestiscono HTTP, CSRF e delegano le transizioni ai servizi applicativi.
- `tests/secdev01-mvc-boundary.py`, obbligatorio in `tools/check.py`, verifica tutti i template PHP distribuiti nel componente e blocca query SQL, acquisizione di database, controlli ACL espliciti e scritture transazionali nel codice di presentazione. Include prove negative della stessa scansione.
- I dati/form appartengono ai model Joomla e la logica didattica a servizi del package; test CI di integrazione su Joomla disposable preservano i contratti implementati.

## Confini della verifica

Questa attestazione riguarda soltanto i sorgenti distribuiti della 1.0.0, non eventuali override del sito o plugin di terzi. Un controllo statico non certifica l'assenza di ogni comportamento scorretto; gli scenari di ruoli e visualizzazione sul sito reale saranno collaudati **in una fase distinta dall'utente**. Il gate SEC-P1 e la review dei nuovi endpoint restano obbligatori.

