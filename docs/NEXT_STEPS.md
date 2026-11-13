# Prossimi passi verso JD Learn 1.0.0

Aggiornato al 13 novembre 2026. Baseline **main 0.56.18-dev**, PR [#338](https://github.com/jeckodevelopment/jd_learn/pull/338), merge `5d38e6e47582422be581247de085206abc2c0296`. CI standard 4/4: [run 43522457570](https://github.com/jeckodevelopment/jd_learn/actions/runs/43522457570). Pacchetto installabile [JD-Learn-0.56.18-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43522457570/artifacts/14083597534), SHA-256 ZIP `c59074d3bbe6a4ddb6b79714ca0d2948e04d6bff0d40316a1ac0e15f8087cbc6` (scadenza 27 novembre 2026, 22:53 UTC).

**Avanzamento implementativo della baseline:** 78,33% globale (78% arrotondato), email 61%, attestati 75%. Il collaudo manuale Joomla/SMTP resta distinto dalla CI e non è completato.

**Candidata 0.56.19-dev — MAIL-04:** implementa un promemoria facoltativo e una tantum prima della scadenza per gli iscritti attivi non completati. La soglia è configurabile da 1 a 30 giorni (default 3), la funzione è disattivata globalmente per default, con override per corso. L’invio usa il template nativo Joomla, Scheduler e outbox idempotente; stato dell’iscrizione e scadenza sono ricontrollati prima dell’invio. La candidatura non modifica ancora la percentuale.

Dopo il merge: eseguire/registrare i collaudi MAIL-04 nel [testbook](QA_TESTBOOK.md), aggiornare checksum, versione, percentuale e collegamento al pacchetto dell’esatto commit runtime. Proseguire con MAIL-03 e gli altri residui ammessi dal [piano PR](STABLE_1.0.0_PR_PLAN.md); completare gli scenari MAIL-11 e i gate di stabilizzazione.

Riferimenti: [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md), [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv), [MAIL_TEMPLATES.md](MAIL_TEMPLATES.md), [RELEASE_SCOPE.md](RELEASE_SCOPE.md) e [RELEASE_SCOPE_MATRIX.md](RELEASE_SCOPE_MATRIX.md).
