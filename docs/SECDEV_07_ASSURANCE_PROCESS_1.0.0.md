# SECDEV-07 — Processo di assurance e tracciabilità dei controlli di sicurezza

**Baseline applicativa:** JD Learn 0.64.19-dev, requisiti 1.0.0. Questa è una procedura verificabile per il **software distribuito**, non una certificazione OWASP, una scansione completa di sicurezza o una verifica del sito Joomla di produzione.

## Metodo obbligatorio

Ogni controllo è tracciato in `docs/SECDEV_07_SECURITY_ASSURANCE_1.0.0.json` con identificativo stabile, superficie/minaccia, riferimento documentale, test eseguibile, contesto di esecuzione e limite della sua evidenza. `tests/secdev07-assurance-gate.py` viene eseguito da `tools/check.py` nel preflight CI e fallisce quando un controllo è rimosso, non è collegato a un test presente, il test non è più chiamato da CI, oppure quando una lacuna di sicurezza viene cancellata senza decisione. Le fixture negative coprono queste regressioni.

Il registro include il threat model SEC-P1, controller e plugin, rilevamento statico di sink di esecuzione e I/O, firme di segreti e action non fissate, dipendenze di pacchetto, finding critici/alti, inventario privacy, flussi reali Joomla e pin di OWASP ASVS 5.0.0. La CI Joomla su PHP 8.3/MySQL e PHP 8.4/MariaDB è distinta dai controlli sorgente; l'esecuzione deve essere confermata sullo SHA preciso della PR. Ogni nuovo controller, plugin, hook privilegiato, dipendenza o surface richiede aggiornamento del modello minacce e del test, non un semplice incremento numerico.

## Limiti espressi e rischio residuo

Il registro mantiene quattro attività **non automaticamente risolte**:
1. Verifica SCA delle dipendenze della piattaforma Joomla/PHP/database e dei servizi esterni nella candidata finale, con advisory e patch level.
2. Revisione dei segreti di hosting, configurazioni, log e rotazioni senza pubblicare credenziali.
3. Test dinamici/penetration review e decisione di rilascio SEC-P5 sul deploy definitivo.
4. Analisi statica e stile completi, oltre ai controlli mirati attualmente presenti, con strumento/versione e risultati riproducibili.

**Regola:** nessun esito negativo, false positive o non applicabilità può essere ignorato. La relativa decisione deve registrare ID della superficie, versione/build, motivazione, prova riproducibile, revisore e data. Un finding high/critical confermato nel codice distribuito impedisce la candidate release tramite il gate SEC-P2. I finding della piattaforma/hosting rimangono blocchi di rilascio finché non sono valutati e risolti; il CI del solo componente non può certificarli.

## Esecuzione, accettazione e matrice

- Il team software può chiudere il **criterio SECDEV-07/criterion_01 sul processo verificabile** solo dopo PR integrata, preflight verde e matrice con prove riferite al commit. Il criterio 02 richiede anche strumenti di stile/SAST/SCA e relativa evidenza; non promuoverlo per il solo registro.
- I criteri 04/05 necessitano un report completo di riesame dei finding e delle non applicabilità sulla candidata; non sono promossi da questa PR.
- Il titolare del sito e chi effettua collaudi mantengono un registro **separato** dell'accettazione Joomla, con ruoli diversi, prove sulla candidata finale, log e backup, dipendenze e attacchi negativi, senza riportare dati personali o credenziali.

Per ogni nuova PR: **Comportamenti da testare**, minacce interessate, controlli e fixture, effetto su privacy/cookie, eventuali finding, risultato della CI e registro dei criteri. Dopo il merge registrare SHA, CI, ZIP installabile e checksum nel registro storico senza confondere ZIP Joomla e archivio Actions.

## English — maintainer summary

The SECDEV-07 assurance ledger links each release security claim to a reviewed threat, a source document, a mandatory CI check and a clear limitation. Missing tests, unregistered evidence or hidden residual risks break the preflight. Targeted static checks and Joomla integration fixtures are not full SAST, SCA or penetration testing. The site controller must separately verify deployed Joomla/PHP versions, hosting secrets, backup and third-party services before a stable release is approved. Manual acceptance remains independent of automated development coverage.
