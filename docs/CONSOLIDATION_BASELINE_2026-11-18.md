# Consolidamento unico documentale 1.0.0 — baseline congelata

**Data di ricognizione:** 18 novembre 2026. **Commit di riferimento main:** `5c89fdb4077578f7664efcbc511a24d01a5b680e` (PR #616). Questa fotografia non si aggiorna implicitamente con PR successive: ogni aggiornamento richiede una nuova verifica.

## Riscontri documentali e limiti

- La PR #578 (LCM-04 docenti) risulta integrata; il registro post-merge cita anche #574–#577. Le PR documentali #595, #602, #603, #604, #605 e #616 sono parte del percorso di allineamento; #589 è stata chiusa senza merge e non va attribuita a main.
- `docs/IMPLEMENTATION_STATUS.md` e `CHANGELOG.md` descrivono la versione **0.60.1-dev** e il blocco di ammissioni/fruizione al ripristino di un corso archiviato; non è la versione storica **0.57.7-dev** registrata nelle evidenze precedenti. Le versioni di sviluppo non attestano un rilascio stabile 1.0.0.
- `docs/IMPLEMENTATION_EVIDENCE.json` mantiene la stima storica di **79,56%**, arrotondata a **80%**, con data 16/11/2026. Non è una percentuale funzionale corrente verificata: la percentuale globale certificabile resta **non determinata**.
- Audit dei criteri riportato nei registri: **129/214 requisiti** scomposti, **85** non ancora scomposti; **359 criteri**, **203** con riferimenti iniziali di evidenza, **156** ancora non valutati. Una prima evidenza non equivale a requisito soddisfatto, test passato o accettazione.
- LCM-02 ha ricevuto incrementi sui corsi archiviati/cestinati e sulle protezioni, ma resta **parziale** fino a completamento delle verifiche; non confondere il completamento di singole PR con l'accettazione dell'intero requisito.
- `docs/STABLE_1.0.0_PR_PLAN.md`, `docs/REQUIREMENTS_STATUS.csv`, `docs/IMPLEMENTATION_STATUS.md`, `docs/IMPLEMENTATION_EVIDENCE.json`, `docs/QA_TESTBOOK.md`, `CHANGELOG.md` e `docs/IMPLEMENTATION_HISTORY.md` sono registri con granularità e cronologie differenti. Le annotazioni datate e le percentuali precedenti sono **storiche**, non necessariamente lo stato corrente.
- I test automatici documentati nelle PR non sostituiscono collaudi Joomla reali, prove SMTP, audit ACL/CSRF, regressioni video e verifica degli artefatti di rilascio. Non segnare questi collaudi come eseguiti senza verbale e ambiente identificabile in modo anonimizzato.
- Privacy: usare soltanto `installazione di prova`, `ambiente Joomla di collaudo` o riferimenti generici nella documentazione pubblica. Le precedenti correzioni non certificano una scansione esaustiva di tutti i file, cronologia Git e binari.

## Gate di chiusura della riconciliazione

1. Confrontare ogni riga della matrice requisiti con codice, PR integrate, test e criteri del registro, marcando **implementato/parziale/non implementato** e **collaudo pendente/eseguito** separatamente.
2. Correggere ogni contraddizione corrente nei registri mantenendo le note storiche chiaramente datate.
3. Verificare CI e pacchetto installabile rispetto a un commit esatto; registrare solo risultati osservati.
4. Completare la scansione privacy su albero corrente, cronologia e artefatti pubblicati; distinguere riferimenti bonificati da riferimenti storici non riscrivibili senza interventi separati.
5. Solo dopo questi gate, calcolare un avanzamento funzionale con metodo esplicito e tracciabile; altrimenti mantenere `null`.
6. Chiudere l'attività di consolidamento solo quando tutti i controlli documentali sono tracciati; i collaudi manuali non eseguiti possono restare pendenti se dichiarati chiaramente.

**Stato di questa fotografia:** ricognizione parziale verificabile; **non** attestazione di riconciliazione esaustiva o collaudo funzionale.
