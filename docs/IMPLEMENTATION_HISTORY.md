## 0.41.53-dev — pulizia delle sessioni video scadute (IMP-05)

La nuova sessione video autorizzata rimuove fino a 250 sessioni scadute, ordinate per scadenza; il limite evita una cancellazione non circoscritta nella richiesta. Il comportamento è coperto dalla suite d’integrazione Joomla. La pulizia periodica resta utile per siti inattivi; collaudo manuale Joomla pendente.

## 0.41.52-dev — retry della posizione audio (PR #196)

La PR [#196](https://github.com/jeckodevelopment/jd_learn/pull/196) aggiunge un retry massimo di una volta per errori transitori durante il salvataggio della posizione audio; mantiene lo stesso body, evita loop sugli errori permanenti e conserva il punto non salvato fino al prossimo evento. Nessun completamento o credito di progresso viene attribuito. CI standard 4/4 superata nel [run 42876421523](https://github.com/jeckodevelopment/jd_learn/actions/runs/42876421523). Collaudo Joomla manuale pendente.

## 0.41.52-dev — filtri della banca domande (PR #194)

La PR [#194](https://github.com/jeckodevelopment/jd_learn/pull/194) risolve le issue #155/#156: filtri visibili per ricerca, categoria, stato e lingua, con intestazione Azioni/Actions tradotta. ACL invariata. CI 4/4 superata nel [run](https://github.com/jeckodevelopment/jd_learn/actions/runs/42873607501); collaudo manuale Joomla pendente nel [testbook](QA_TESTBOOK.md#pr-194-integrata--filtri-della-banca-domande).

## 0.41.52-dev — fuso globale del nuovo corso (PR #192)

- Corretto issue #172: la form corso rende selezionabile “Usa globali”; al salvataggio il sito fornisce il fuso IANA, mentre le date UTC e i fusi già scelti restano invariati.
- PR #192 integrata nel commit `64ae66454c7d2a64a3237f1f7f12b2d9174f95ae`; CI standard 4/4 superata nel [run 42865678167](https://github.com/jeckodevelopment/jd_learn/actions/runs/42865678167). Pacchetto verificato [JD-Learn-0.41.52-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42865678167/artifacts/13787890952); checksum e procedura manuale nel [testbook](QA_TESTBOOK.md#pr-192--fuso-globale-per-nuovi-corsi).

## 0.41.52-dev — indice aula comprimibile su smartphone (PR #190 integrata)

- Il programma didattico resta sempre disponibile; sugli schermi stretti parte chiuso e si apre con un pulsante tradotto IT/EN con stato `aria-expanded`. Desktop lo mantiene aperto; senza JS la navigazione resta visibile.
- Aggiunto test DOM per apertura/chiusura e variazione viewport. PR #190 integrata nel commit `6be07e359bf56935470aa58285fb03e92419b86e`; CI standard 4/4 superata nel [run 42860617351](https://github.com/jeckodevelopment/jd_learn/actions/runs/42860617351). Pacchetto [JD-Learn-0.41.52-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42860617351/artifacts/13784608215), SHA-256 interno `278d260dddbdcdda305fff7456943d5ee43a15d042c94e1c3d901fe9f166c332`, wrapper `6214644bc36d91e60cb7da5b9d31f9216e3e53c8492eba1a5ea3ac5fa4962ddd`; collaudo Joomla pendente.

## 0.41.51-dev — retry limitato del salvataggio di avvio (PR #187 integrata)

- Il browser mostra lo stato di salvataggio e ripete una sola volta il POST `lesson.begin` per rete/timeout o HTTP 408, 429 e 5xx. Riutilizza la stessa chiave idempotente, con timeout 8 s e pausa 500 ms; gli altri 4xx non vengono ritentati. Non attribuisce completamento o credito e non promette funzionamento offline.
- PR #187 integrata nel commit `68c984ecb195a66bc7a3b236cffa810cf784ec60`; CI standard 4/4 superata nel [run 42845258898](https://github.com/jeckodevelopment/jd_learn/actions/runs/42845258898). Pacchetto [JD-Learn-0.41.51-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42845258898/artifacts/13779340061), SHA-256 interno `1f7563bd4957f66027dbc1d9b925784a087e196a75ad8909d4b78ce9b2455cab`, wrapper `75e5ede2387cc29e48ab43fcd09bc5ecf125d5816db9eeb638c1387bc311529f`; collaudo Joomla pendente.

## 0.41.50-dev — avvio e ripresa della lezione (PR #185 integrata)

- L’apertura esplicita di una lezione accessibile salva lo stato `started` tramite POST con token Joomla e aggiorna la destinazione Riprendi; il GET aula resta read-only.
- L’avvio non attribuisce completamento o percentuale; il completamento manuale aggiorna la riga esistente. Il POST non incrementa la revisione di una riga video già aperta, preservando sessioni di riproduzione concorrenti.
- PR #185 integrata nel commit `de93c1290e89a5048a5cf6e16c0b15f719580322`; CI standard 4/4 superata nel [run 42839639601](https://github.com/jeckodevelopment/jd_learn/actions/runs/42839639601). Pacchetto [JD-Learn-0.41.50-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42839639601/artifacts/13776106013), SHA-256 interno `9534346da253c8f953da826d203b3f3509fab0682b09e7d09736e60180e28e83`, wrapper `461d8dd9c4d8287c15d6dc667821920e25c4c879108f0a3d9a2ac8cea5ec70f6`; collaudo Joomla pendente.

## 0.41.49-dev — soglia video configurabile per lezione (PR #183 integrata)

- Soglia facoltativa per singola lezione video; campo vuoto eredita la regola della versione, valore esplicito sovrascrive solo la lezione. Persistenza immutabile nelle versioni assegnate e uso nel calcolo del completamento.
- CI standard 4/4 superata nel run 42830117573 sul candidato `0b62f8e3505bbc23d4936cf205d9deeaf9a4d4a1`; merge `4ef19dd8b5ebe4d6eddb1f127a5864929b457297`.
- Pacchetto [JD-Learn-0.41.49-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42830117573/artifacts/13770693036), SHA-256 ZIP interno `46db03c8214873bbd2d266f067585458ebec059e3abbaf2fe29d9e38be1635bb`; collaudo manuale Joomla/provider reali pendente.

## 0.41.43-dev — anteprima dei livelli di accesso (PR #162 integrata)

- La preview editoriale consente di simulare viewing access level Joomla e distingue visibilità di catalogo, accesso ai contenuti e requisito combinato. La simulazione è read-only e non modifica identità, iscrizioni, corso o progressi.
- Aggiunte traduzioni IT/EN e verifiche HTTP/integrazione per accessi misti, autorizzazione editor e assenza di mutazioni.
- CI standard 4/4 superata nel run 42712132178; merge `4c87851d427592885ef3a5348da3e1a182de6042`. Pacchetto [JD-Learn-0.41.43-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42712132178/artifacts/13715794795), SHA-256 interno `39797f26dfee8bcfec34c9aa8365c218653e56857c8133808f1892996e2de738`; collaudo manuale Joomla pendente.

# Cronologia dello sviluppo

Questo documento conserva il percorso passato. **Per decidere cosa funziona e cosa manca usare [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md)**; per le prossime attività usare [NEXT_STEPS.md](NEXT_STEPS.md).

| Versione / periodo | Risultato introdotto |
|---|---|
| 0.41.42 | PR #159 integrata nel commit `1c42b07f3fb7ec7d605f455dcf431510282ef5f2`: archiviazione/ripristino workflow preservando versione live e accesso esistente; CI 4/4 nel run 42704783478; pacchetto verificato; collaudo Joomla pendente |
| 0.41.41 | PR #153 integrata nel commit `a754805387b41bd0509f898e15bd380f76a45645`: workflow Joomla In revisione, blocco authoring, restituzione e approvazione preservando la versione live; CI standard 4/4 nel run 42701584797; collaudo Joomla pendente |
| 0.41.40 | PR #151 integrata nel commit `2dd9d41c2e7496143a009da763fa9738871321c0`: ordine dei collegamenti dashboard, stati sempre visibili per YouTube/Vimeo/Cloudflare Stream/HTML5; CI standard 4/4 nel run 42679096692 (PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6, Chromium e controlli leggeri); collaudo Joomla pendente |
| 0.41.39 | PR #147 integrata nel commit `55ec3d5cc81f9d6756a470d19f123188e37459a4`: accesso amministrativo ai Percorsi formativi dalla dashboard e dal sottomenu con ACL dedicate, guida authoring; CI standard 4/4 superata nel run 42674520291; collaudo Joomla pendente |
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
