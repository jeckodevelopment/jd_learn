# Cronologia dello sviluppo

Questo documento conserva il percorso passato. **Per decidere cosa funziona e cosa manca usare [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md)**; per le prossime attività usare [NEXT_STEPS.md](NEXT_STEPS.md).

| Versione / periodo | Risultato introdotto |
|---|---|
| Fino alla 0.19 | Base Joomla, catalogo/aula, video/materiali, quiz e gestione singola delle iscrizioni; [fotografia storica](history/IMPLEMENTATION_THROUGH_0.19.md) |
| 0.20–0.20.1 | Preassegnazione senza livelli; percorso esplicito di prima pubblicazione e diagnostica |
| 0.21–0.24 | Presentazione, copertina/SEO, docenti e calendario |
| 0.25–0.26 | ACL dell'editor e validazioni; successive correzioni video/pubblicazione |
| 0.27–0.28 | Player e correzioni YouTube/iframe; prima anteprima amministrativa |
| 0.29–0.30 | Descrizione estesa obbligatoria alla prima pubblicazione; Rules per corso |
| 0.31 | Salvataggio dei corsi pubblicati e storico Joomla; copia di lavoro completa; profili docente e campi nativi |
| 0.32 | Duplicazioni autonome di corso, modulo e lezione |
| 0.33 | Elenco/editor corsi secondo le convenzioni articoli Joomla |
| 0.34 | Programma contestuale, spostamento e riordino drag/tastiera |
| 0.34.1 | Protezione delle modifiche non salvate; [PR #43](https://github.com/jeckodevelopment/jd_learn/pull/43) integrata |
| 0.35 | Campi personalizzati nativi nell’editor corso; CI sulle PR senza duplicazione al merge |
| 0.36 | Riepilogo/conferma degli aggiornamenti didattici |
| 0.37 | Recupero del programma storico in nuova bozza, conservando altre bozze e dati studente; prove miste dei tre provider esterni |
| 0.38 | Editor delle regole supportate e soglia video configurabile per versione; confronto prima/dopo e soglie assegnate conservate |
| 0.39 | Durata automatica nell’editor, provenienza e ricevute legate alla sorgente; piccoli scarti tollerati con completamento sulla durata osservata; ripiego manuale e retry SDK Vimeo |

Il [changelog](../CHANGELOG.md) contiene i dettagli per versione. Il [registro dei requisiti](REQUIREMENTS_CHANGES.md) descrive le variazioni del risultato richiesto, non le implementazioni. Il [registro delle prove](IMPLEMENTATION_EVIDENCE.json) identifica il build corrente verificato e mantiene i record storici precedenti.

Le vecchie indicazioni “mancante”, “da verificare” o “prossimo incremento” nelle fotografie storiche descrivono soltanto il momento in cui furono scritte. Le versioni precedenti dei riepiloghi rimangono disponibili nella cronologia Git, senza copiarle nei documenti correnti.
