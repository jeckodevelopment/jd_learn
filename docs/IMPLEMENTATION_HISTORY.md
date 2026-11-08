# Cronologia dello sviluppo

Questo documento conserva il percorso passato. **Per decidere cosa funziona e cosa manca usare [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md)**; per le prossime attività usare [NEXT_STEPS.md](NEXT_STEPS.md).

| Versione / periodo | Risultato introdotto |
|---|---|
| 0.41.24 | Correzioni dashboard: link root aggiornato nel postflight, URL shortcuts non doppiamente codificati, traduzioni IT/EN ripulite; PR #110 e CI standard superata; ricollaudo Joomla AC-132 ancora aperto |
| 0.41.23 | Dashboard amministrativa come ingresso del componente e sei riquadri con icone/ACL per funzioni già esistenti; issue #105, PR #108 integrata, CI standard superata; collaudo Joomla reale ancora aperto |
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

| 0.40 | Menu amministrativo nativo e ingresso diretto nell’elenco corsi; stato provider su accesso esplicito |

| 0.40.1 | Plugin video apre com_plugins filtrato per gruppo, azzerando i filtri precedenti; sostituisce il collegamento fuorviante alla vecchia dashboard |

| 0.41.6 | Ritiro dal catalogo senza revoca dell’aula per iscritti autorizzati; PR #64 integrata, CI standard finale superata (run 42414492267), R-03 parziale e collaudo Joomla reale ancora aperto |
| 0.41.7 | Campi personalizzati nativi delle lezioni, baseline aggiornato per la revisione dell’impatto, snapshot in pubblicazione e valori nell’aula; PR #65 integrata nel commit [50925d2](https://github.com/jeckodevelopment/jd_learn/commit/50925d2f27ddcf9b23a18643747bbc047dae1ddd), CI standard superata ([run 42420998171]); collaudo Joomla reale aperto |
| 0.41.9 | Controlli distinti R-03 per nuove ammissioni ed erogazione, anteprima/motivo/storico, senza modificare iscrizioni o progressi; PR #70 integrata nel commit [70f6ed7](https://github.com/jeckodevelopment/jd_learn/commit/70f6ed7f2a1f4386b943bcb834f57e8d31cf935f); CI standard superata sul commit finale (run [42428496835](https://github.com/jeckodevelopment/jd_learn/actions/runs/42428496835)); collaudo Joomla reale aperto |
| 0.41.8 | Dashboard amministrativa con percorso corsi in evidenza, strumenti raggruppati, provider espandibili e foglio WAM responsive; PR #68 integrata nel commit [6e7789a](https://github.com/jeckodevelopment/jd_learn/commit/6e7789afff3aadce4353c5a17a072f945075c212), CI standard superata ([run 42423237440]); collaudo Joomla reale aperto |
| 0.41.6 | Ritiro catalogo senza revoca aula per gli iscritti autorizzati; PR #64 integrata, CI standard superata; R-03 parziale |
| 0.41.5 | Fallback Riprendi alla prima attività obbligatoria incompleta; CI standard superata e PR #62 integrata |
| 0.41.4 | Schede dell’editor corso raggruppate; CI standard superata e PR #60 integrata |
| 0.41.3 | Corretto il valore inviato dal checkbox di risposta corretta; regressione HTTP legge il markup Joomla effettivo |
| 0.41.1 | Normalizzazione dei checkbox non selezionati nel salvataggio delle opzioni della banca domande; verifiche dominio e HTTP aggiunte |
| 0.41 | Campi editoriali correnti nella scheda pubblica tramite plugin/layout Joomla, tre posizioni automatiche e controlli di accesso; storico didattico invariato |

Il [changelog](../CHANGELOG.md) contiene i dettagli per versione. Il [registro dei requisiti](REQUIREMENTS_CHANGES.md) descrive le variazioni del risultato richiesto, non le implementazioni. Il [registro delle prove](IMPLEMENTATION_EVIDENCE.json) identifica il build corrente verificato e mantiene i record storici precedenti.

Le vecchie indicazioni “mancante”, “da verificare” o “prossimo incremento” nelle fotografie storiche descrivono soltanto il momento in cui furono scritte. Le versioni precedenti dei riepiloghi rimangono disponibili nella cronologia Git, senza copiarle nei documenti correnti.
