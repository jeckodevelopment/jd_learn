# Prossimi passi verso JD Learn 1.0.0

Aggiornato al 13 novembre 2026. Baseline **main 0.56.19-dev**, PR [#344](https://github.com/jeckodevelopment/jd_learn/pull/344), merge `0fc688bfdbcdacb99616378b69cac30260eef5fb`. CI standard 4/4: [run 43527480797](https://github.com/jeckodevelopment/jd_learn/actions/runs/43527480797). Pacchetto installabile [JD-Learn-0.56.19-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43527480797/artifacts/14086560667), SHA-256 del pacchetto interno `764d47952c513b554329689f136d90b747081d9128711f44e98452611d942021`; wrapper ZIP SHA-256 `03cfde440b0241b42e6465b0e8e2781d16459202fe9c7a62ca18637199e9193f`, da estrarre una volta. Artefatto disponibile fino al 27 novembre 2026 alle 23:32 UTC.

**Avanzamento implementativo stimato:** 78,67% globale (79% arrotondato), email 64%, attestati 75%. Calcolo: media non ponderata delle nove macroaree; MAIL-04 porta email dal 61% al 64%. L’accettazione manuale Joomla/SMTP resta pendente e distinta dalla CI.

**PR #344 / MAIL-04 integrata:** promemoria facoltativo una tantum prima della scadenza per iscritti attivi non completati; soglia globale 1–30 giorni (default 3), funzione globale disattivata per default, override per corso. Scheduler/outbox Joomla con idempotenza e verifica dello stato dell’iscrizione prima dell’invio.

**Prossime priorità:** completare i residui ammessi dal [piano PR](STABLE_1.0.0_PR_PLAN.md), in particolare gli eventi MAIL-03 e le relative preferenze approvate; registrare i collaudi manuali di MAIL-04 e degli altri scenari email nel [testbook](QA_TESTBOOK.md), poi chiudere i gate di stabilizzazione.

Riferimenti: [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md), [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv), [MAIL_TEMPLATES.md](MAIL_TEMPLATES.md), [RELEASE_SCOPE.md](RELEASE_SCOPE.md) e [RELEASE_SCOPE_MATRIX.md](RELEASE_SCOPE_MATRIX.md).
