# SECDEV-02 — Core Joomla e PHP: verifiche e limiti

## Perimetro e criteri

La specifica richiede (01) CORE-01–23, PSR-12, namespace e contratti; (02) runtime supportati e API verificate per la patch target; (03) nessuna modifica al core né dipendenza intenzionale da API rimosse; (04) motivazione dei servizi core non pertinenti e assenza di duplicazioni.

## Evidenza automatica di questa PR

- `tests/secdev02-core-php.py` verifica il requisito dichiarato PHP >=8.3, estensione JSON, PSR-4 e presenza delle relative directory.
- Blocca la presenza di alcuni percorsi riconoscibili come core Joomla incorporato nel repository JD Learn. La verifica **non** dimostra l'assenza di tutti i possibili fork o copie del core.
- Verifica l'esistenza in CI della matrice PHP containerizzata, del controllo patch-floor PHP, del lint PHP e del proprio gate.
- Include fixture negative per downgrade del requisito PHP, copia di un percorso core e rimozione del controllo CI.
- La CI esegue i controlli automaticamente. `composer.json` e `.github/workflows/ci.yml` sono i riferimenti del contratto dichiarato.

## Da completare prima della chiusura SECDEV-02

1. Riconciliare singolarmente **CORE-01–23** con un registro di evidenze, test e motivazioni di non applicabilità; non equiparare una integrazione parziale alla copertura totale.
2. Eseguire verifica di conformità PSR-12 sull'intero PHP e verificare firme, tipizzazione e contratti; il semplice lint sintattico non garantisce lo stile.
3. Controllare le API Joomla effettivamente utilizzate contro la versione/patch target e cercare chiamate deprecate o rimosse: il gate statico non effettua questo audit.
4. Verificare in ambiente Joomla la matrice realmente supportata, i gestori core e i casi autorizzativi; i collaudi manuali rimangono separati.

**Stato:** SECDEV-02 non è dichiarato chiuso. I quattro criteri funzionali restano `unverifiable` fino a evidenza completa; questa PR implementa una barriera CI parziale senza alterare le percentuali o anticipare l'accettazione Joomla.

## Comportamenti da testare

- `python3 tests/secdev02-core-php.py --self-test` e `python3 tests/secdev02-core-php.py` terminano con codice 0.
- Alterare il requisito PHP, togliere un mapping PSR-4, introdurre un percorso core vietato o rimuovere una fase CI produce codice diverso da zero.
- Le normali installazioni Joomla, il pacchetto JD Learn, il comportamento frontend/backend, i dati e i cookie restano invariati.
