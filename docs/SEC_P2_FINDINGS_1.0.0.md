# SEC-P2 — Riesame finding per la candidata software 0.64.18-dev

**Data:** 21/11/2026. **Ambito:** codice e package JD Learn distribuiti, non l'intero hosting Joomla. **Stato:** review implementativa completata; nessun finding critical/high **confermato e non mitigato nel runtime JD Learn corrente** tra quelli censiti. Gate di release/accettazione esterno ancora aperto.

## Fonti di evidenza

Il registro leggibile da macchina e validato in CI è `docs/SEC_P2_FINDINGS_1.0.0.json`; i riferimenti sono a file di codice e test in repository. `tests/sec-p2-findings-gate.py` esegue una verifica conservativa dei marker di mitigazione, dei test presenti, delle severità e dell'inventario. Il gate rifiuta finding critical/high marcati `unresolved` nel codice distribuito e rifiuta la promozione a `mitigated` dei rischi relativi a installazioni esterne non verificati. Fixture negative confermano queste proprietà.

| ID | Ambito | Giudizio | Evidenza / prossimo passo |
| --- | --- | --- | --- |
| MAILQUEUE-GLOBAL-ACL | JD Learn corrente | **Alta, mitigata** | `MailQueueAuditService` richiede sia `core.manage` sia `jlms.audit.view`; test su fixture Joomla `joomla-roster.php` |
| VIDEO-ROOT | JD Learn corrente | **Alta, mitigata** | `ProtectedVideoStorage` fallisce se la root non è impostata o non è privata; `joomla-video.php` copre root assente/pubblica/esterna |
| DISTRIBUTABLE-ARCHIVE | Pacchetto | **Alta, mitigata** | `tools/build.py` rifiuta archivi malevoli e dipendenze non censite prima di scrivere lo ZIP; negative fixtures |
| HISTORICAL-SECRET-SIGNATURES | Sorgenti/workflow | **Alta, mitigata per firme note** | Storia Git raggiungibile e policy actions; 3211 blob esaminati in PR #844, zero firme note |
| PHP-PATCH-FLOOR-20261119 | Runner CI | **Alta, mitigata nei runner** | `tests/sec-p2-php-security-floor.php` prima delle fixture Joomla; PHP 8.3.37 / 8.4.28 |
| VIDEO-LEGACY-STORAGE | Hosting preesistente | **Alta potenziale, non accertata sul sito; verifica obbligatoria** | Eseguire `tools/audit_legacy_video_storage.py` sul webroot di prova. Il runtime non può impedire richieste statiche a file legacy esistenti; bloccare la release se presenti fino a messa in sicurezza |
| JOOMLA-CORE-DEPENDENCIES-NOV2026 | Core Joomla dell'hosting | **Severità/versione da verificare; non dichiarata mitigata** | Aggiornamento upstream di 5 dipendenze e 6 advisory sul ramo Joomla 6.2-dev del 13/11, successivo alla fixture 6.2.0. Verificare patch del sito e advisory reali prima del go-live |

## Advisory e runtime verificati il 21 novembre 2026

- PHP: [archivio ufficiale 2026](https://www.php.net/archive/2026.php), comunicazioni di sicurezza per 8.3.37 e 8.4.28 del 19/11. Gate di patch floor già presente in CI; il PHP dell'hosting resta da verificare.
- Joomla: [Security Centre](https://developer.joomla.org/security-centre) riporta le versioni vulnerabili e corrette delle issue del core. Il package deve essere installato solo su core patchato. La fixture di CI usa Joomla 6.2.0, ma **non** può attestare l'inclusione di fix a dipendenze pubblicati dopo ottobre.
- Joomla [issue #48846, 13/11/2026](https://issues.joomla.org/tracker/joomla-cms/48846): sei advisory delle dipendenze del core `phpseclib` e Symfony, aggiornate nel ramo `6.2-dev`; non si assume che la vecchia fixture Joomla 6.2.0 contenga tali modifiche. C'è anche un advisory di livello medio su WebAuthn ignorato nel relativo audit upstream. Questa informazione riguarda il **core Joomla**, non dipendenze vendorizzate da JD Learn.

## Decisione, confini e rilascio

L'esecuzione riuscita di `tests/sec-p2-findings-gate.py`, `tools/check.py` e delle matrici Joomla documenta la conclusione del **riesame dei finding del codice distribuito**. Non è prova di sicurezza dell'installazione effettiva, assenza di vulnerabilità sconosciute o di un penetration test. In particolare **il finding VIDEO-LEGACY-STORAGE non è accettato né archiviato**: l'eventuale rischio diretto tramite webserver richiede verifica e remediation sull'hosting; il gate di pubblicazione non può ignorarlo.

La promozione canonica del criterio generale SEC-P2/criterion_02 («nessun finding critico/alto irrisolto») richiede che tutti i finding critici/alti pertinenti alla candidata e al sito siano risolti o valutati prima della release. Non segnare una falsa assenza di finding soltanto perché il nuovo registro supera la CI. L'implementazione automatizzabile della review è completa; i test e le azioni esterne appartengono alla **accettazione di rilascio**.

**Comportamenti da testare manualmente:** verificare header/CSP del template, log reverse proxy/PHP/SMTP, versioni effettive Joomla/PHP e conteggio dei file legacy; in caso di file legacy, impedire l'accesso diretto GET/HEAD/Range e migrare/bonificare prima del go-live, senza cancellazioni automatiche. Nessun dato personale deve essere pubblicato nelle evidenze.
