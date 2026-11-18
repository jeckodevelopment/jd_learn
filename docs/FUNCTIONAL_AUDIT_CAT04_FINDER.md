# Riconciliazione puntuale CAT-04 — Smart Search

Data: 18 novembre 2026. Perimetro: solo criterio `CAT-04/finder` della release 1.0.0.

## Evidenza letta

`plugins/finder/jlms/src/Extension/Jlms.php` registra l'adapter Joomla Finder, indicizza esclusivamente corsi con `state=1`, `catalog_access=1`, versione editoriale pubblicata, categoria e antenati pubblici (accesso 1), e non indicizza corsi con finestre di pubblicazione programmate. L'indice forza `access=1`. Sono gestiti eventi di salvataggio/cancellazione/cambio stato e reindicizzazione categorie.

Questi controlli confermano un'**indicizzazione intenzionalmente pubblica**, sufficiente per classificare il criterio `finder` come implementato a livello di codice. La verifica del solo adattatore non prova che tutti gli endpoint di indicizzazione, i plugin di terze parti o le combinazioni SEO non possano esporre dati; pertanto `CAT-04/robots_security` e `CAT-04/seo_combinations` restano `unassessed`.

## Accettazione non attribuita

Da collaudare in Joomla reale: Smart Search attivato, categorie annidate, accessi diversi da Public, cambi di stato, lingue, corso temporizzato, eliminazione, disabilitazione plugin; verifica degli URL e dell'assenza di dati personali/protetti nell'indice. Un controllo statico del codice non equivale a CI verde o collaudo manuale.

Non incrementare SemVer né riutilizzare il 79,56% come percentuale attuale. La percentuale globale resta non calcolabile fino all'analisi degli altri criteri.
