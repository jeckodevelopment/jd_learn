# Prossimi passi verso JD Learn 1.0.0

Aggiornato al 14 novembre 2026. Baseline **main 0.56.19-dev**, PR [#346](https://github.com/jeckodevelopment/jd_learn/pull/346), merge `9fab60856369491da3fb88b06b3534d0e332368b`. CI superata nel [run 43531772475](https://github.com/jeckodevelopment/jd_learn/actions/runs/43531772475) (4 job), validatore CSV nel [run 43531772466](https://github.com/jeckodevelopment/jd_learn/actions/runs/43531772466). Pacchetto installabile [JD-Learn-0.56.19-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43531772475/artifacts/14087084624), digest SHA-256 dell'artefatto `809ca9a7c827c526758d1794e355ee7f7c3583de51e2130a5e908de6a6e3aff3`; disponibile fino al 28 novembre 2026 alle 00:10 UTC.

**Avanzamento implementativo stimato:** 78,67% globale (79% arrotondato), email 64%, attestati 75%. La PR #346 riorganizza l'accesso amministrativo e non giustifica da sola un aumento della copertura implementativa. L’accettazione manuale Joomla/SMTP resta pendente e distinta dalla CI.

**PR #346 / dashboard amministrativa integrata:** raggruppamento dei collegamenti in Didattica, Persone e valutazione, Comunicazioni e Configurazione; aggiunto l'accesso ai Mail Templates Joomla con filtro iniziale `com_jlms`. I test manuali di ACL, filtro, layout responsive, tastiera e traduzioni sono nel corpo della PR e restano da eseguire sul sito Joomla di prova. Issue #308 resta parzialmente coperta e #312 non è risolta.

**PR #344 / MAIL-04 integrata:** promemoria facoltativo una tantum prima della scadenza per iscritti attivi non completati; soglia globale 1–30 giorni (default 3), funzione globale disattivata per default, override per corso. Scheduler/outbox Joomla con idempotenza e verifica dello stato dell’iscrizione prima dell’invio.

**Prossime priorità:** completare i residui approvati di MAIL-03/MAIL-02 nel blocco IMP-09-MAIL-D; registrare sul sito Joomla i collaudi manuali dashboard, template e notifiche nel [testbook](QA_TESTBOOK.md). Poi proseguire con DOC-P1–DOC-P5 e SEC-P1–SEC-P5 previsti come gate obbligatori prima della 1.0.0.

Riferimenti: [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md), [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv), [MAIL_TEMPLATES.md](MAIL_TEMPLATES.md), [RELEASE_SCOPE.md](RELEASE_SCOPE.md) e [RELEASE_SCOPE_MATRIX.md](RELEASE_SCOPE_MATRIX.md).
