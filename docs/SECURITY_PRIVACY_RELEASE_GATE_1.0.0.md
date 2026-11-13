# JD Learn — Security, privacy e cookie: release gate 1.0.0

**Approvato come piano, non come certificazione o audit eseguito.** Seguire [progettazione tecnica](specifications/Progettazione_tecnica_esecutiva_LMS_1.0.md), [policy sicurezza](../SECURITY.md) e [inventario cookie](PRIVACY_COOKIES.md). Tutte le verifiche SEC-P1–SEC-P5 sono necessarie prima del rilascio stabile; non sostituiscono l'accettazione Joomla e non aumentano la copertura stimata al solo atto di pianificazione.

| Fase | Priorità | Contenuto | Evidenze/gate |
|---|---|---|---|
| SEC-P1 | P0 | Threat model e code review sistematica su endpoint, ACL/IDOR, CSRF, XSS/SQLi, SSRF, upload/traversal, file privati, quiz, token/replay, segreti, transazioni, code; confronto con prassi Joomla e OWASP ASVS | Matrice minaccia → controllo → test, findings classificati |
| SEC-P2 | P0 | Hardening runtime e supply chain: dipendenze, secret scan, configurazione sicura, logging, dati sensibili, build/package, CSP e webhook provider | Nessun critical/high pertinente aperto senza decisione esplicita e motivata |
| SEC-P3 | P0 | Privacy by design: mappatura trattamenti, export/cancellazione via Joomla com_privacy, retention per progressi/attestati/email, log e backup, diritti dell'interessato | Flussi manuali ed evidenze con account e dati di prova |
| SEC-P4 | P0 | Inventario cookie e storage browser, integrazione CMP e video, documentazione pubblicabile IT/EN per amministratori | Scansioni desktop/mobile per Joomla, JD Learn e provider; guida configurazione CMP; vedi [PRIVACY_COOKIES.md](PRIVACY_COOKIES.md) |
| SEC-P5 | P0 | Test negativi E2E e penetration test/risk review della candidate release, riesame vulnerabilità, patch e incident process | Rapporto finale, tracciabilità issue, gate go/no-go documentato |

## Regole di esecuzione

- Eseguire audit **su codice e candidata finale effettivi**, non soltanto su specifiche; CI verde, controlli statici e mock non dimostrano sicurezza in produzione.
- Non inviare agli strumenti esterni credenziali, dati degli studenti o segreti. Registrare evidenze minimizzate e prive di dati personali.
- Verificare su hosting standard Joomla Apache/LiteSpeed con sessioni/cookie protetti, upgrade da versione precedente, minimo runtime supportato e versione corrente.
- [SECURITY.md](../SECURITY.md) è stato aggiornato nella PR di sicurezza 1.0.0: non dichiara supportate release non ancora pubblicate, descrive il canale di segnalazione privata e il processo di risposta. Questo aggiornamento di policy non completa SEC-P1: la review sistematica del codice, la matrice minaccia-controllo-test e la classificazione dei findings restano da svolgere.
- Non dichiarare certificazione ASVS/GDPR o assenza di cookie fino all'ottenimento delle evidenze pertinenti; classificazione legale e privacy policy finale spettano al titolare del sito.
- Includere per ogni PR implementativa una sezione **Impatto sicurezza/privacy/cookie** con test di regressione; conservare collegamento tra PR, requisito e testbook.

Questo piano è separato dalle funzionalità future Credentials/Open Badges; firma digitale, custodia/rotazione chiavi, revoca e verifiche di interoperabilità richiederanno una review dedicata quando implementate.


## PRIV-C — Registro e diritto di accesso ai consensi applicativi

La verifica di SEC-P3 include obbligatoriamente **PRIV-C1–C5** in [JOOMLA_PRIVACY_CONSENT_INTEGRATION.md](JOOMLA_PRIVACY_CONSENT_INTEGRATION.md): verificare l'interfaccia Joomla 6 per l'eventuale visualizzazione del consenso MAIL-11 nel registro centrale; garantire comunque una consultazione autorizzata senza duplicare fonti di verità, completare export/rimozione/anomizzazione dei dati correlati e documentare la procedura per gli amministratori IT/EN. Il plugin Privacy JD Learn espone già capabilities, export e un percorso di removal, ma la copertura dei token di revoca, code e audit va provata. Il registro Joomla non aggrega automaticamente consensi conservati in tabelle di componenti terzi. **Non scrivere direttamente nel registro interno Joomla senza API documentata e testata.** Nessuna nuova copertura attribuita finché le PR implementative e i test non sono integrati.
