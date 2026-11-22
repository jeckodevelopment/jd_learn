# LES — chiusura delle attività software verificabili (1.0.0)

Data: 22 novembre 2026. Fonte di verità per la valutazione funzionale: `docs/FUNCTIONAL_CRITERIA_1.0.0.json` e `docs/REQUIREMENTS_STATUS.csv`.

## Esito

Nel perimetro LES-01–04 non risultano criteri classificati `missing` (lacuna implementativa accertata). Le attività software individuate nella revisione sono integrate in `main`, con suite Joomla/CI superata. **Non equivale a chiusura funzionale integrale**: quattro criteri conservano correttamente lo stato `unverifiable` perché richiedono verifica di contesti Joomla aggiuntivi oppure configurazioni hosting, proxy e CDN reali. La matrice resta `Da eseguire` per gli scenari manuali.

| Requisito | Criteri implementati | Residui non verificabili | Evidenza recente | Collaudo Joomla |
| --- | ---: | ---: | --- | --- |
| LES-01 | 4/4 | 0 | [PR #898](https://github.com/jeckodevelopment/jd_learn/pull/898), copie versionate e materiali | Non eseguita |
| LES-02 | 1/3 | 2 | [#943](https://github.com/jeckodevelopment/jd_learn/pull/943), [#947](https://github.com/jeckodevelopment/jd_learn/pull/947), [#948](https://github.com/jeckodevelopment/jd_learn/pull/948); provider allowlist, sanitizzazione e URL con credenziali | Non eseguita |
| LES-03 | 2/3 | 1 | [#940](https://github.com/jeckodevelopment/jd_learn/pull/940), [#945](https://github.com/jeckodevelopment/jd_learn/pull/945); storage cifrato, GET/HEAD/Range anonimi e consegna autorizzata | Non eseguita |
| LES-04 | 1/2 | 1 | [#941](https://github.com/jeckodevelopment/jd_learn/pull/941); limite applicativo materiali 1–20 MiB e vincoli PHP | Non eseguita |

Totale **8/12 criteri classificati implemented, 0 missing, 4 unverifiable**. Non è una percentuale di accettazione.

## Che cosa è stato verificato automaticamente

- LES-01: attributi delle lezioni, completamento, materiali corso/lezione, copia e versionamento senza trasferire lo stato didattico.
- LES-02: allowlist dei provider, filtro HTML per superfici core, blocco di script/iframe arbitrari, rifiuto di credenziali negli URL di sorgenti video, rel `noreferrer noopener` e rimozione degli href contenenti credenziali da testi editoriali e corpi delle lezioni.
- LES-03: accesso protetto e HTTP Range dei file locali, file gestiti cifrati e protetti da guard PHP, rifiuto di radici pubbliche configurate; richiesta anonima GET, HEAD o Range al blob gestito respinta nella CI Joomla disposable.
- LES-04: nessuna promessa di transcodifica o CDN illimitata; upload limitati da configurazione applicativa e impostazioni PHP.

L'ultima CI completa pertinente a LES-02 è [#45053968452](https://github.com/jeckodevelopment/jd_learn/actions/runs/45053968452), **4/4 verde**, con [pacchetto di sviluppo JD-Learn-0.64.22-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/45053968452/artifacts/14766961529). Il link identifica l'artefatto Actions; non costituisce attestazione autonoma del checksum dello ZIP Joomla interno.

## Casi di accettazione separati, non bloccanti per nuove implementazioni core

1. **LES-02**: su installazione Joomla reale e con i plugin di contenuto effettivamente autorizzati, verificare che HTML aggiunto dopo i filtri core non introduca script, iframe o URL con credenziali. Verificare nel browser i link dei testi, dei percorsi, delle biografie e delle lezioni.
2. **LES-03**: su hosting effettivo (Apache/LiteSpeed/Nginx e, se presente, reverse proxy/CDN), verificare che l'accesso diretto anonimo alla directory gestita tramite GET, HEAD e Range non riveli file né metadati; provare inoltre consegna, revoca e download autorizzato.
3. **LES-04**: provare dimensionamento reale dei provider e del percorso storage/CDN scelto, con limiti documentati e un carico coerente con l'utenza prevista; non attribuire a JD Learn capacità di transcodifica e distribuzione non incluse.

Queste verifiche restano aperte nell'asse di accettazione: non creare falsi criteri `implemented`, non dichiarare manual test `Eseguito` e non modificare la percentuale globale senza riconciliazione nel registro funzionale.
