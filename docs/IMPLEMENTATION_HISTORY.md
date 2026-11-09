# Cronologia dello sviluppo

Questo documento conserva il percorso passato. **Per decidere cosa funziona e cosa manca usare [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md)**; per le prossime attività usare [NEXT_STEPS.md](NEXT_STEPS.md).

| Versione / periodo | Risultato introdotto |
|---|---|
| 0.41.38 | PR #143 integrata nel commit `7681473d34e23ec633fcf9fc59c3711e644b2d98`: rilascio relativo lezioni, protezione pre-rilascio, requisito lezione iniziale e controllo scadenze note durante ammissione; CI standard 4/4 (run 42652681260); pacchetto verificato; collaudo Joomla pendente |
| 0.41.37 | PR #141 integrata: finestre assolute di disponibilità delle lezioni nel fuso del corso, validazione di raggiungibilità e protezione aula/endpoint; CI standard 4/4 superata (run 42646076977); pacchetto verificato; collaudo Joomla pendente; rilascio relativo a giorni dall’iscrizione ancora residuo |
| 0.41.36 | Candidato: modalità sequenziale per legare le attività obbligatorie nell’ordine del programma; CI e collaudo Joomla da completare |
| 0.41.35 | PR #136 integrata: prerequisiti espliciti delle lezioni, controllo cicli, blocco protetto e collegamento nell’aula; CI 4/4 superata, collaudo Joomla pendente |
| 0.41.34 | Ripresa protetta della posizione audio, senza completamento automatico; PR #134 integrata, CI standard 4/4 superata nel run 42613814879, collaudo manuale Joomla pendente |
| 0.41.33 | Lezioni Audio MP3/OGG con player HTML5, streaming privato e completamento manuale; PR #132 integrata, CI standard superata nel run 42609983073, collaudo Joomla manuale pendente |
| 0.41.31 | Filtro ACL delle voci sidebar native via plugin di sistema; PR #128 integrata, CI standard superata nel run 42594246627, collaudo Joomla manuale pendente |
| 0.41.30 | ACL feature-specific nelle scorciatoie dashboard (issue #113, PR #126); CI standard superata; filtraggio del sottomenu nativo e collaudo Joomla ancora aperti |
| 0.41.29 | Corrette le traduzioni IT/EN della pagina Opzioni: titolo browser e gruppo Cronologia versioni; issue #115 / AC-134, PR #124 integrata, quattro job CI superati; collaudo Joomla manuale aperto |
| 0.41.28 | Conteggio corsi per docente, link alla lista Corsi filtrata, filtro Docente e intestazione tradotta; issue #117 / AC-133, PR #122 integrata, CI standard superata; collaudo Joomla AC-133 aperto |
| 0.41.27 | Banca domande con elenco Search Tools e form separato, duplicazione e cancellazione protetta; PR #120 integrata, quattro job CI superati; collaudo Joomla AC-131 aperto |
| 0.41.26 | Lista backend docenti secondo Search Tools Joomla, con ricerca/filtri/ordinamento/paginazione; PR #116 integrata, quattro job CI superati; collaudo Joomla AC-130 aperto |
| 0.41.25 | Dashboard come prima voce figlia della sidebar, collegata alla vista già esistente; PR #112 integrata, quattro job CI superati; ricollaudo Joomla AC-132 ancora aperto |
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
