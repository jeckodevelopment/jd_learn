## 0.56.14-dev — MAIL-11.2: opt-in durante la registrazione Joomla

- Aggiunto il plugin utente JD Learn che inserisce nella registrazione Joomla un checkbox facoltativo e non preselezionato per gli annunci di nuovi corsi.
- La scelta richiede conferma email double opt-in; l’account viene creato anche se l’invio fallisce. Il plugin viene attivato alla prima installazione e il suo stato manuale è preservato negli aggiornamenti.
- Pacchetto aggiornato con l’undicesima estensione; test CI coprono il form e lo stato plugin. Collaudo reale Joomla/SMTP pendente secondo `docs/QA_TESTBOOK.md`.

## 0.56.12-dev — MAIL-11.2: adesione durante iscrizione automatica (PR #319)

- Aggiunta una scelta facoltativa e non preselezionata per la richiesta di adesione agli annunci di nuovi corsi nel modulo di avvio automatico.
- Il double opt-in è richiesto solo dopo l'avvio riuscito; le richieste duplicate non reinviano una conferma già pending. Un errore SMTP non annulla l'iscrizione.
- CI standard 4/4 nel [run 43469236700](https://github.com/jeckodevelopment/jd_learn/actions/runs/43469236700); merge `571cd69639a9d7ca0f7c204dd55e08ce5276d500`.
- Pacchetto [JD-Learn-0.56.12-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43469236700/artifacts/14059519179), SHA-256 ZIP `2219c21df9308f2688ef83f0c408fe9da17280a636bd8b7fc8578edacde16b47`, digest artifact wrapper `98e31e8727ea5170e8c8a5e9bd9c1bb201225393cb7f009f57ec5b03c5adebf6` (scadenza 27 novembre 2026 alle 17:09 UTC). Accettazione manuale Joomla/SMTP pendente; gli annunci restano disattivati.

# 0.56.11-dev — consenso confermato per annunci nuovi corsi (MAIL-11.2/11.3, PR #317 integrata)

- Aggiunge in Area personale l’adesione facoltativa non preselezionata, conferma email double opt-in con token SHA-256 monouso e scadenza 24 ore, stato pending/confirmed/revoked e revoca autenticata.
- Registra data, origine e versione dell’informativa; rende i dati esportabili/rimovibili dal plugin Privacy Joomla e aggiunge il template nativo di conferma.
- Nessuna campagna o annuncio corso viene accodato; manca ancora la revoca tramite link nell’email di annuncio e la selezione/preview amministrativa.
- PR [#317](https://github.com/jeckodevelopment/jd_learn/pull/317), merge `6c44bc4e852bf1d693b53646b5deee6c83153629`; CI standard 4/4 nel [run 43457091398](https://github.com/jeckodevelopment/jd_learn/actions/runs/43457091398). Collaudo manuale Joomla/SMTP pendente.
- Pacchetto installabile [JD-Learn-0.56.11-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43457091398/artifacts/14053993647), ZIP interno SHA-256 `3e6b918c157b320984064a7fecc0b392ef5bc049d164a3e088b5a835e6732420`, wrapper SHA-256 `c8b6c3a4421d90a6c3588c80ffff635de614b401b1e313d7d3e6dcfdd3c3d569` (scade 27 novembre 2026).

# 0.56.8-dev — notifica disponibilità attestato (CERT-D, PR #307 integrata)

- Aggiunge il template Joomla `com_jlms.certificate.available` e l’accodamento idempotente alla prima emissione.
- Preferenze globali/per corso; errore outbox non annulla l’emissione; consegna differita al task esistente.
- CI standard 4/4 nel [run 1038](https://github.com/jeckodevelopment/jd_learn/actions/runs/43432555845); collaudo manuale Joomla in `docs/QA_TESTBOOK.md` ancora da eseguire.

# 0.56.7-dev — gestione amministrativa attestati (PR #304 integrata)

- Elenco ricercabile/filtrabile, dettaglio su snapshot e download amministrativo protetto; ACL `jlms.certificates.manage` e audit minimo per elenco, consultazione e download.
- Migrazione additiva per il registro audit; schema ora a 44 tabelle e 65 FK.
- PR [#304](https://github.com/jeckodevelopment/jd_learn/pull/304) integrata nel merge `461c2d83c85e1152e2fb2497e0e15be62e36cec9`, milestone 1.0.0. CI standard 4/4 nel [run 1031](https://github.com/jeckodevelopment/jd_learn/actions/runs/43421028364); collaudo manuale Joomla pendente.
- Pacchetto [JD-Learn-0.56.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43421028364/artifacts/14034621388), ZIP interno SHA-256 `28c462f2eae0b795cf02574408ac9bfa830e8e4a95bd5ad634bc011700e14bc4`, wrapper `f0467b1a4e96ab784283090e48377d400fbb69f944579ffd444934cdc41175e2` (scadenza 27 novembre 2026 alle 12:00 UTC).

# 0.56.6-dev — PDF e download protetto attestati (PR #302 integrata)

- Generazione PDF A4 IT/EN con dati certificati congelati, intestazioni personalizzabili, colore e logo JPEG locale.
- Elenco attestati nella dashboard studente e download GET/HEAD protetto; PDF conservato come asset nello storage privato esistente e rendering alla prima richiesta autorizzata.
- PR [#302](https://github.com/jeckodevelopment/jd_learn/pull/302) integrata nel merge `a3a3a706b4d312f58a79952ad355c02fd7dcd3a8`, milestone 1.0.0; CI standard 4/4 nel [run 1023](https://github.com/jeckodevelopment/jd_learn/actions/runs/43410407842). Collaudo manuale Joomla, audit e backup/ripristino pendenti. Requisiti CERT-03/05 e parte di CERT-08.
- Pacchetto [JD-Learn-0.56.6-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43410407842/artifacts/14029108411), ZIP Joomla interno SHA-256 `73a8cb6de3e4c2f636b2a2b0702799864e3644dcc3c463913e628a4c4c4056d1`, wrapper `313e4a2b18adc2d1f431114eed9b19ff593ec5b154dc0784481e094546a80a92` (scadenza 27 novembre 2026).

## 0.56.4-dev — conferma iscrizione amministrativa (in preparazione)

- Collega l’iscrizione amministrativa singola al template Joomla di conferma e all’outbox già esistente.
- Applica le preferenze globali/per corso e deduplica per enrollment; i preassegnamenti senza accesso corrente non inviano una falsa conferma.
- Requisiti: IMP-09-MAIL-A, MAIL-02/05. Collaudo Joomla/SMTP pendente.

## 0.56.3-dev — Notifiche sospensione e riattivazione iscrizione (PR #297 integrata)

- Registra template Joomla per sospensione e riattivazione dell’accesso al corso.
- Accoda i messaggi nella transazione della modifica di stato, applicando preferenze globali/per corso; la chiave evento deduplica i replay.
- PR [#297](https://github.com/jeckodevelopment/jd_learn/pull/297) integrata nel merge `18d916075ac9580f442c7ec85a45c9c82c6e9642`, milestone 1.0.0; CI standard 4/4 nel [run 43307181190](https://github.com/jeckodevelopment/jd_learn/actions/runs/43307181190).
- Pacchetto [JD-Learn-0.56.3-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43307181190/artifacts/13983759363), SHA-256 ZIP `8c3d5e0de6935f67cf93e1b7caf3a27e7d10d61d26c20450f5d9ace1cc6cdee0`; digest wrapper `7177c5d117d83bc41725845bfbde05ba40a497adae2d8c1ef373e1264df70e4a` (scade il 26 novembre 2026).
- Progressione ricalcolata: 66,39% (66% arrotondato); email 28,5%, attestati essenziali 0%. Collaudo manuale Joomla/SMTP pendente.

## 0.56.3-dev — Notifiche sospensione e riattivazione iscrizione (in sviluppo)

- Registra template Joomla per sospensione e riattivazione dell’accesso al corso.
- Accoda i messaggi nella stessa transazione della modifica di stato e applica le preferenze globali/per corso.
- La chiave evento deduplica i replay della transizione; il mancato invio non annulla lo stato didattico.

## 0.56.2-dev — Preferenze notifiche email per sito e corso (PR #295 integrata)

- Aggiunge opzioni globali per abilitare o disabilitare conferme di iscrizione automatica e email di completamento.
- Aggiunge override per corso: eredita dall'impostazione globale, abilita o disabilita l'evento. Le preferenze sono salvate in `params_json` senza migrazioni DB e valutate alla transizione, prima dell'accodamento.
- PR [#295](https://github.com/jeckodevelopment/jd_learn/pull/295) integrata nel merge `8152e337455b1ec7b9d307dc9248c8070b964709`; CI standard 4/4 nel [run 43305020698](https://github.com/jeckodevelopment/jd_learn/actions/runs/43305020698).
- Pacchetto installabile [JD-Learn-0.56.2-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43305020698/artifacts/13983557253), SHA-256 ZIP `9923d41d5d342ac1b854090f6956b9c9fff745c2b71f939a446a6b7494849ad7`; digest artefatto `4e541658e5cf45c2b1238c41bb1501a768ec850cbd3b2958c37d3e735959450e` (scade il 26 novembre 2026).
- Avanzamento aggiornato a 66,06% (66% arrotondato), contando anche gli attestati essenziali PDF approvati e ancora non implementati (0%).

## 0.56.1-dev — Notifiche email core con coda Joomla (PR #291 integrata)

- Collega la conferma dell'iscrizione automatica e il completamento corso ai template Mail Templates introdotti in 0.56.0-dev.
- Aggiunge una coda persistente con chiave univoca evento, invio tramite attività pianificata Joomla, retry progressivo limitato e pulizia dei dati personali a consegna confermata.
- Se la consegna SMTP ha esito incerto, il messaggio resta in stato `sending` e non viene reinviato automaticamente; le richieste in approvazione, gli inserimenti manuali/import, gli attestati e i promemoria restano fuori da questo incremento.
- PR [#291](https://github.com/jeckodevelopment/jd_learn/pull/291) integrata nel merge `a6488b3b646d607dbc20317168f1c69dae055527`, milestone 1.0.0; CI standard 4/4 nel [run 43300371393](https://github.com/jeckodevelopment/jd_learn/actions/runs/43300371393).
- Pacchetto [JD-Learn-0.56.1-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43300371393/artifacts/13981827024), SHA-256 `beacb58745732bf803d61c6c10c72e483ad6eba2ee2a9e4337eb80d326b90154`; digest wrapper `d4fe5ad8470a88163b4682d6a95aafaf013b6ea9358ecee9c071514e91a9ceb5` (scade il 26 novembre 2026).
- Avanzamento aggiornato: 73,88% (74% arrotondato), includendo il macroambito email approvato al 22%.

## 0.56.0-dev — CORE-18: template email nativi Joomla (PR #288 integrata)

- PR [#288](https://github.com/jeckodevelopment/jd_learn/pull/288), merge `9ea5725c1b1784d9ae59a91339d8b3dcb83d1144`, milestone 1.0.0. Registra template per richiesta, conferma iscrizione e completamento corso nel gestore Joomla; gli aggiornamenti preservano testo e traduzioni personalizzati.
- CI standard 4/4 nel [run 43294015095](https://github.com/jeckodevelopment/jd_learn/actions/runs/43294015095); test d’installazione/upgrade superato su PHP 8.3/MySQL 8.4 e PHP 8.4/MariaDB 10.6.
- Pacchetto [JD-Learn-0.56.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43294015095/artifacts/13979316386), SHA-256 ZIP `4f3907ff29bb195b2568fed588fd259ddd1ff5ddc228061868aac1b7afd20d37`, digest wrapper `9fa791b4157c3b405059ce5a5d90acfa6584af66b358c2c8f533273b9a4c0deb` (scade il 26 novembre 2026).
- Avanzamento globale **81,71% esatto (82% arrotondato)**; M10 93%.
- Collaudo Joomla manuale ancora pendente. L’invio automatico con outbox/retry/deduplicazione AC-13/AC-57 resta un residuo 1.0.0; attestati e promemoria avanzati restano esclusi.

## 0.55.0-dev — IMP-04 / feedback salvataggio progresso video (PR #287)

- PR [#287](https://github.com/jeckodevelopment/jd_learn/pull/287) integrata nel commit `2af8994271d95f3c41b3e5f813ba2222794265c5`; CI standard 4/4 nel [run 43289138110](https://github.com/jeckodevelopment/jd_learn/actions/runs/43289138110).
- Il player mostra gli stati localizzati di salvataggio e conferma; completamento, retry e gestione degli errori restano invariati.
- Pacchetto installabile [JD-Learn-0.55.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43289138110/artifacts/13975579105), SHA-256 ZIP `fb30f231780fc290aeecb64ff3ab7b79f3bb2c00ea19085ea67c0ff239794282`. Collaudo Joomla manuale ancora pendente in `docs/QA_TESTBOOK.md`.

## 0.54.0-dev — AUT-03: avviso durata video in sola consultazione (PR #284 integrata)

- Merge `04704621f64a7ee712743f98037dfe31d7a4606f`; CI standard 4/4 nel [run 43256423443](https://github.com/jeckodevelopment/jd_learn/actions/runs/43256423443).
- Il riepilogo pre-pubblicazione mostra un avviso quando un video consultabile non ha durata confermata. La modalità Apprendimento mantiene il blocco esistente.
- Pacchetto installabile [JD-Learn-0.54.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43256423443/artifacts/13961114324), SHA-256 ZIP `bb30f239755b24b7c439d03ba5a70b7e11294360d648ce2a0a291cdbb0c46f12`; SHA-256 wrapper `ba7f9c7568500bfd82da3d9ff054ea907272942c9dfc9ff9f5203bfc8331494d` (estrarre una volta; scade il 26 novembre 2026).
- Collaudo Joomla manuale ancora pendente: vedere `docs/QA_TESTBOOK.md`.
- Avanzamento globale **81,43% esatto (81% arrotondato)**; M01/M02 82%, M03 78%, M04 99%.

## 0.54.0-dev — AUT-03: avviso durata video in sola consultazione

- Il riepilogo pre-pubblicazione mostra un avviso quando un video consultabile non ha una durata confermata. La modalità Apprendimento mantiene il blocco esistente.
- Regressione aggiunta in `tests/run.php`; accettazione Joomla manuale documentata in `docs/QA_TESTBOOK.md` e ancora da eseguire.

## 0.53.0-dev — IMP-04 / PR #282 integrata

- Merge `66a59d0a5e4e60943b0d1487ddce4c95c64f97b0`; PR [#282](https://github.com/jeckodevelopment/jd_learn/pull/282), CI standard 4/4 [run 43252689828](https://github.com/jeckodevelopment/jd_learn/actions/runs/43252689828).
- Un replay idempotente di un batch video già salvato ricalcola il conflitto di ripresa dalla sessione corrente: restituisce `resume_conflict=true` e lascia invariati resume e copertura.
- Pacchetto [JD-Learn-0.53.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43252689828/artifacts/13959653251), ZIP installabile SHA-256 `7898ba9c5dc340aaec1b9c260ef729bc4314cde4cd31bc44c2411efacca0616e`, wrapper `a2dc70a32891b12c378f0920c75c5fa4b9da3c47e46fc0efd442b898051235fa`, scadenza 26 novembre 2026.
- Collaudo Joomla manuale ancora pendente: vedere [QA_TESTBOOK.md](docs/QA_TESTBOOK.md).
- Avanzamento globale invariato: 81,29% (81% arrotondato), M03 78%, M04 99%.
## 0.52.0-dev — ripresa video protetta tra sessioni concorrenti (IMP-04)

- PR [#280](https://github.com/jeckodevelopment/jd_learn/pull/280), milestone 1.0.0, integrata nel commit `dcc49eb0e09b0ba6f2424e6eaaa973b837b3e4f4`; CI standard 4/4 nel [run 43250267681](https://github.com/jeckodevelopment/jd_learn/actions/runs/43250267681).
- Una sessione video che rileva un conflitto di ripresa mantiene la revisione obsoleta per tutti i batch successivi. Continua ad aggiungere copertura senza spostare il resume più recente né duplicare il completamento.
- Pacchetto [JD-Learn-0.52.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43250267681/artifacts/13958934116), SHA-256 `f5c0986ae59361de77c67ebae9ed9d91583b12f38e835a4a12a9cedfebfb9216`, wrapper `3f528639d38a2c61d39fc5713f6750f7dfadb5bf5c6220d810174cddaf8b89e9`; scade il 26 novembre 2026.
- Collaudo multi-browser Joomla ancora da registrare nel testbook.

## 0.51.0-dev — capabilities minime per il tracciamento video (IMP-05)

- PR [#278](https://github.com/jeckodevelopment/jd_learn/pull/278), milestone 1.0.0, integrata nel commit `62af5dbcbc6bd2300c67bc149c04bccaa0203aec`; CI standard 4/4 nel [run 43246533928](https://github.com/jeckodevelopment/jd_learn/actions/runs/43246533928).
- Per pubblicare e avviare una lezione video tracciata, provider e descrittore devono supportare posizione, durata e seek. Velocità, caption e trascrizione restano opzionali. La consultazione non richiede capabilities di tracciamento.
- Pacchetto [JD-Learn-0.51.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43246533928/artifacts/13957416321), SHA-256 `b6b01e432ddd660c32f25dfa4f611bfab069edcfa92977349f2ef16dbff0fd2f`, wrapper `2da0bd7ce7d03b58227ab24b4e7c84060143d41d5841aa3b54449aaa06a707bc`; scade il 26 novembre 2026.
- Collaudo Joomla dei quattro provider e adapter con capabilities incomplete ancora pendente; vedere il [testbook](docs/QA_TESTBOOK.md).

## 0.50.0-dev — preferenza locale caption su YouTube e Cloudflare Stream (STU-05)

- PR [#276](https://github.com/jeckodevelopment/jd_learn/pull/276), milestone 1.0.0, merge `d29cdc9326bcc0d1f9ac3dd454fa40189ca20c82`; CI standard 4/4 nel [run 43240360856](https://github.com/jeckodevelopment/jd_learn/actions/runs/43240360856).
- Pacchetto [JD-Learn-0.50.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43240360856/artifacts/13954487496), SHA-256 interno `fce6b1f09ab3d7d1fe1821aff366a11718e420e2331754ea18cf4b741b0b877c`, digest wrapper `094f514f005324c76af7284e4e0d43a2e09a25563976c94982504e23bf78d342`; estrarre una volta, scadenza 26 novembre 2026.
- La lingua caption valida salvata nel browser viene passata a YouTube (`cc_lang_pref` e `cc_load_policy`) e Cloudflare Stream (`defaultTextTrack`) all’inizializzazione. Input non validi o localStorage non accessibile non bloccano la riproduzione. Il selettore JD Learn rimane limitato alle tracce interrogabili tramite Vimeo/HTML5; nessun dato lingua o telemetria aggiuntiva viene inviato.
- Accettazione Joomla manuale con account/provider/browser reali ancora pendente secondo il [testbook](docs/QA_TESTBOOK.md).

## 0.49.0-dev — anteprime pubbliche esplicite delle lezioni di testo (IMP-02)

- PR [#274](https://github.com/jeckodevelopment/jd_learn/pull/274), milestone 1.0.0, integrata nel commit `2f371ddb09eaeeb1a6ae11264793dad230b7e274`. CI standard 4/4 (PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6, Chromium e controlli leggeri) nel [run 43234079722](https://github.com/jeckodevelopment/jd_learn/actions/runs/43234079722).
- Pacchetto [JD-Learn-0.49.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43234079722/artifacts/13951218766), SHA-256 interno `5d5abf5374426bc2585520a0fc4d41b6057085d4f49802a94a0ed16df031e408`, digest wrapper `0b00be026f88f683c17d9dea92454202aa4c3d91be800fde175b32b8cb6b18be`; estrarre l’archivio una volta. Scade il 26 novembre 2026.
- Aggiunge un’impostazione di corso e la selezione esplicita per le lezioni Testo; la pubblicazione impedisce preview attive quando il corso non le abilita.
- La vista ospite rivalida visibilità del corso, versione didattica corrente, selezione e finestra della lezione; non crea iscrizioni, progressi o tentativi. Video, quiz e materiali di lezione non vengono esposti.
- Collaudo Joomla manuale ancora da eseguire secondo [testbook](docs/QA_TESTBOOK.md#imp-02--anteprime-pubbliche-esplicite-delle-lezioni-di-testo-edge-02--isc-04).

## 0.48.0-dev — consultazione video senza requisiti di tracciamento (IMP-02 / AUT-03)

- PR [#272](https://github.com/jeckodevelopment/jd_learn/pull/272), milestone 1.0.0, integrata nel commit `ae81e46a69fc3d2528585c497f0b263677d3d0f9`; CI standard 4/4 nel [run 43211155470](https://github.com/jeckodevelopment/jd_learn/actions/runs/43211155470). Package [JD-Learn-0.48.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43211155470/artifacts/13940634151), SHA-256 interno `6e7d35f18d15eb71e6e7d3c13906038da52f4fb06fc191a26dc35946aa8182dd`, digest wrapper `831e28868f9bcfe1605dc8fd43c9232a1b2b26f4f196733c3af578c0dd113e1a`; scade il 26 novembre 2026.
- La modalità di sola consultazione pubblica lezioni video anche senza durata misurata e senza capacità di telemetria/progresso. Il preflight e il controllo server restano coerenti; configurazione, normalizzazione della sorgente e validità del contenuto sono ancora verificate.
- Test Joomla estesi sul percorso di pubblicazione e regressione del preflight. Collaudo manuale in `docs/QA_TESTBOOK.md`.

## 0.47.0-dev — modalità corso di sola consultazione (IMP-02 / AUT-03)

- PR [#270](https://github.com/jeckodevelopment/jd_learn/pull/270), milestone 1.0.0, integrata nel commit `0a1294e491e0aa531aeca776911674fc701ddff4`; CI standard 4/4 nel [run 43207411958](https://github.com/jeckodevelopment/jd_learn/actions/runs/43207411958). Package [JD-Learn-0.47.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43207411958/artifacts/13939861423), SHA-256 interno `e65ceefa13de8ec577a84d8284ec3a09012cb6e2950351674dec9af6ed4c009f`, wrapper `7721896ba521c4ba8cfbb836023af16d752df3b2c40890000fe1cb29e22cc6a0`; scade il 26 novembre 2026.

- Aggiunta una modalità esplicita per pubblicare corsi con sole lezioni facoltative. La modalità standard continua a richiedere attività obbligatorie.
- I corsi di consultazione non accettano quiz, prerequisiti o sequenziamento e non attribuiscono completamenti o percentuali; dashboard e percorsi indicano che il progresso non si applica.
- Aggiornati il preflight, la validazione autorevole, le guide e le regressioni automatiche. Accettazione manuale Joomla ancora da eseguire.

# Changelog

## 0.46.0-dev — snapshot dei campi corso (IMP-01 / CORE-10)

- PR [#266](https://github.com/jeckodevelopment/jd_learn/pull/266), milestone 1.0.0, integrata nel commit `d41111e229ced8460ff9e30a0baa24f2094c4e60`; CI standard 4/4 nel [run 43196626438](https://github.com/jeckodevelopment/jd_learn/actions/runs/43196626438). Pacchetto [JD-Learn-0.46.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43196626438/artifacts/13932614114), SHA-256 interno `044782978b03fa740889c6f215439c7658a84a95d7bb9fe721f64dfc19ca5183`, digest wrapper `4993336bd9f382f8e6df7191b4842c7f3699525a12ab2c46dfa7abfff7384013` (scade il 26 novembre 2026).

- Alla pubblicazione, i valori e le definizioni dei campi Joomla `com_fields` del corso sono congelati in `fields_snapshot_json` della versione didattica e inclusi nell'hash strutturale. I cambi editoriali successivi non alterano la versione assegnata; una nuova pubblicazione registra i valori aggiornati.
- Il limite artificiale che rifiutava versioni con snapshot corso non vuoto è rimosso. Restano attivi i controlli dello snapshot lezione, dei livelli di accesso Joomla e del limite massimo di campi.
- Collaudo manuale Joomla in `docs/QA_TESTBOOK.md`; verifica automatica nella CI della PR.

## 0.45.0-dev — selettori ricercabili nel roster (IMP-08)

- PR [#264](https://github.com/jeckodevelopment/jd_learn/pull/264), milestone 1.0.0, integrata nel commit `aed93820f0a948b11d3427ce9ba4d312621b0d74`; CI standard 4/4 nel [run 43188504970](https://github.com/jeckodevelopment/jd_learn/actions/runs/43188504970). Artifact [JD-Learn-0.45.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43188504970/artifacts/13929363404), ZIP SHA-256 `ee4b58ba2bbd81821a1b481951c7a411b767d3a7885c0e6b402a1692f6a5b677` (artifact in scadenza il 26 novembre 2026).

- I filtri Versione e Iscritto nel roster sono selettori ricercabili e mostrano solo voci del corso autorizzato. Ricerca libera per nome/username, stato, avanzamento e permessi per corso restano integrati.
- Test automatici HTTP e JavaScript aggiunti. Collaudo Joomla manuale richiesto nel testbook; nessuna modifica allo storico o ai diritti di accesso.


## 0.44.0-dev — filtro per ambito nella gestione materiali (MAT-01)

- La vista amministrativa dei materiali consente di filtrare l’elenco per corso, singola lezione o corso e tutte le lezioni; il filtro conserva la paginazione e le azioni operano solo sugli allegati della versione autorizzata.
- Aggiunti messaggi IT/EN per il filtro, il conteggio e lo stato senza risultati. Il collaudo automatico copre separazione degli ambiti e validazione di lezioni estranee alla versione.
- Collaudo Joomla manuale richiesto nel testbook; nessun file diventa pubblico né cambia il comportamento di accesso/download.


## 0.43.0-dev — preferenza lingua sottotitoli player (STU-05)

- Aggiunta la preferenza lingua locale per le tracce caption/sottotitoli esposte dal player; selezione esatta o fallback al codice lingua principale e comando per disattivare le tracce.
- La selezione è disponibile per Vimeo e per tracce native HTML5. YouTube e Cloudflare Stream non espongono all’adapter un elenco interrogabile e non mostrano il selettore. La preferenza resta nel browser e non modifica progresso, soglia o dati Joomla.
- Collaudo Joomla da registrare secondo la sezione STU-05 in `docs/QA_TESTBOOK.md`; selezione e persistenza verificate anche con test automatici su adapter simulati.

## 0.42.0-dev — velocità di riproduzione video (STU-05 / IMP-05)

- Aggiunta una preferenza locale per la velocità video, applicata solo se il player e il singolo video la supportano; disponibile sui player YouTube, Vimeo quando consentito dall’account/video, e HTML5.
- Il player Cloudflare Stream non espone questa impostazione e non mostra il selettore. La velocità effettiva resta parte dei campioni di telemetria già usati dal calcolo della copertura; nessuna soglia o regola di completamento cambia.
- PR [#258](https://github.com/jeckodevelopment/jd_learn/pull/258), milestone 1.0.0, integrata nel commit `c04bda9b5db8f82e571b27400863cb29ba5b84ef`; CI standard 4/4 nel [run 43134490135](https://github.com/jeckodevelopment/jd_learn/actions/runs/43134490135). Pacchetto [JD-Learn-0.42.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43134490135/artifacts/13907211349), SHA-256 interno `b984c68bcd06470d656de5b11d88e13aba614327df37216b66058e655826d26d`; digest wrapper `4cba266ce1b7008f5d4736d25482f589566a030fdb53a7130f0bd0a6fae5720e` (scade il 26 novembre 2026).
- STU-05 resta parziale: preferenza lingua da completare e collaudo provider da registrare nel [testbook](docs/QA_TESTBOOK.md#stu-05--preferenza-velocita-player-0420-dev).

## Processo di rilascio — issue #160

- Definita la policy SemVer per le versioni 0.x, la stabile 1.0.0 e le release successive in [VERSIONING.md](docs/VERSIONING.md).
- PR [#251](https://github.com/jeckodevelopment/jd_learn/pull/251), milestone 1.0.0, integrata nel commit `f15945853bd11546300d9776fa4bdc12707369a9`; CI standard 4/4 nel [run 43130882855](https://github.com/jeckodevelopment/jd_learn/actions/runs/43130882855). Issue #160 chiusa come completata.
- Il controllo strutturale verifica versione canonica e corrispondenza tra manifest del package e del componente; builder e generatore feed rifiutano versioni stabili con zeri iniziali.
- Modifica di processo e strumenti; nessuna variazione del comportamento Joomla o del numero di versione runtime.

## 0.41.81-dev — titoli amministrativi senza duplicati (STAB-05 / issue #199)

- Rimossi i titoli interni ripetuti dalla Dashboard e dalla lista Percorsi formativi; resta il titolo Joomla nella toolbar.
- Conservate l’introduzione Dashboard e la caption accessibile della tabella Percorsi. Aggiunti controlli HTTP e strutturali contro la regressione.
- PR [#247](https://github.com/jeckodevelopment/jd_learn/pull/247), milestone 1.0.0, integrata nel commit `a8734dd606cc579077312dfecd9cd66773934804`; CI standard 4/4 nel [run 43129077496](https://github.com/jeckodevelopment/jd_learn/actions/runs/43129077496).
- Pacchetto [JD-Learn-0.41.81-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43129077496/artifacts/13904467823), SHA-256 interno `3ef211e6ac7713d381fb9fabb106ee40881eca72a9b3bbff5465fd300bdcc573`, wrapper `1924e7a641dbb539e3b5833d6b57a737c2389edda2645b8c6d6688e4699df3ee` da estrarre una volta; scade il 26 novembre 2026.
- Collaudo Joomla pendente: vedere [STAB-05 nel testbook](docs/QA_TESTBOOK.md#stab-05--titoli-non-duplicati-in-dashboard-e-percorsi-formativi-04181-dev-issue-199).

## 0.41.80-dev — rimozione del collegamento Programma duplicato (STAB-05 / issue #200)

- Rimosso il collegamento “Programma didattico” duplicato dal contenuto dell’editor; restano l’azione toolbar e il riepilogo nella scheda Programma.
- Aggiornato il test HTTP perché verifichi l’assenza del link duplicato e conservi l’accesso al Programma.
- PR [#244](https://github.com/jeckodevelopment/jd_learn/pull/244), milestone 1.0.0, integrata nel commit `ce3ee6f9d62c2483a3a826a34c0a0ba9b0d3bf62`; CI standard 4/4 nel [run 43121346817](https://github.com/jeckodevelopment/jd_learn/actions/runs/43121346817).
- Pacchetto [JD-Learn-0.41.80-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43121346817/artifacts/13900597900), SHA-256 interno `5faefa65f5dce76823cb8dd7aa378ebe1801ad720bc740dc7ebe228bdb19132a`, wrapper `c82f72b0c1728bf7bda492c368e5581a3768133ad1ce06e6c9c3f9e50c65b2c7` da estrarre una volta; scade il 25 novembre 2026.
- Collaudo manuale Joomla pendente: vedere [STAB-05 nel testbook](docs/QA_TESTBOOK.md#stab-05--un-solo-accesso-al-programma-nell-editor-corso-04180-dev-issue-200).

## 0.41.79-dev — label compatta per i campi lezione (STAB-04 / PR #241)

- Accorcia la label del collegamento ai campi aggiuntivi delle lezioni: “Campi aggiuntivi” in italiano e “Lesson fields” in inglese, sia nella toolbar Programma sia nei cataloghi di sistema.
- La destinazione resta la gestione nativa `com_fields` con contesto `com_jlms.lesson`; permessi e visibilità del comando non cambiano.
- Il controllo strutturale verifica le due traduzioni in entrambi i file lingua.
- PR [#241](https://github.com/jeckodevelopment/jd_learn/pull/241), milestone 1.0.0, integrata nel commit `adc3bf5c205590c351ead14c4a086f62235b7078`; CI standard 4/4 nel [run 43116473760](https://github.com/jeckodevelopment/jd_learn/actions/runs/43116473760).
- Pacchetto [JD-Learn-0.41.79-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43116473760/artifacts/13899451043), SHA-256 interno `4c54233651a85f33594fcde84d32f7f31d51bcc82d03f63fead3e189fa270843`, wrapper `a33d99e3742305adfe0964f8e878b8804092d4bf3caa7e125f91d5bfeef02eaa` (estrarre una volta; scade il 25 novembre 2026). Accettazione Joomla pendente.
- Collaudo Joomla: vedere [STAB-04 nel testbook](docs/QA_TESTBOOK.md#stab-04--label-compatta-per-i-campi-lezione-04179-dev).

## 0.41.78-dev — traduzione del menu Percorsi (STAB-01 / PR #238)

- Aggiunta `COM_JLMS_PATHWAYS` ai cataloghi di sistema IT/EN; il menu amministrativo mostra la voce tradotta invece della chiave grezza. Controllo strutturale dedicato.
- PR [#238](https://github.com/jeckodevelopment/jd_learn/pull/238) integrata nel commit `6e7d2cb40f8a641e30b391f046b6e4b1d3b09e33`; CI standard 4/4 nel [run 43113528500](https://github.com/jeckodevelopment/jd_learn/actions/runs/43113528500).
- Pacchetto [JD-Learn-0.41.78-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43113528500/artifacts/13897377687), SHA-256 interno `19931c7606ae274ca12291e713bddc09a8c946bed3f83f7d3cad1b48db7b53f1`, wrapper SHA-256 `6a029d5283ef897d62869bc8fb88eaf193eb966bab3d61424a04951cae8c9ec5` (estrarre il wrapper ZIP una volta; scade il 25 novembre 2026).
- Collaudo manuale Joomla IT/EN, dashboard, ACL e stato dei quattro provider ancora da registrare nel testbook.

## 0.41.77-dev — materiali corso nell’anteprima editoriale (IMP-03)

- L’anteprima del corso mostra gli allegati dell’ambito corso anche nelle lezioni di testo e video, come nel contesto di fruizione, mantenendo privati i campioni pubblici e i file non disponibili.
- Il download passa da un endpoint amministrativo protetto dall’autorizzazione editoriale e dalla relazione corso/versione/lezione; materiali associati a una lezione continuano a comparire solo per lezioni Documento/Audio. Nessun progresso o tentativo viene creato.
- Collaudo Joomla richiesto sul sito di prova: vedere [IMP-03 nel testbook](docs/QA_TESTBOOK.md#imp-03--preview-video-in-sola-lettura-04146-dev).
- PR [#235](https://github.com/jeckodevelopment/jd_learn/pull/235) integrata nel commit `dbd277605a7eebd18d5d16515853a5f61a40d3f0`; CI standard 4/4 nel [run 43110571231](https://github.com/jeckodevelopment/jd_learn/actions/runs/43110571231). Pacchetto [JD-Learn-0.41.77-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43110571231/artifacts/13896755810), SHA-256 interno `22f41d76870fb2c5b39fa621d8d9d0e596ccf1a9c19886fe712aeac8d4159cd1`, wrapper ZIP da estrarre una volta, SHA-256 `fcbdd65eac2f49d17a987d5510e39581dd445335e4014393f4a2db370d82f61f`; scadenza 25 novembre 2026. Collaudo manuale Joomla pendente.




## 0.41.76-dev — pubblicazione delle anteprime materiali corso (IMP-06)

- Corretto il controllo di pubblicazione che trattava l'anteprima esplicita degli allegati corso come un materiale non pronto. Ora una bozza può pubblicare allegati corso selezionati come anteprima se pronti, validi e scansionati quando richiesto; l'anteprima degli allegati lezione resta rifiutata.
- Estesa la prova HTTP Joomla con pubblicazione, pagina ospite, consegna pubblica, metodi/ID negati, finestra futura e visibilità del corso revocata.
- Collaudo manuale Joomla richiesto nel sito di prova: vedere [testbook IMP-06](docs/QA_TESTBOOK.md#imp-06--anteprima-pubblica-esplicita-dei-materiali-corso-04176-dev).
- PR [#233](https://github.com/jeckodevelopment/jd_learn/pull/233) integrata nel commit `5b7e7460dfa637b2847d6231f337b830950887df`; CI standard 4/4 nel [run 43106823340](https://github.com/jeckodevelopment/jd_learn/actions/runs/43106823340). Pacchetto [JD-Learn-0.41.76-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43106823340/artifacts/13894937008), wrapper SHA-256 `2a50af30a6bfa349806c38325c0388771e983d9f56495186736375519e077665`; collaudo manuale Joomla pendente.

## 0.41.75-dev — anteprima pubblica esplicita dei materiali corso (IMP-06)

- L'editor può marcare un singolo allegato dell'ambito corso come anteprima pubblica; il default resta privato e il server rifiuta il flag sugli allegati di lezione.
- La scheda corso mostra soltanto anteprime della versione corrente pubblicata, pronte, attive e nel periodo configurato. La consegna revalida corso/catalogo/allegato, usa cache privata/no-store e non indicizza il file.
- Gli asset riutilizzati generano collegamenti privati. Aggiunta la procedura di collaudo Joomla in `docs/QA_TESTBOOK.md`; accettazione manuale ancora pendente.
- PR [#230](https://github.com/jeckodevelopment/jd_learn/pull/230) integrata nel commit `a01c096321769903c15212aa2b0c420e95245041`; CI standard 4/4 nel [run 43103266860](https://github.com/jeckodevelopment/jd_learn/actions/runs/43103266860).
- Pacchetto [JD-Learn-0.41.75-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43103266860/artifacts/13892259551), ZIP interno SHA-256 `1d7f0463ee2438ddb1d74bb98a13c116b85bf8570fd5d44092bd09b198f3349c`; wrapper `0c8a7dab69680ee9461cd35aaf94b45e19ec854cc9529411952695cda0409119`, scadenza 2026-11-25T21:32:33Z.
- Collaudo manuale della preview su Joomla resta pendente.

## 0.41.74-dev — scansione materiali verificata con ClamAV reale (IMP-06)

- PR [#229](https://github.com/jeckodevelopment/jd_learn/pull/229) integrata in main (merge `d02aeceb2402b8b609fbe97a4ad31c38aa7b4b8c`): il job PHP 8.3 installa ClamAV, aggiorna le firme e prova il flusso reale via socket Unix con file pulito ed EICAR. PHP 8.4 mantiene anche il fixture di protocollo.
- File puliti passano a `ready`; EICAR viene rifiutato; socket indisponibile mantiene la quarantena. Il codice non dichiara una certificazione del server o delle definizioni di produzione.
- CI standard 4/4 superata nel [run 43094357303](https://github.com/jeckodevelopment/jd_learn/actions/runs/43094357303).
- Pacchetto [JD-Learn-0.41.74-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43094357303/artifacts/13890232415); wrapper SHA-256 `902cc7909297f38936feddade12020bffec6335a7fd430ffe173b71e172e29c8`, scadenza artifact 2026-11-25T20:16:48Z.
- Accettazione manuale Joomla e configurazione dello scanner sul sito restano pendenti.

## 0.41.73-dev — configurazione Cloudflare Stream coerente con il flusso API (IMP-05)

- PR [#228](https://github.com/jeckodevelopment/jd_learn/pull/228) integrata in main (merge `d677924aca265f3397be156f7298d614bad330f3`): rimossi dal form i controlli di firma locale non implementati; i parametri legacy vengono ignorati e le configurazioni API esistenti restano valide.
- Aggiunto controllo Joomla del manifest e prova di compatibilità dei parametri legacy.
- CI standard 4/4 superata nel [run 43091365354](https://github.com/jeckodevelopment/jd_learn/actions/runs/43091365354).
- Pacchetto [JD-Learn-0.41.73-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43091365354/artifacts/13888777492), ZIP interno SHA-256 `b3f577a3760a5884290a9dcb52ad09a10aaf3fd2d04d521408f1574f31c83172`, wrapper `f3259abb5ce3dad3413da045cbd396967d41c98abd7d756ff82489833276a688`.
- Accettazione Joomla con account Cloudflare e video reali ancora da eseguire.

## 0.41.72-dev — conservazione configurabile degli asset orfani (IMP-06)

- Aggiunge nelle opzioni Materiali una soglia di conservazione configurabile fra 24 e 8760 ore; il default resta 24 ore.
- Il cleanup Scheduler continua a elaborare al massimo 100 asset per esecuzione, verifica tutti i riferimenti e rimuove soltanto file locali non referenziati più vecchi della soglia. Valori fuori intervallo vengono limitati lato server.
- Aggiunge una prova d’integrazione Joomla che configura 48 ore e dimostra che un asset di 96 ore viene rimosso mentre uno di 30 ore e gli allegati referenziati vengono conservati.
- Collaudo manuale Joomla ancora da eseguire: vedere [testbook IMP-06](docs/QA_TESTBOOK.md#imp-06--periodo-configurabile-per-la-pulizia-degli-asset-locali-orfani-04172-dev).

## 0.41.71-dev — streaming video HTML5 compatibile con Apache/LiteSpeed (IMP-05)

- PR [#226](https://github.com/jeckodevelopment/jd_learn/pull/226) integrata in main (merge `1b889ee550a23700071488930b50fbcb0081ad74`): stream MP4 privato con PHP a blocchi, controlli Joomla e supporto Range/HEAD; rimosse le configurazioni Nginx.
- I siti aggiornati con il valore legacy `x_accel` usano il percorso PHP protetto. Le prestazioni concorrenti richiedono benchmark separato.
- CI standard 4/4 superata nel [run 43079973855](https://github.com/jeckodevelopment/jd_learn/actions/runs/43079973855). Pacchetto [JD-Learn-0.41.71-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43079973855/artifacts/13885130510), ZIP SHA-256 `3d9dcca6c943da364e6c6f83d6ac45b44f51a8a7a5d34f532da40150b9e21c8f`, wrapper `4ecf79e8fa6d64cc0171412dbc56b4ea26e7ee0f4a48544348fb4a6817e34d33`. Test Joomla manuale e benchmark concorrente restano aperti; vedere [testbook IMP-05](docs/QA_TESTBOOK.md#imp-05--consegna-html5-locale-compatibile-con-apachelitespeed-04171-dev-pr-226).

## 0.41.70-dev — ricollocazione dipendenze categoria (IMP-07 / CAT-01 / CORE-01)

- Aggiunto nel backend un riepilogo dei riferimenti a corsi, percorsi, profili docente e domande, con ricollocazione esplicita verso categorie autorizzate della stessa sezione.
- Aggiornamento atomico del solo riferimento categoria; versioni didattiche, iscrizioni, progressi e risultati restano invariati. Se Finder è attivo, i corsi spostati vengono reindicizzati.
- Guida e scenari Joomla aggiornati in `docs/CATEGORIES.md` e `docs/QA_TESTBOOK.md`. Il collaudo manuale Joomla resta pendente.

## 0.41.69-dev — ricerca nei selettori delle traduzioni (IMP-07 / CORE-07)

- Aggiunge un filtro accessibile per titolo o ID ai selettori delle associazioni multilingua dei corsi, utile nei gruppi editoriali numerosi.
- Mantiene l’elenco autorizzato, il limite esplicito di 2.000 corsi e la convalida server di lingua, ACL, checkout e appartenenza al gruppo.
- Corregge la guida e le stringhe editoriali: le associazioni dei corsi pubblicati sono modificabili dalla PR #223; programmi, iscrizioni e progressi restano invariati.
- PR [#224](https://github.com/jeckodevelopment/jd_learn/pull/224) integrata in main 0.41.69-dev. Pacchetto SHA-256 `54b8667258a833fcd907b3a9767abbd2d7cadbbd8e01fceecd0dda1c86034450`; controlli documentali e due stack PHP/database CI superati, Chromium ancora bloccato nel setup runtime. Accettazione manuale AC-20/AC-48 pendente.

## 0.41.68-dev — associazioni multilingua su corsi pubblicati (IMP-07 / CORE-07)

- Abilita i selettori nativi delle associazioni per corsi in bozza valida e pubblicati con versione live.
- Consente di collegare, staccare e ripristinare traduzioni senza cambiare versioni didattiche, iscrizioni o progressi; ACL, checkout e coerenza delle lingue restano verificati.
- Aggiorna test automatici, documentazione multilingua e collaudi manuali AC-20/AC-48. Accettazione Joomla manuale pendente.

## 0.41.67-dev — Tag Joomla nativi per i corsi (IMP-07 / CORE-09)

- Registra i corsi come contenuto taggabile Joomla con type alias `com_jlms.course`, content type UCM e mapping dei campi standard.
- Aggiunge il campo Tag nativo nell’editor corso; salva le associazioni, mantiene i tag nella duplicazione e collega le viste `com_tags` ai filtri già disponibili nel catalogo.
- Aggiorna la documentazione e il testbook con i comportamenti manuali per AC-19 e AC-64. Integrata nella PR #222 in 0.41.67-dev. Accettazione Joomla manuale ancora da eseguire.

## 0.41.66-dev — consegna materiali compatibile con Apache/LiteSpeed (IMP-06)

- Rimuove dalla consegna degli allegati la dipendenza opzionale da Nginx introdotta nella PR #219. I materiali privati vengono trasmessi dal componente PHP dopo i controlli Joomla e di integrità; non occorre configurare moduli o location specifiche del web server.
- Aggiorna la documentazione e le prove manuali per verificare download, anteprima, richieste Range/HEAD e revoca dell’accesso in un ambiente Joomla standard.
- PR [#221](https://github.com/jeckodevelopment/jd_learn/pull/221) integrata in 0.41.66-dev; pacchetto ricostruito dopo il merge, SHA-256 `214e311d5cee72963b3e9d01ba958dfa52088b781c52052761e4f1d74ffdc4af`.

## 0.41.64-dev — riuso dei materiali nella bozza (IMP-06)

- La gestione Materiali consente di collegare un asset privato esistente ai materiali del corso o a un’altra lezione della stessa versione in bozza, senza duplicare il file.
- Le associazioni ereditano titolo, descrizione e finestra di disponibilità del sorgente; il riuso ripetuto nello stesso ambito viene rifiutato. Le sostituzioni continuano a creare asset immutabili e le versioni pubblicate restano inalterate.
- PR [#217](https://github.com/jeckodevelopment/jd_learn/pull/217) integrata con squash nel commit `abf82a4053918f823726c84a156d295652c7b72c`, milestone **1.0.0**; CI standard 4/4 nel [run 42983654823](https://github.com/jeckodevelopment/jd_learn/actions/runs/42983654823).
- Pacchetto [JD-Learn-0.41.64-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42983654823/artifacts/13838677604), wrapper SHA-256 `1045619ab6ba9a47d050afc233ae9e8f124b8ede994f5677a9d921f1e1920986`, ZIP Joomla SHA-256 `f8d318f1dea8b68801bda56ac057bfd3df7dc6d0af93b972131e979196b72484`, disponibile fino al 25 novembre 2026. Estrarre il wrapper una volta per ottenere lo ZIP installabile. Collaudo Joomla manuale pendente nel [testbook](docs/QA_TESTBOOK.md#imp-06--lifecycle-materiali-e-riuso-in-bozza-04164-dev).

Warning: truncated output (original token count: 25233)
Total output lines: 839

## 0.41.63-dev — protezione lifecycle categorie native (IMP-07)

- Le FK verso categorie Joomla impediscono la cancellazione permanente di categorie ancora usate da corsi, percorsi, docenti o domande.
- Il cestinamento e il ripristino restano reversibili e non cancellano corsi o storico; la visibilità segue lo stato della categoria.
- PR [#215](https://github.com/jeckodevelopment/jd_learn/pull/215) integrata con squash nel commit `148a4e4c7fe1a814f8a1bd561cecbb0380c7dd39`, milestone **1.0.0**; CI standard 4/4 nel [run 42978021654](https://github.com/jeckodevelopment/jd_learn/actions/runs/42978021654).
- Pacchetto [JD-Learn-0.41.63-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42978021654/artifacts/13835639241), wrapper SHA-256 `dfcfab154b2425a1c862a3680315aecb9c633abdab249c6f7c66af93e0a9feb0`, ZIP Joomla SHA-256 `938a623037eeaba7cdb0c5f70e6aa159f9926e6b84bfbc88e856e7c285b8bdd5`, disponibile fino al 25 novembre 2026. Collaudo Joomla manuale nel [testbook](docs/QA_TESTBOOK.md#imp-07--lifecycle-sicuro-delle-categorie-04163-dev).

## 0.41.62-dev — filtri docente e percorso nel catalogo (IMP-07, PR #213 integrata)

- Aggiunge filtri docente e percorso nel catalogo e nelle viste categoria; opzioni e risultati sono limitati a schede e profili accessibili all’utente.
- Filtri componibili con tag, difficoltà, durata, ricerca, ordine, vista griglia/elenco e paginazione. Gli ID non validi non allargano i risultati.
- PR [#213](https://github.com/jeckodevelopment/jd_learn/pull/213) integrata con squash nel commit `a08e67eb09c0b11279f6a68ee6abf0a0d077be1a`, milestone **1.0.0**; CI 4/4 nel [run 42923290761](https://github.com/jeckodevelopment/jd_learn/actions/runs/42923290761). Pacchetto [JD-Learn-0.41.62-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42923290761/artifacts/13811584820), wrapper SHA-256 `003c56dd360cd8a7c375c220a9b1a0ce0c57e6669913a693c63ea6781f032cf2`, ZIP Joomla SHA-256 `e26a39a0c63c3ba07a9851b49b701582d3df8a656ac55c634cd1414ff9412728`, disponibile fino al 24 novembre 2026. Collaudo Joomla manuale pendente nel [testbook](docs/QA_TESTBOOK.md#imp-07--filtri-docente-e-percorso-nel-catalogo-04162-dev-pr-213-integrata).

## 0.41.60-dev — filtro tag nel catalogo pubblico (IMP-07, PR #209 integrata)

- Aggiunge il filtro per tag Joomla nel catalogo e nelle pagine categoria, combinabile con difficoltà, durata, ricerca e ordinamento.
- Le opzioni mostrano soltanto tag pubblicati/accessibili associati a corsi visibili; il filtro mantiene il ramo categoria e non rivela corsi riservati.
- PR [#209](https://github.com/jeckodevelopment/jd_learn/pull/209) integrata con squash nel commit `66b9488009378ba7b7e7d77204bf33b904028e0e`, milestone **1.0.0**; CI standard 4/4 nel [run 42918303751](https://github.com/jeckodevelopment/jd_learn/actions/runs/42918303751). Pacchetto [JD-Learn-0.41.60-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42918303751/artifacts/13810143951), wrapper SHA-256 `fda316f972196e9990d57e27a0d8be632eeda51afdfaa99bf846aca803d5d994`, disponibile fino al 24 novembre 2026. Il checksum ZIP installabile verificato in CI è registrato nel log del job di build; accettazione Joomla manuale pendente. Testbook aggiornato.

## 0.41.59-dev — filtri core del catalogo (CAT-01, PR #210 integrata)

- Aggiunge filtri pubblici per difficoltà e fasce di durata stimata al catalogo e alla lista corsi delle categorie; ricerca, ordine, categoria e paginazione si combinano.
- I valori inviati sono limitati a opzioni previste; i filtri leggono solo i metadati promozionali del corso.
- PR [#210](https://github.com/jeckodevelopment/jd_learn/pull/210) integrata con squash nel commit `e5d0bae954c099c8522db57c30b1e32882b468ee`; milestone **1.0.0**; CI standard 4/4 nel [run 42913999069](https://github.com/jeckodevelopment/jd_learn/actions/runs/42913999069). Pacchetto [JD-Learn-0.41.59-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42913999069/artifacts/13807843959), wrapper SHA-256 `c1e9e8e7c3e64daebc4c5162f64695c89b0d3d79a9c41019f5eeee087227644c`, ZIP Joomla SHA-256 `f22a8a3fb1c87e17f10f0342e6ba6980e5c8882e83dcfec41bab34713b3c41dc`; scade il 24 novembre 2026. Collaudo manuale Joomla pendente nel [testbook](docs/QA_TESTBOOK.md#imp-07--filtri-di-difficolta-e-durata-del-catalogo).
- Collaudo Joomla: vedere [testbook](docs/QA_TESTBOOK.md#imp-07--filtri-di-difficolta-e-durata-del-catalogo).

## 0.41.58-dev — URL SEF con alias per catalogo e percorsi (IMP-07, PR #208 integrata)

- Genera segmenti Joomla ID-alias per corsi, categorie e percorsi; conserva il parsing degli URL numerici storici e usa query parametrizzate.
- Il canonical delle viste pubbliche indica l’URL SEF con alias o il menu nativo dedicato; gli ID distinguono alias ripetuti tra lingue e contenitori.
- PR [#208](https://github.com/jeckodevelopment/jd_learn/pull/208) integrata con squash nel commit `6b92612254c060c1ef296c0a03661442871ad1cc`; CI standard 4/4 nel [run 42910392500](https://github.com/jeckodevelopment/jd_learn/actions/runs/42910392500).
- Pacchetto installabile [JD-Learn-0.41.58-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42910392500/artifacts/13806778546), wrapper SHA-256 `773fb7cd4e200df599ffd1b858cc5ef60d013c8823780b625024afbb4acec8cb`, ZIP Joomla SHA-256 `7a4d52cdbbd708b523d6d05f29f0b937fcf5094019a6c9e70ab443bd7a28ade3`; scade il 24 novembre 2026. Collaudo Joomla manuale pendente nel [testbook](docs/QA_TESTBOOK.md#imp-07--url-sef-con-alias-per-corsi-categorie-e-percorsi).

## 0.41.57-dev — metadati SEO/social pubblici (IMP-07, PR #207 integrata)

- Aggiunge canonical URL e metadati Open Graph/Twitter per schede corso, categorie e percorsi visibili agli ospiti; per le schede corso usa titolo/descrizione SEO e immagine promozionale locale già configurati.
- Le pagine con accesso catalogo/contenuto non disponibile agli ospiti e le viste percorso autenticate ricevono `noindex, nofollow`; le immagini social puntano a media locali.
- PR [#207](https://github.com/jeckodevelopment/jd_learn/pull/207) integrata con squash nel commit `32215c493fe1d7a2bf99871b5f93087a842563bd`; CI standard 4/4 superata nel [run 42905972598](https://github.com/jeckodevelopment/jd_learn/actions/runs/42905972598).
- Pacchetto installabile [JD-Learn-0.41.57-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42905972598/artifacts/13804558462), checksum wrapper SHA-256 `f0729b8cfe769f5d7f2065522651bc4b56278c6a4fdb2e29adae4e54be2199de`; checksum ZIP Joomla `7e078f93ff421c143e7a7c198911e4d38c5c6208c788c5920080fd5d4d642921`. Il wrapper scade il 24 novembre 2026: estrarlo per ottenere lo ZIP Joomla. Collaudo manuale ancora pendente; procedura nel [testbook](docs/QA_TESTBOOK.md#imp-07--metadati-seo-e-social-pubblici).

## 0.41.56-dev — ricerca e ordinamento del catalogo (IMP-07, PR #206 integrata)

- Aggiunge ricerca case-insensitive su titolo e sommario promozionale, ordinamento per più recenti o titolo, anche nella vista categoria.
- La ricerca usa parametri SQL associati, mantiene il perimetro di accesso e non interroga programma, lezioni o dati personali; i controlli sono tradotti IT/EN e accessibili da tastiera.
- PR [#206](https://github.com/jeckodevelopment/jd_learn/pull/206) integrata nel commit `cc5af168e5b42aff0b30db2e051acecd86806b9c`; CI standard 4/4 superata nel [run 42901417240](https://github.com/jeckodevelopment/jd_learn/actions/runs/42901417240). [Pacchetto installabile JD-Learn-0.41.56-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42901417240/artifacts/13803875500), wrapper SHA-256 `3c3d7d078ae6b3d03e530d81d833bd89babd8c12903580f84f8d8f4b3b526cd2`, scade il 24 novembre 2026. Collaudo Joomla pendente: ricerca titolo/sommario, termine riservato assente, filtro categoria e ordinamenti. Procedura in [testbook](docs/QA_TESTBOOK.md#imp-07--ricerca-e-ordinamento-del-catalogo-04156-dev-pr-206-integrata).

## 0.41.55-dev — Smart Search catalogo (IMP-07, PR #204)

- Integrato il plugin Finder `plg_finder_jlms`: indice limitato a titolo e sommario delle schede pubbliche idonee, con lingua/categorie e aggiornamento al cambio di corso o categoria.
- Esclusi contenuti non pubblicati, programmati, riservati, senza versione attiva o collocati in categorie non pubbliche. Nessun programma, lezione, media, dato personale o campo privato entra nell’indice.
- PR [#204](https://github.com/jeckodevelopment/jd_learn/pull/204) integrata nel commit `5018d895e52727be29cedb31f4f93b5245ef346a`; CI standard 4/4 superata nel [run 42898585659](https://github.com/jeckodevelopment/jd_learn/actions/runs/42898585659).
- Pacchetto installabile [JD-Learn-0.41.55-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42898585659/artifacts/13802930146), wrapper ZIP SHA-256 `48af954a9fa682519e5a6c8121ef97d22b17bd37004f9969e1e8c59c694e66e1`, scade il 24 novembre 2026. Estrarre il wrapper ZIP per ottenere il pacchetto da installare in Joomla.
- Il collaudo manuale Joomla resta pendente; procedura in [testbook](docs/QA_TESTBOOK.md#imp-07--smart-search-del-catalogo-jd-learn-04155-dev-pr-204-integrata).

## Riconciliazione requisiti — 10 novembre 2026 (runtime 0.41.56-dev, merge IMP-07)

- Riconciliati tutti i 210 ID senza alterare descrizioni/scenari/perimetro baseline: implementazione, esecuzione automatica con copertura, accettazione Joomla, perimetro 1.0.0/fase 2, PR/commit e residui separati.
- 14 implementazioni completate nel sottoinsieme core esplicito, 166 parziali, 30 non iniziate; nessuna accettazione integrale. 133 righe core, 40 miste, 37 fase 2. Il 73% deriva dalla media aggiornata delle sette aree (513/7), non dal conteggio delle righe.
- Verificatore e sei regressioni impediscono confusione tra CI, codice e accettazione; riepilogo JSON controllato contro il CSV. Ricostruzione del pacchetto: SHA-256 invariato `2a3b87ab7669e2d41e830b2c1bddb7d41668260278916f85510aac9cf12024a6`.

## 0.41.54-dev — pulizia programmata dei materiali orfani (IMP-06)

- Aggiunta attività Joomla Scheduler `jlms.materials.cleanup`: elimina in batch limitato asset locali senza riferimenti, più vecchi di 24 ore, e i relativi blob privati. I riferimenti a lezioni, allegati, attestati e job sono esclusi; le sostituzioni immutabili restano conservate.
- L’attività è disponibile nel plugin `plg_task_jlms`, ma va pianificata dall’amministratore. Non viene creata automaticamente all’installazione.
- PR [#202] integrata nel commit `fc94b715d265306777471a40e1043c9cec204d1b`; CI standard 4/4 superata nel [run 42886192087](https://github.com/jeckodevelopment/jd_learn/actions/runs/42886192087). Pacchetto [JD-Learn-0.41.54-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42886192087/artifacts/13797460306), SHA-256 artefatto `622501af14e1a9b50a47203c7c641ec0beac1ad7b939589343e68d83274e23b6`, scade il 24 novembre 2026. Test manuale Joomla pendente.

## 0.41.53-dev — pulizia limitata delle sessioni video scadute (IMP-05)

- L’avvio autorizzato di una nuova sessione elimina fino a 250 sessioni video scadute usando l’indice di scadenza; le sessioni attive non vengono toccate. La pulizia è opportunistica e non sostituisce un’attività periodica sul sito inattivo.
- Aggiunta prova d’integrazione Joomla per la rimozione di una riga scaduta. Nessuna modifica a progresso, accessi o provider.
- PR [#198] integrata nel commit `df98bb5cfd8d7056643e786eedc9bab0804d9d53`; CI standard 4/4 superata nel [run 42883180012](https://github.com/jeckodevelopment/jd_learn/actions/runs/42883180012). Pacchetto installabile [JD-Learn-0.41.53-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42883180012/artifacts/13794578948), SHA-256 ZIP `e8ac53685a7b2189f23373b6048a3342987112c0f15d15637a329e67e51a69c7`, artefatto `f30f0475571a2240fabb03eaee7b0280b78d0b8154b6a7a46ce4c976984b5815`; dimensioni rispettive 545.191 e 545.042 byte, scadenza 24 novembre 2026.
- Collaudo manuale Joomla richiesto: avvio di un video dopo la scadenza di una sessione precedente; verificare che il player parta normalmente e che la nuova sessione salvi la posizione.

## 0.41.52-dev — retry del progresso audio (PR #196 integrata)

- Gli errori temporanei di rete, timeout, HTTP 408/429 e 5xx ritentano una sola volta il salvataggio della stessa posizione audio. Gli errori permanenti non generano retry; il punto fallito resta disponibile per il successivo evento del player. Completamento e percentuale non cambiano.
- CI standard 4/4 superata nel [run 42876421523](https://github.com/jeckodevelopment/jd_learn/actions/runs/42876421523). [Pacchetto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/42876421523/artifacts/13793090250).
- Collaudo Joomla pendente: ripresa, pausa/seek, errore transitorio e permanente, nessun completamento automatico. [Testbook](docs/QA_TESTBOOK.md#pr-196-integrata--retry-del-progresso-audio).

## 0.41.52-dev — filtri della banca domande (PR #194 integrata)

- La vista Banca domande mostra i filtri Ricerca, Categoria, Stato e Lingua; consente combinazioni e mostra l’intestazione Azioni/Actions.
- CI standard 4/4 superata nel [run 42873607501](https://github.com/jeckodevelopment/jd_learn/actions/runs/42873607501). [Pacchetto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/42873607501/artifacts/13791570779).
- Collaudo manuale Joomla pendente: filtri singoli e combinati, pulizia, paginazione/ordinamento, intestazione IT/EN e accesso negato ai ruoli non autorizzati. [Testbook](docs/QA_TESTBOOK.md#pr-194-integrata--filtri-della-banca-domande).

## 0.41.52-dev — indice aula comprimibile su smartphone e default fuso globale (PR #190/#192 integrate)

- PR #192 corregge issue #172: il nuovo corso mostra l’opzione “Usa globali” nel campo Timezone nativo; al salvataggio usa il fuso globale IANA del sito. Fusi espliciti, date UTC e corsi esistenti invariati. CI standard 4/4 superata nel [run 42865678167](https://github.com/jeckodevelopment/jd_learn/actions/runs/42865678167); pacchetto installabile [JD-Learn-0.41.52-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42865678167/artifacts/13787890952), checksum e procedura manuale nel testbook. Collaudo Joomla manuale pendente in [testbook](docs/QA_TESTBOOK.md#pr-192--fuso-globale-per-nuovi-corsi).


- Aggiunge in aula un comando tradotto e accessibile per mostrare/nascondere il programma didattico sotto 48rem; desktop mantiene l’indice visibile. Senza JavaScript il programma resta disponibile.
- CI standard 4/4 superata nel [run 42860617351](https://github.com/jeckodevelopment/jd_learn/actions/runs/42860617351); pacchetto [JD-Learn-0.41.52-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42860617351/artifacts/13784608215), SHA-256 interno `278d260dddbdcdda305fff7456943d5ee43a15d042c94e1c3d901fe9f166c332`, wrapper `6214644bc36d91e60cb7da5b9d31f9216e3e53c8492eba1a5ea3ac5fa4962ddd`; collaudo Joomla manuale pendente. Procedura in [testbook](docs/QA_TESTBOOK.md#pr-190--indice-aula-su-smartphone).

## 0.41.51-dev — retry limitato del salvataggio di avvio (PR #187 integrata)

- Aggiunge stato “Salvataggio attività…” e un singolo retry dopo errore di rete/timeout o risposta HTTP 408, 429 o 5xx; timeout per richiesta 8 secondi e pausa 500 ms.
- Riutilizza la stessa chiave di idempotenza nelle due richieste; gli errori di autorizzazione e gli altri 4xx non vengono ritentati. Nessuna modalità offline o retry continuo.
- CI standard 4/4 superata nel [run 42845258898](https://github.com/jeckodevelopment/jd_learn/actions/runs/42845258898); pacchetto [JD-Learn-0.41.51-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42845258898/artifacts/13779340061), SHA-256 interno `1f7563bd4957f66027dbc1d9b925784a087e196a75ad8909d4b78ce9b2455cab`, collaudo Joomla manuale pendente.
- Comportamenti Joomla in [testbook](docs/QA_TESTBOOK.md#pr-187-integrata-retry-del-salvataggio-di-avvio-04151-dev); CI standard 4/4 superata.

## 0.41.50-dev — registrazione dell’avvio lezione (PR #185 integrata)

- L’apertura esplicita di una lezione accessibile registra lo stato “in corso” e la destinazione Riprendi tramite POST con token Joomla. Il GET dell’aula resta senza scritture.
- Nessun completamento, credito o aumento di percentuale viene attribuito; il completamento manuale aggiorna la riga esistente senza duplicarla.
- PR #185 integrata nel commit `de93c1290e89a5048a5cf6e16c0b15f719580322`; CI standard 4/4 superata nel [run 42839639601](https://github.com/jeckodevelopment/jd_learn/actions/runs/42839639601). Pacchetto [JD-Learn-0.41.50-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42839639601/artifacts/13776106013), SHA-256 interno `9534346da253c8f953da826d203b3f3509fab0682b09e7d09736e60180e28e83`, wrapper `461d8dd9c4d8287c15d6dc667821920e25c4c879108f0a3d9a2ac8cea5ec70f6`; collaudo manuale Joomla pendente nel [testbook](docs/QA_TESTBOOK.md#pr-185-integrata--avvio-e-ripresa-della-lezione-04150-dev).

## 0.41.49-dev — soglia video configurabile per lezione (PR #183 integrata)

- Aggiunta una soglia facoltativa per ogni lezione video: campo vuoto eredita quella della versione, valore impostato prevale per la singola lezione.
- La soglia è salvata nei parametri della lezione, inclusa nel confronto del programma, validata al salvataggio/pubblicazione e usata nel calcolo di completamento. Le versioni assegnate restano immutabili.
- CI standard 4/4 superata nel [run 42830117573](https://github.com/jeckodevelopment/jd_learn/actions/runs/42830117573); merge `4ef19dd8b5ebe4d6eddb1f127a5864929b457297`. Pacchetto [JD-Learn-0.41.49-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42830117573/artifacts/13770693036), SHA-256 interno `46db03c8214873bbd2d266f067585458ebec059e3abbaf2fe29d9e38be1635bb`; collaudo manuale Joomla pendente nel [testbook](docs/QA_TESTBOOK.md#pr-183-integrata--soglia-video-per-lezione-04149-dev).

## 0.41.48-dev — apertura inline protetta di PDF e immagini (PR #181 integrata)

- Nell’aula aggiunge **Apri nel browser** per PDF e immagini JPEG/PNG/WebP, mantenendo il download separato. L’endpoint applica nuovamente accesso all’iscrizione, disponibilità e relazioni del materiale e ammette solo i MIME passivi consentiti.
- La visualizzazione resta read-only: non cambia iscrizione, progresso o completamento. Gli altri formati continuano a essere scaricabili ma non sono serviti inline.
- PR #181 integrata nel commit `06ac78d277c8b7656d1cd635c88441ef74eef6be`; CI standard 4/4 superata sul commit `eacca4b25f5489a75481989d51cb242d6cc5f460` nel [run 42807134904](https://github.com/jeckodevelopment/jd_learn/actions/runs/42807134904).
- Pacchetto Actions [JD-Learn-0.41.48-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42807134904/artifacts/13758379615): wrapper ZIP da estrarre una volta; SHA-256 interno `c1cae9dc3f5fe084a61880f8e4ead4df7f84564d7343631a2dc4147f6d0897c5` (539797 byte), wrapper `e879592a674a5f75e8693f9411ff6412683897675a94aa112d8c76baa62a9c0c` (539608 byte), scadenza 24 novembre 2026.
- Collaudo manuale Joomla dettagliato nel [testbook](docs/QA_TESTBOOK.md#imp-04--apertura-protetta-inline-di-pdf-e-immagini-nell-aula-04148-dev); resta pendente.

## 0.41.47-dev — preview editoriale di documenti e audio (PR #179 integrata)

- Estende IMP-03 alle lezioni documento/audio: mostra allegati di corso e lezione pronti, rispetta stato e finestre di pubblicazione e rivalida ACL prima della consegna. PDF, immagini e testo consentiti possono aprirsi inline; altri documenti restano download. MP3/OGG sono riprodotti con il player HTML5 nativo.
- La preview non crea iscrizioni, sessioni, tentativi o progressi. Le interazioni quiz restano fuori da questa preview.
- PR #179 integrata nel commit `1b957f22e5ceeccb7aa5b387d5018011a8ec20db`; CI standard 4/4 superata sul commit `2c7c6b03a33bca5a4c6b65aa214b5da7613c04ca` nel [run 42804753192](https://github.com/jeckodevelopment/jd_learn/actions/runs/42804753192).
- Pacchetto Actions [JD-Learn-0.41.47-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42804753192/artifacts/13758745162): wrapper ZIP da estrarre una volta; SHA-256 interno `cf204d5ac6366e7a509892ad47f5d30841e87d49737f5323f56b0a5a0c90c6d6` (539512 byte), wrapper `e1cb2a2a8b3940d1b53d1974e2b2cc45a25975d5889b7b46aed4018307c7fc19` (539324 byte), scadenza 24 novembre 2026.
- Collaudo manuale Joomla dettagliato nel [testbook](docs/QA_TESTBOOK.md#imp-03--preview-editoriale-di-documenti-e-audio-04147-dev); resta pendente.




## 0.41.46-dev — riproduzione video nella preview editoriale (PR #177 integrata)

- Aggiunge descrittori video in sola lettura nella preview amministrativa, con autorizzazione editoriale rivalidata e consegna HTML5 locale protetta. Il player non crea sessioni, non invia telemetria e non salva progressi o tentativi.
- Aggiorna i messaggi IT/EN per distinguere la riproduzione video dalle attività non simulate (documenti, audio e quiz).
- PR #177 integrata nel commit `b80aead8792b549823538883c3c88080d614ae4f`; CI standard 4/4 superata sul commit `1874616ab2d192b92875071212099481f9fc21c7` nel [run 42800028533](https://github.com/jeckodevelopment/jd_learn/actions/runs/42800028533).\n- Pacchetto Actions [JD-Learn-0.41.46-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42800028533/artifacts/13755751590): wrapper ZIP da estrarre una volta; SHA-256 interno `ccbaff840cf94a5829de32b9efea90b7c83fe0565e77c50f16bf124bbe69f94b` (538172 byte), wrapper `8647a6a99551b19d793d83eedcddb14401c137888df046566f0c980601e70353` (537983 byte), scadenza 24 novembre 2026.\n- Collaudo manuale Joomla richiesto per provider, consenso, revoca ACL e assenza di effetti sullo stato studente; vedi [QA_TESTBOOK.md](docs/QA_TESTBOOK.md).


## 0.41.45-dev — vista del programma didattico allineata alla toolbar Joomla (PR #169 integrata)

- Le azioni principali del programma sono disponibili nella toolbar nativa, con URL contestuali al corso e visibilità condizionata ai permessi già verificati.
- Il pannello laterale separa visivamente moduli, moduli di form e comandi, migliorando spaziatura e leggibilità sui viewport stretti.
- Aggiunta una verifica HTTP per l’azione contestuale di aggiunta modulo. Collaudo manuale Joomla ancora da eseguire; la checklist è in `docs/QA_TESTBOOK.md`.

## 0.41.44-dev — fuso predefinito dei corsi dalla configurazione globale (PR #167 integrata)

- Risolve issue #154: un nuovo corso eredita il fuso impostato nella configurazione globale del sito Joomla. L’autore può scegliere e conservare un fuso diverso; i corsi già esistenti non vengono reinterpretati.
- Aggiunta la prova Joomla d’integrazione sul valore globale e sulla selezione del form. Il collaudo manuale reale resta pendente.
- PR [#167](https://github.com/jeckodevelopment/jd_learn/pull/167) integrata in `43d87a52320dc7e6bbd0d6f3f55371bdacd4c8c7` nella milestone `1.0.0`; CI standard 4/4 superata nel [run 42769089189](https://github.com/jeckodevelopment/jd_learn/actions/runs/42769089189).
- Pacchetto [JD-Learn-0.41.44-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42769089189/artifacts/13740848347), wrapper ZIP da estrarre una volta, checksum interno `e68934ff0b4257d6defd8ed9e28762eb526c9f3a2d0999c315536534658a4458` (534662 byte), checksum wrapper `f4d0ca0bc889bdbe03cd60b8e8b91984ef060d65ab2c66ccd2a901b6165d4796`; scade il 24 novembre 2026.

## 0.41.43-dev — anteprima dei livelli di accesso (PR #162 integrata)

- La preview editoriale consente di simulare viewing access level Joomla e distingue visibilità di catalogo, accesso ai contenuti e requisito combinato. La simulazione è read-only e non modifica identità, iscrizioni, corso o progressi.
- Aggiunte traduzioni IT/EN e verifiche HTTP/integrazione per accessi misti, autorizzazione editor e assenza di mutazioni.
- CI standard 4/4 superata nel run 42712132178; merge `4c87851d427592885ef3a5348da3e1a182de6042`. Pacchetto [JD-Learn-0.41.43-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42712132178/artifacts/13715794795), SHA-256 interno `39797f26dfee8bcfec34c9aa8365c218653e56857c8133808f1892996e2de738`; collaudo manuale Joomla pendente.

## 0.41.42-dev — archiviazione e ripristino corsi (PR #159 integrata)

- Estende il workflow Joomla JD Learn con archiviazione e ripristino del corso pubblicato. L’archivio sparisce dal catalogo e non ammette nuove iscrizioni.
- Versione didattica, iscrizioni esistenti e accesso degli studenti già ammessi sono preservati; il ripristino riporta online la stessa versione.
- Upgrade del workflow idempotente; prove Joomla d’integrazione per tra…13233 tokens truncated…e.admin sul relativo asset. Le modifiche vengono salvate dal controller com_config di Joomla.
- I salvataggi editoriali conservano le regole; grant, deny, ereditarietà, CSRF e scope verificati sul percorso HTTP nativo.

## 0.29.0-dev — descrizione richiesta alla pubblicazione

- Applica il requisito della descrizione estesa alla prima pubblicazione. Rifiuta testo assente o composto da soli spazi e markup vuoto, indicando il campo da completare.
- Le bozze esistenti richiedono il completamento della descrizione. Le versioni già pubblicate e i ritiri/ripubblicazioni conservano i dati congelati.

## 0.28.0-dev — anteprima editoriale

- Il Programma apre l’anteprima della versione selezionata. L’editor può consultare moduli e testo delle lezioni senza iscrizioni, riproduzione video, tentativi o progressi.

## 0.27.2-dev — inizializzazione iframe YouTube

- Crea l’iframe con URL e origine espliciti e attende la navigazione prima di collegare l’SDK. Il referrer dell’iframe contiene solo l’origine del sito.

## 0.27.1-dev — caricamento SDK YouTube

- Ripulisce il caricamento SDK fallito, consente un nuovo tentativo e distingue blocchi CSP, script indisponibile, video non incorporabile e identificazione del sito mancante.

## 0.27.0-dev — player integrato nell’aula

- Il player appare all’apertura della lezione secondo la politica configurata dal gestore. Rimangono i controlli di riproduzione del player.
- La precedente modalità esplicita richiede una scelta nelle opzioni. Sessione, modulo e provider hanno errori distinti.

## 0.26.3-dev — avvio player YouTube

- Attende i metadati di YouTube prima di valutare la durata e registrare progressi. Mostra un messaggio distinto se il server rifiuta l’accesso al player.

## 0.26.2-dev — diagnostica video nel programma

- Stato di configurazione dei quattro provider nel form lezione e accesso alle impostazioni native per operatori autorizzati.
- Messaggi specifici per sorgenti non valide, provider non registrati e rifiuti del servizio Cloudflare Stream.

## 0.26.1-dev — pubblicazione delegata

- Il workflow verifica il permesso di cambio stato sul singolo corso, con ereditarietà dalla categoria. Prova di prima pubblicazione HTTP con un operatore senza permesso globale.

## 0.26.0-dev — validazione editoriale M01/c2

- Limite di 500 caratteri per descrizioni brevi nuove o modificate, senza troncamento dei testi preesistenti.
- Alias duplicati rilevati dopo normalizzazione, con messaggio specifico per categoria e lingua.
- Prove native e HTTP per i limiti, la conservazione dei dati e i messaggi del modulo.

## 0.25.0-dev — permessi editor M01/c1

- Elenco corsi con scope ACL nativo per-record prima di paginazione/totali, cache coerente dopo revoca e nessun filtro basato sul solo autore.
- Nuovo corso consentito anche ai delegati categoriali; selettore Category esteso con scope e conservazione della categoria reale, cestino rifiutato.
- Permessi effettivi, opzioni native autorizzate, checkout altrui in sola lettura ed errori copertina specifici; modalità avvio documentate.
- Nuove prove native/HTTP/reinstall, guide e matrice aggiornate; esiti nella PR. Schema41tabelle/58FK invariato; M01 e integrazioni core ancora parziali.

## 0.24.0-dev — calendario M01/b3

- Campi calendario/fuso nativi: pubblicazione e fruizione, UTC, intervalli e DST senza normalizzazione silenziosa.
- Snapshot delle ultime date alla pubblicazione e assegnazione della finestra alla prima edizione; date pubblicate immutabili, vecchi client/snapshot conservati.
- Scheda con fuso/offset e disponibilità corrente del comando di avvio; politica dei quiz già aperti invariata. Test dominio/Joomla/HTTP/reinstall e guide aggiornati, esiti nella PR del build. Schema41tabelle/58FK invariato.

## 0.23.0-dev — docenti M01/b2

- Profili docente con MVC/Form/Table Joomla, categorie/asset, ACL dedicata e revisioni; bio testuale, immagine/alt, sito e account facoltativo senza gruppi aggiunti.
- Selezione multipla nei corsi in bozza; snapshot ordinato, pubblicazione validata e visibilità corrente/storica su profili e categorie.
- Prove native/HTTP/upgrade e guide/IT-EN/override; esiti nella PR del build. Schema41tabelle/58FK invariato. Calendario, directory pubblica standalone, associazioni multilingua dei docenti e selettore ricercabile avanzato ancora pendenti.

## 0.22.0-dev — copertina e SEO M01/b1

- Media field nativo per copertina pubblica locale, alt obbligatorio o decorazione esplicita; validazione path/MIME/dimensioni e confini filesystem.
- Tab metadati con titolo/descrizione SEO facoltativi, congelati nella versione e applicati alla head nativa soltanto dopo autorizzazione.
- Compatibilità degli snapshot0.21 e test di upgrade reale; schema41tabelle/58FK invariato. Docenti, calendario, provider media remoti e asset versionati ancora pendenti.
- Corretto avvio automatico concorrente: il lock dell’edizione è la prima lettura transazionale, prima dello snapshot MySQL/MariaDB; identità e disponibilità aggiornate dopo l’attesa. Test deterministico con un’iscrizione concorrente inserita durante il blocco.
- Prove servizio/HTTP e regressioni: esiti nella PR del build. Nessuna accettazione completa della fase dedotta dai test.

## 0.21.0-dev — editor/presentazione M01/a

- Schede native editor e nuovi campi di presentazione, filtrati/tipizzati e conservati senza accettare JSON grezzo.
- Snapshot degli ultimi dati alla pubblicazione; scheda autorizzata con nuove regioni CSS. Versioni storiche non modificate e liste leggere.
- Piano M01–M12 più differitoM13, con assegnazione verificata di tutti i210ID; non dichiarazione di completamento.
- Nuove prove di persistenza, autorizzazione, XSS, input invalidi, snapshot e upgrade; risultati da registrare. Schema41tabelle/58FK invariato.

## 0.20.1-dev — stabilizzazione pubblicazione

- Priorità0 per il blocco segnalato nel collaudo utente; import CSV rinviato fino alla verifica del flusso essenziale. Nessuna diagnosi del sito dedotta dalla CI.
- Collegamento programma/pubblicazione dall’editor, istruzioni corrette e diagnostica di versione, permessi, plugin Publishing e transizioni mancanti.
- Nuova prova HTTP di creazione e prima pubblicazione, rifiuto programma vuoto/plugin disabilitato e replay senza duplicare l’edizione. Prima CI41349160121:13asserzioni HTTP per ambiente e suite completa riuscite. Chiarimento successivo della voce nell’elenco corsi nella PR22; riscontro sul sito pendente.
- Schema invariato, quattro provider base conservati, nessun bypass del workflow o dell’immutabilità.

## 0.20.0-dev

- Preassegnazione manuale esplicita senza livelli correnti, con avviso e conferma; default di ammissione invariato.
- Assegnazione distinta dalla fruizione: nessun gruppo aggiunto, stessi controlli runtime, disponibilità editoriale obbligatoria.
- Opt-in vincolato all’anteprima/hash e registrato nel log Joomla; compatibilità degli hash standard0.19 preservata.
- Nessuna modifica allo schema41tabelle/58FK; prove negative, concorrenza, grant/revoca e conservazione superate nella CI41319974680 (37servizio/18HTTP/4conservazione per ambiente). CSV e accettazione completa ancora pendenti.

## 0.19.0-dev

- Iscrizione manuale singola alla versione pubblicata corrente e all’edizione predefinita, con controlli freschi su operatore e destinatario.
- Anteprima/conferma/motivo, CSRF, receipt idempotente e audit core atomico; nessuna assegnazione di gruppi o duplicazione/riattivazione implicita.
- Lock dell’edizione condiviso con Inizia, capienza e subject risolti in transazione; nessuna email automatica.
- Schema additivo41tabelle/58FK; prove runtime/HTTP/concorrenza/audit/upgrade predisposte, esiti effettivi nelle Actions. Import CSV, approvazioni e gestione completa restano pendenti.

## 0.18.0-dev

- Sospensione e riattivazione delle iscrizioni con anteprima aggiornata, motivo/conferma e permessi correnti per corso.
- Eventi append-only e Action Logs atomici; replay idempotenti e lock/CAS condivisi con i writer didattici.
- Progressi, completamenti, risposte e timer conservati; nessuna assegnazione di gruppi o proroga delle finestre.
- Schema additivo40tabelle/57FK; test runtime/HTTP/concorrenza/audit/upgrade predisposti, esiti effettivi nelle Actions. Creazione manuale, annullamenti e gestione completa ancora pendenti.

## 0.17.0-dev

- Elenco amministrativo degli iscritti per corso con permessi nativi dedicati e aggiornati su ogni lettura.
- Filtri versione/utente/stato/avanzamento, paginazione nativa e date UTC; nessuna mutazione o assegnazione di gruppi.
- Stato iscrizione distinto dal completamento; dati minimizzati e nomi escapati, viste native e override documentati.
- Nessuna modifica allo schema; prove ACL/runtime/HTTP/conservazione predisposte, esiti effettivi nelle Actions. Gestione con audit, report completi ed export restano da implementare.

## 0.16.0-dev

- Area personale del solo utente autenticato con storico paginato, filtri nativi e gruppi didattici separati dalla disponibilità corrente.
- Storico e titolo congelato conservati dopo revoca o scadenza; nessuna lezione/risorsa/risposta nel riepilogo.
- Riprendi/Rivedi in lettura con nuova autorizzazione, ultima attività valida e lingua originale, senza iscrizioni o tentativi automatici.
- MVC/menu/form/override e ID CSS nativi; intestazioni private e noindex. Nessuna nuova tabella; test runtime/HTTP/upgrade predisposti, esiti effettivi nelle Actions.
- Attestati, attività dettagliate e gestione amministrativa delle iscrizioni restano da implementare.

## 0.15.0-dev

- Viste pubbliche categorie e categoria con figli/corsi paginati e ID CSS stabili per override nativi.
- Filtro corrente di stato/lingua/accesso su ogni antenato e sezione; nessun conteggio di rami nascosti.
- Tipo nativo course.category nel gestore Associazioni e collegamenti lingua pubblici, menu/Pathway e SEF.
- Nessuna nuova tabella; test lifecycle/HTTP/upgrade predisposti, esiti effettivi nelle Actions. Rimozione con dipendenze e SEO completo restano da implementare.

## 0.14.0-dev

- Associazioni dei corsi in bozza tramite AdminModel, tabella e contratto nativi Joomla; campi per lingua con ACL e checkout dei target.
- Integrazione con gestore Associazioni, Language Filter e mod_languages; schede tradotte filtrate per visibilità corrente, senza trasferire progressi.
- Gruppi pubblicati protetti, input manomessi e appropriazione di collegamenti rifiutati; nessuna modifica allo schema39tabelle/56FK.
- Test runtime, HTTP con pacchetto lingua italiano, routing SEF e conservazione predisposti. Categorie e gestione dei collegamenti pubblicati restano da completare; esiti effettivi nelle Actions.

## 0.13.0-dev

- Proroghe dei tentativi aperti con timer, anteprima/conferma/motivo, autorizzazioni native per corso e Action Logs atomici.
- Deadline effettiva distinta dallo snapshot originario: storia append-only, nessuna modifica alle risposte o alla loro revisione; limite48ore e nessuna riapertura dopo scadenza/consegna.
- Stato studente compatto e sincronizzazione del timer anche senza risposte cambiate; selezioni locali conservate e avvisi riallineati alla proroga.
- Schema additivo39tabelle/56FK. Verifiche runtime/HTTP/client/concorrenza e aggiornamento dalla0.12 predisposti; esiti effettivi nelle Actions.
- Rivalutazioni, attestati/anomalie, integrazioni complete e certificazioni di produzione restano da completare.

## 0.12.0-dev

- Annullamento dei tentativi con anteprima d’impatto, conferma e motivo; restituzione esplicita del consumo e numeri storici mai riutilizzati.
- Evidenze originarie conservate, risultati/completamento ricalcolati sui tentativi validi, annullamenti distinti dallo storico ancora valido.
- Audit Action Logs e ledger nella transazione; anteprime obsolete e replay incompatibili rifiutati. Mutazioni dello studente bloccate sul tentativo annullato.
- Guardie per attestati, revisioni d’esito e storici oltre10.000tentativi; questi casi richiedono flussi dedicati successivi.
- Test runtime/HTTP/concorrenza/upgrade predisposti; esiti effettivi nelle Actions. Nessuna modifica allo schema38tabelle/55FK.

## 0.11.0-dev

- Autorizzazione e ritiro del tempo aggiuntivo per iscrizione/quiz, con backend MVC/form nativi, ACL per corso, motivazione e revisioni append-only.
- Durata individuale congelata all’avvio; tentativi già aperti/conclusi immutabili, anche durante revoche o aumenti successivi. Compatibilità con snapshot0.10.
- Action Logs Joomla con dati minimi e verifica atomica della registrazione; nessun invio email dentro la transazione.
- Avvisi a cinque minuti e a un minuto in regione accessibile separata dal salvataggio. Riepilogo distinto fra tempo attuale e prossimo tentativo.
- Schema additivo:38tabelle/55FK. Collaudi predisposti per aggiornamento0.10→0.11 con tentativi preesistenti, ACL/audit/deadline, HTTP e conservazione. Risultati effettivi nelle Actions della PR.
- Rivalutazioni, proroghe durante un tentativo, privacy completa, carico e certificazione accessibilità restano da completare.

## 0.10.0-dev

- Pubblicazione di quiz con pool congelato e snapshot privati/pubblici per ogni tentativo; estrazione e ordine stabili alla ripresa.
- Avvio condiviso fra schede, salvataggi CAS, UUID idempotenti, consegna e scoring esatto lato server, migliore risultato e completamento una sola volta.
- Timer server, chiusura sulle sole risposte salvate e plugin base Joomla Scheduler `task/jlms`, routine `jlms.quiz.expire` a lotti limitati.
- Viste studenti con override/ID CSS, task JSON con CSRF/accesso corrente e feedback protetto; autosave JavaScript ogni20secondi con retry immutabili.
- Test runtime/HTTP/concorrenza/upgrade predisposti; vedere Actions della PR per l’esito effettivo. Tempi aggiuntivi, rivalutazione, collaudo browser/carico e integrazioni complete restano da implementare.

## 0.9.0-dev

- Banca domande con categorie e asset Joomla, lingua, stato, editor/subform e checkout/checkin nativi.
- Scelta singola, vero/falso e multipla: validazione, chiavi di correzione separate e nuove revisioni immutabili a ogni salvataggio.
- Quiz nelle bozze: composizione per revisioni esplicite, punti, selezione/estrazione configurata, soglia esatta, timer/tentativi/intervallo/feedback e cancellazione con dipendenze.
- ACL su corso e banca, revisione aggregata, confini delle risposte corrette e guide/override aggiornati.
- Tentativi, estrazione runtime, autosave, valutazione, feedback studente e pubblicazione dei quiz restano chiusi fino all'incremento successivo.

## 0.8.0-dev

- HTML5 con upload MP4/H.264/AAC per lezione, durata/codec rilevati da ffprobe e asset privati immutabili.
- Quarantena e rescan con policy antivirus condivisa; nessuna assegnazione arbitraria di asset o sostituzione di video pubblicati.
- Consegna GET/HEAD/Range con accesso Joomla corrente, PHP limitato a64MiB oppure offload Nginx interno, senza fallback pubblico.
- Player nativo con progressi comuni e nessuna dipendenza dal consenso ai provider esterni; nuove regioni admin documentate.
- Verifica HTTP con ffprobe e Nginx reali; browser decoder, carico, altri codec/storage e cleanup ancora da completare.

## 0.7.0-dev

- Cloudflare Stream opt-in in modalità API: segreti server via riferimenti ambiente, verifica remota di privacy/account/dominio/origini e token temporanei senza download.
- Scadenza limitata dalle finestre didattiche note; rinnovo con nuovo controllo Joomla, generazione per sessione e conservazione progressi/posizione.
- Chiamate provider fuori dai lock di scrittura e nuova autorizzazione prima della consegna; nessuna chiamata API durante i salvataggi del progresso.
- Adapter SDK Stream, messaggi di rinnovo e test API/SDK simulati, accesso revocato durante risposta e upgrade dalla0.6.
- Firma locale, HTML5, diagnostica autonoma, cache e collaudo reale provider/carico restano da completare.

## 0.6.0-dev

- Player YouTube/Vimeo opt-in tramite plugin Joomla, SDK ufficiali caricati dopo consenso e grant server.
- Pubblicazione video con durata verificata e snapshot immutabile; aula con nuove regioni CSS e materiali corso/lezione.
- Sessioni, sequenze e replay, copertura unica, budget temporale server, resume concorrente e controllo ACL corrente.
- Test dominio, integrazione Joomla/database, endpoint HTTP e mock del trasporto browser; upgrade da 0.5 con storico conservato.
- Cloudflare Stream/HTML5, metadati remoti, prove SDK reali e test di carico restano da completare.

## 0.5.0-dev

- Materiali di corso/versione e lezione: upload, metadati, ordine, sostituzione immutabile e rimozione del collegamento in bozza.
- Storage fuori webroot, allowlist e controlli MIME/firme/archivi bounded, quarantena e scansione ClamAV opzionale via socket locale.
- Download con accesso corrente, ownership/parent, SHA256, attachment/nosniff/no-store e Range/HEAD; materiali facoltativi senza credito didattico.
- Pubblicazione include hash dei materiali e richiede asset pronti; nuove regioni CSS e override documentati.
- Offload, cleanup, preview e riuso asset ancora da completare.

## 0.4.0-dev

- Iscrizione automatica/ripresa e aula per lezioni di testo, con login nativo Joomla.
- Completamento idempotente e transazioni per iscrizione, contatori obbligatori e data di conclusione separata dallo stato di accesso.
- Identità e livelli aggiornati dal core a ogni richiesta, anche dopo revoca con sessione aperta.
- Test con processi concorrenti, revoca/ripristino, ownership, CSRF e flusso HTTP studente.
- Modalità approvazione, player/materiali, quiz, attestati e integrazioni restanti ancora in sviluppo.

## 0.3.0-dev

- Pubblicazione validata dei corsi di testo tramite workflow e plugin Publishing Joomla; transazione unica, versione immutabile, hash ed edizione iniziale.
- Catalogo paginato e scheda pubblica con programma, filtro categoria/discendenti, autorizzazioni correnti e nuovi ID CSS.
- Test integrativi workflow/veto/immutabilità/visibilità e rendering HTTP; risultati effettivi nella PR.
- Video, materiali, iscrizioni e fruizione studenti ancora in sviluppo.

## 0.2.0-dev — 31 ottobre 2026

- Aggiunti servizio applicativo DI, prima bozza didattica, editor del programma e comandi MVC nativi.
- Implementati moduli e lezioni testo/video, obbligatorietà, modifica, riordino e rimozione con controlli delle dipendenze.
- Controlli centralizzati di ACL, parent, revisione aggregata/elemento e immutabilità delle versioni pubblicate; query e transazioni brevi.
- Integrazione editor/filtro HTML core, provider dal registro estensibile e normalizzazione delle sorgenti.
- Nuovi test integrativi e HTTP del flusso completo. Nessuna pubblicazione o fruizione studenti ancora attiva.

## 0.1.0-dev — 31 ottobre 2026

Primo incremento di sviluppo, non release del sistema e-learning completo.

- Importate le specifiche funzionali, tecniche, sicurezza/provider, servizi/endpoint e matrici di collaudo concordate.
- Aggiunti package Joomla, libreria contratti, componente MVC, schema37tabelle/53FK e installer conservativo con verifiche dello schema trattenuto.
- Implementata gestione dei corsi in bozza con categorie, livelli, lingua, asset ACL, check-in/out e controllo delle revisioni.
- Inclusi i quattro plugin video con registrazione lazy e validazione locale delle sorgenti; riproduzione esplicitamente non disponibile.
- Aggiunte regole dominio iniziali, traduzioni, WAM, prime regioni CSS pubbliche e guida agli override.
- Predisposte build riproducibile e CI PHP8.3/8.4, MySQL8.4/MariaDB10.6, installazione/aggiornamento Joomla, test MVC e rendering HTTP.
- Pubblicazione, fruizione, quiz persistenti, attestati e integrazioni rimanenti restano nello stato documentato in IMPLEMENTATION_STATUS.md.

## Requisiti — 7 novembre 2026: durata video

- Registrata come prossima priorità R-06 l’acquisizione automatica della durata dai provider/file, con inserimento manuale di ripiego e gestione coerente degli scarti player/server.
- Aggiornati piano, limiti attuali, checklist e tracciabilità; runtime/pacchetto0.38 invariati. La funzione esterna completa è ancora da implementare.

## Processo e roadmap — 7 novembre 2026

- PR in bozza con controlli leggeri; integrazioni per il candidato pronto, due coppie PHP/database e Chromium. Matrice completa disponibile manualmente prima di milestone/rilascio o interventi di compatibilità; nessuna rimozione di scenari dalla suite.
- Restyling del backend/ingresso e menu amministrativo JD Learn nella sidebar nativa pianificati in M01/M10, con verifica ACL, installazione/upgrade e navigazione accessibile. Non ancora implementati.
- Versione applicativa e pacchetto0.37 invariati.

## Documentazione — 5 novembre 2026

- Consolidato lo stato attuale per funzionalità; separati piano residuo, perimetro delle fasi e cronologia.
- Allineati matrice, problemi aperti, prove del build e guide alla 0.34.1; distinte funzioni assenti e collaudi pendenti.
- Nessuna modifica del runtime, della versione o dei criteri di accettazione.

## Requisiti funzionali1.3 —4novembre2026

- Recepita esplicitamente la richiesta di aggiornamento dei corsi pubblicati con storico Joomla e gestione semplificata dei docenti, campi nativi e viste di menu.
- Aggiornate specifica funzionale/tecnica, matrice e catalogo con AC-119–125, registro della modifica e criteri residui M01; nessun requisito dichiarato completo per effetto dell’aggiornamento documentale.
- Pacchetto runtime invariato0.31.0-dev; verifiche della PR38 registrate separatamente dai nuovi criteri di accettazione.

## Riallineamento documentale della baseline0.19

- Riepilogo/README aggiornati ai flussi realmente presenti, schema41tabelle/58FK e integrazioni core ancora mancanti.
- Matrice dei210requisiti con descrizione/perimetro/scenari baseline preservati, sorgenti e prove parziali distinti dai criteri residui; nessuna accettazione completa dedotta dalla CI.
- Registro delle differenze aperte (preassegnazione senza livello, provider aggiuntivi, ritiro/erogazione, ID pagina e fallback di ripresa), evidenze del build0.19 e piano esecutivo residuo.
- Note storiche conservate separatamente e controllo di coerenza documentale nella verifica strutturale. Nessuna modifica al codice distribuito o al pacchetto0.19.
## 0.41.58-dev — URL SEF con alias per catalogo e percorsi (IMP-07)

- Genera segmenti Joomla nel formato ID-alias per corsi, categorie e percorsi; mantiene il parsing degli URL numerici esistenti e usa il database Joomla con parametri associati.
- Il canonical delle viste pubbliche risolve al percorso SEF con alias. Restano gli ID per disambiguare alias ripetuti tra lingue e contenitori.
- PR IMP-07 in preparazione per la milestone **1.0.0**. Collaudo Joomla manuale in [testbook](docs/QA_TESTBOOK.md#imp-07--url-sef-con-alias-per-corsi-categorie-e-percorsi).
## 0.41.61-dev — vista elenco del catalogo (IMP-07, PR #211 integrata)

- Consente di passare dalla griglia all’elenco nel catalogo corsi e nella pagina di una categoria; selezione tramite filtro allowlist e mantenuta nell’URL insieme agli altri filtri.
- La vista elenco si adatta agli schermi stretti e usa le stesse schede e gli stessi risultati autorizzati della griglia.
- PR [#211](https://github.com/jeckodevelopment/jd_learn/pull/211) integrata con squash nel commit `ca6fecc685902c60527ea4d74f436f87ce9f4d12`, milestone **1.0.0**; CI standard 4/4 nel [run 42920368304](https://github.com/jeckodevelopment/jd_learn/actions/runs/42920368304). Pacchetto [JD-Learn-0.41.61-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42920368304/artifacts/13811701431), wrapper SHA-256 `e99f8024ae32bc221234262697dad8a196673994cafbcc7a32493c2dce500d12`, ZIP Joomla SHA-256 `cc7df8c79e4e3dc8717337df4d427eb43f1b15b61618ee9233aefd92c8201174`, disponibile fino al 24 novembre 2026. Collaudo Joomla manuale pendente nel [testbook](docs/QA_TESTBOOK.md#imp-07--vista-elenco-del-catalogo-04161-dev).
## 0.41.69-dev — ricerca nei selettori delle traduzioni (IMP-07 / CORE-07)

- Aggiunge un filtro accessibile per titolo o ID ai selettori delle associazioni multilingua dei corsi, utile nei gruppi editoriali numerosi.
- Mantiene l'elenco autorizzato, il limite esplicito di 2.000 corsi e la convalida server di lingua, ACL, checkout e appartenenza al gruppo.
- Corregge la guida e le stringhe editoriali: le associazioni dei corsi pubblicati sono modificabili dalla PR #223; programmi, iscrizioni e progressi restano invariati.
- PR [#224](https://github.com/jeckodevelopment/jd_learn/pull/224) integrata in main 0.41.69-dev. Pacchetto SHA-256 `54b8667258a833fcd907b3a9767abbd2d7cadbbd8e01fceecd0dda1c86034450`; controlli documentali e due stack PHP/database CI superati, Chromium ancora bloccato nel setup runtime. Accettazione manuale AC-20/AC-48 pendente.
# 0.56.5-dev — 2026-11-13

- Avvia CERT-A: preferenze globali e per corso (predefinito disabilitato) e registrazione idempotente dell'attestato sui completamenti persistiti in modalità Apprendimento, anche tramite quiz, revisione quiz e video.
- Il record conserva snapshot di studente, corso/versione, data e modello; non è ancora generato o scaricabile un PDF.
- Integra la conferma email dell'iscrizione amministrativa della PR #299 (merge 561e3a9).


## 0.56.9-dev — PR #313 integrata (0.56.9-dev)

- Aggiunta comunicazione operativa agli iscritti selezionati per corso con anteprima, conferma e test all'operatore.
- Aggiunto job asincrono idempotente, rivalidazione ACL/stato, preferenze globale/per corso e template Joomla com_jlms.course.message.
- CI 4/4 superata, merge 53f5e032ae16da9df963e938cfb449c6c249a933; collaudo manuale Joomla/SMTP pendente; pacchetto artifact 14046839802. vedere [QA_TESTBOOK.md](docs/QA_TESTBOOK.md).


## 0.56.10-dev — PR #315 integrata

- Registra il template nativo Joomla `com_jlms.course.published` per MAIL-11.1, con variabili per contenuti pubblici, immagine e revoca.
- Nessun trigger o invio di annuncio; opt-in, double opt-in, revoca e invio restano da implementare. CI 4/4 nel run 43450891869; artifact 14051706158.


## 0.56.13-dev — MAIL-11.3: revoca via link email (PR #321 integrata)

- Implementa revoca anonima esplicita con token opaco SHA-256, monouso e valido 30 giorni, legato alla specifica versione del consenso.
- La visita GET non revoca; la conferma POST richiede CSRF. La revoca invalida i token paralleli; cancellazione consenso elimina i token via FK.
- Aggiunge view Joomla IT/EN, migrazione schema e verifiche automatizzate del ciclo di vita. Il collegamento al flusso di invio annunci resta nella tranche admin successiva; nessun annuncio viene inviato da questa PR.
- CI standard 4/4 nel [run 43477935433](https://github.com/jeckodevelopment/jd_learn/actions/runs/43477935433); merge `dda543402a036dd7cbc1cbf84a6545c61b4f47fe`.
- Pacchetto [JD-Learn-0.56.13-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43477935433/artifacts/14062899580), SHA-256 ZIP `6af55c08a96d9c3b9ad02add1e3c3d491ecd931e11c5aa9d6ec3555f7447128c`, digest artifact `0e3ec5bd9e067833edf7160e70fe5a3ec5be9f5bfcc73f76fd5552f9a35958a7`.
