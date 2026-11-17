# Ricognizione post-merge PR #578 — 17 novembre 2026

> Stato: ricognizione iniziale #578, integrata dalla verifica dei merge fino alla #587 (17/11/2026). **non** accettazione finale della versione 1.0.0. Non confondere PR integrate, test automatici e collaudi reali Joomla.

## Merge confermati

| PR | Ambito | Stato verificato |
| --- | --- | --- |
| [#578](https://github.com/jeckodevelopment/jd_learn/pull/578) | LCM-04: cestinazione e ripristino conservativo dei docenti | Integrata |
| [#577](https://github.com/jeckodevelopment/jd_learn/pull/577) | LCM-03: operazioni massive e cancellazione protetta dei percorsi | Integrata |
| [#576](https://github.com/jeckodevelopment/jd_learn/pull/576) | LCM-03: cestino e ripristino dei percorsi | Integrata |
| [#575](https://github.com/jeckodevelopment/jd_learn/pull/575) | LCM-03: archiviazione e ripristino dei percorsi | Integrata |
| [#574](https://github.com/jeckodevelopment/jd_learn/pull/574) | Test regressione replay lezioni completate; registrazione collaudo | Integrata |
| [#573](https://github.com/jeckodevelopment/jd_learn/pull/573) | Player: riproduzione delle lezioni già completate | Integrata |
| [#572](https://github.com/jeckodevelopment/jd_learn/pull/572) | Ripristino playback YouTube dopo regressione | Integrata |
| [#571](https://github.com/jeckodevelopment/jd_learn/pull/571) | UX completamento e replay video | Integrata |
| [#570](https://github.com/jeckodevelopment/jd_learn/pull/570) | UX player YouTube orientata all'aula | Integrata |
| [#569](https://github.com/jeckodevelopment/jd_learn/pull/569) | Allineamento metadati/versioni estensioni | Integrata |
| [#568](https://github.com/jeckodevelopment/jd_learn/pull/568) | Recupero controllato attestati | Integrata |
| [#566](https://github.com/jeckodevelopment/jd_learn/pull/566) | PDF attestati e loghi raster | Integrata; download PNG con errore HTTP 503 risolto e collaudato per il caso segnalato |

## Lavori documentali e funzionali ancora da riconciliare

1. **LCM-03/04**: le righe della matrice requisiti precedenti ai merge possono riportare ancora "Non iniziata". Aggiornare solo i sotto-requisiti dimostrati dalle rispettive PR. Per LCM-04 la PR #578 copre **cestinazione e ripristino**: non dedurre automaticamente che tutte le operazioni richieste (in particolare eliminazione definitiva condizionata e verifiche ACL) siano coperte.
2. **LCM-02 e LCM-05**: mantenere aperti gli eventuali residui funzionali e di collaudo. Nessuna chiusura dedotta dal merge delle PR sui percorsi/docenti.
3. **Video**: il collaudo utente ha confermato il replay dopo completamento. Non generalizzare questo riscontro ai quattro provider, a tutti i browser, a tutte le transizioni di sessione o alle verifiche di sicurezza.
4. **Attestati ed email**: il download PDF con logo PNG ha un riscontro positivo mirato; restano da verificare le altre varianti e i workflow SMTP/Scheduler. Le PR di fix non sono prova di accettazione generale.
5. **FE-12, release, ACL e privacy**: i gate manuali e di hardening rimangono distinti dai test CI; consultare `QA_TESTBOOK.md` e `SECURITY_PRIVACY_RELEASE_GATE_1.0.0.md`.
6. **Pacchetto installabile**: la build documentata è `0.57.7-dev`. Non attribuire un artefatto precedente all'ultimo merge senza controllare run, commit, disponibilità dell'artefatto e checksum effettivo dello ZIP Joomla.

## Avanzamento e fonti canoniche

La stima nel registro `IMPLEMENTATION_EVIDENCE.json` ha baseline del 16 novembre ed è **79,56%** (80% arrotondato) come media delle nove macroaree. È un **riferimento storico da ricalcolare**, non una misura aggiornata post-#578, né un'approvazione al rilascio. Non attribuire incrementi senza rivedere pesi e prove dei requisiti.

Per la riconciliazione definitiva usare, nell'ordine: `REQUIREMENTS_STATUS.csv` (stato per requisito e evidenze), `IMPLEMENTATION_STATUS.md` (sintesi e residui), `IMPLEMENTATION_EVIDENCE.json` (baseline e artefatti verificati), `QA_TESTBOOK.md` (accettazione Joomla), `CHANGELOG.md` (modifiche versionate) e `STABLE_1.0.0_PR_PLAN.md` (piano residuo). Una PR documentale non modifica da sola il progresso implementativo.

## Privacy redazionale

Non inserire mai in issue, PR o documentazione pubblica nomi delle installazioni cliente/di prova, URL completi, domini riservati, dati degli utenti o log sensibili: usare sempre "installazione Joomla di prova" e descrizioni genericamente riproducibili. La bonifica dello storico richiede una ricognizione dedicata: questo documento non dichiara di averla completata.

## Comportamenti da testare

Questa modifica è **solo documentale**. Verificare che tutti i collegamenti alle PR siano corretti, che lo stato di integrazione corrisponda a GitHub e che nessun riferimento di un ambiente di prova compaia nel nuovo testo. Nessun nuovo test funzionale Joomla o esito CI viene dichiarato da questa ricognizione.

## Aggiornamento verificato dopo la ricognizione iniziale (17 novembre 2026)

Sono integrate anche [#579](https://github.com/jeckodevelopment/jd_learn/pull/579) (eliminazione condizionata docenti), [#580](https://github.com/jeckodevelopment/jd_learn/pull/580) (ricognizione documentale), [#581](https://github.com/jeckodevelopment/jd_learn/pull/581), [#582](https://github.com/jeckodevelopment/jd_learn/pull/582), [#584](https://github.com/jeckodevelopment/jd_learn/pull/584), [#586](https://github.com/jeckodevelopment/jd_learn/pull/586) (test di isolamento, autorizzazioni, HTTP/CSRF e transizioni positive dei percorsi), e le rispettive PR documentali [#583](https://github.com/jeckodevelopment/jd_learn/pull/583), [#585](https://github.com/jeckodevelopment/jd_learn/pull/585), [#587](https://github.com/jeckodevelopment/jd_learn/pull/587).

**Riconciliazione funzionale:** LCM-02 non iniziata; LCM-03 parziale per accettazione manuale e verifiche residue, ma azioni massive e cancellazione protetta sono già implementate in #577; LCM-04 parziale con cancellazione condizionata implementata in #579; LCM-05 parziale nonostante i test #581/#582/#584/#586. Le evidenze di test automatici non sostituiscono il collaudo su Joomla reale. La stima 79,56% è storica e invariata fino a una rivalutazione dei nove pesi.

**Rettifica documentale:** le frasi anteriori che descrivono la cancellazione condizionata di docenti/percorsi e i test HTTP/CSRF come interamente da implementare sono superate dai merge successivi. La ricognizione non certifica audit completo di tutti i documenti storici né checksum di un artefatto recente.
