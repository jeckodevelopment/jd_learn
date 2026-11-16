# FE-12 — Accettazione integrata frontend Joomla 1.0.0

> **Registro di gate.** L'esito di un test automatico non certifica l'accettazione. Nessun caso manuale è considerato eseguito senza data, ambiente, account di prova, risultato e riferimenti alle evidenze. Il presente documento non attribuisce conformità WCAG 2.2 AA, né dichiarazioni su template/override non collaudati.

**Evidenza automatica post-merge:** PR #526 integrata (merge `fe32457f939c32a439d75192f65074b1dc960c24`); [CI 43946500653](https://github.com/jeckodevelopment/jd_learn/actions/runs/43946500653) 4/4 verde; [artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43946500653/artifacts/14264109572) contenente `pkg_jlms-0.57.7-dev.zip`, SHA-256 `2e318ed0ed11870a2bad26e3307dd593e9d626adf790b8390a0980824a2132a2`. **FE-12 non accettato manualmente:** i 10 scenari sono ancora da eseguire. Stima globale di riferimento 79,56% invariata.

## Copertura HTTP aggiuntiva in corso

La tranche FE-12 HTTP estende `tests/http-smoke.py` con rendering effettivo delle tre viste pubbliche `categories`, `pathways`, `teachers` sul Joomla disposable installato in CI. Controlla radici DOM, titolo di pagina, stylesheet WAM e assenza della stringa di prova per contenuti di lezioni protette. Non verifica CSS visuale, accessibilità completa, ACL multi-ruolo o template terzi; i dieci esiti manuali restano pendenti.

## Copertura e limiti

- **Gate strutturale CI:** `python3 tests/frontend-release-gate.py` verifica le viste/menu nativi, traduzioni IT/EN, router, registrazione Web Asset Manager, radici DOM, privacy SEO delle viste personali, alcuni contratti CSS e i test prerequisiti. Rimangono attive le suite Joomla e HTTP già previste dalla CI. Controlli strutturali non dimostrano ACL runtime né correttezza del rendering.
- **Accettazione reale:** richiede verifica indipendente su Joomla, almeno due template (di cui uno con sidebar/moduli), varie larghezze e 200% zoom, tastiera e lettore schermo, IT/EN e livelli di accesso differenti. Il browser automatizzato per la navigazione curriculum usa una fixture controllata e non equivale al collaudo frontend complessivo.
- **Release:** FE-12 non può essere chiuso solo con il merge della PR del gate. Il proprietario del collaudo deve compilare gli esiti, registrare difetti e rinviare a nuove PR le correzioni. Un passaggio P0/P1 fallito blocca l'accettazione; non assegnare il valore "superato" a verifiche non eseguite.

## Matrice operativa di accettazione

| ID | Ambito | Scenario ed esito atteso | Evidenza automatica indicativa | Stato manuale |
|---|---|---|---|---|
| FE12-01 | Menu/SEF/i18n | Tutte le viste pubbliche selezionabili, menu IT/EN, URL SEF on/off e breadcrumb coerenti | frontend-release-gate, associazioni HTTP | Da eseguire |
| FE12-02 | ACL e visibilità | Ospite, iscritto, docente, admin; profili/corsi/percorsi e categorie riservati non esposti con GET o link diretto | Joomla publication, categories, teachers, HTTP | Da eseguire |
| FE12-03 | Configurazione | Catalogo e categoria con parametri globali e override indipendenti; filtro disabilitato non riattivabile via GET | frontend-menu-inheritance, catalog-options | Da eseguire |
| FE12-04 | Responsive | Desktop, tablet, smartphone, 200% zoom, contenitori stretti con moduli laterali; nessun overflow orizzontale | frontend-css-contract, browser fixture parziale | Da eseguire |
| FE12-05 | Accessibilità | Titoli/landmark, contrasto, ordine focus, etichette form, stato annunciato, tastiera, screen reader; target WCAG 2.2 AA | frontend-release-gate solo indizi statici | Da eseguire |
| FE12-06 | Aula e progressi | Player video/audio/testo/quiz, nessun autoplay o progresso tramite GET; errori/ritentativi, revoca sessione | learning-http, video-http, quiz HTTP, progress feedback | Da eseguire |
| FE12-07 | Area personale | Due iscritti isolati; storico archiviato, ripresa vietata, attestato proprio, preferenze e consenso | dashboard-http, certificati, consenso | Da eseguire |
| FE12-08 | Override | Due template e un override per vista senza perdita hook JS, router, filtri, ACL, navigazione, download | frontend-contract-hooks, CSS contract | Da eseguire |
| FE12-09 | Installazione | Installazione e upgrade reali di ZIP, voci menu già salvate, asset e traduzioni corrette | install/upgrade CI | Da eseguire |
| FE12-10 | Privacy e sicurezza | Contenuti privati senza cache/index, link diretti negati, foto locali, nessun dato account privato docente | test HTTP e FE-09 statici | Da eseguire |

## Protocollo e registrazione

Per ogni caso: **data**, versione Joomla/PHP, template, versione e SHA ZIP, utente/ruolo, lingua, viewport/zoom, **pass/fail**, riferimento screenshot o log, issue/regressione e PR di correzione. Non usare `pass` per prove non riproducibili. Le verifiche WCAG richiedono ispezione manuale oltre ai test automatici.

**Stato iniziale:** tutte le dieci righe manuali sono **Da eseguire**. Implementazione FE-01–FE-11 e CI restano evidenze distinte e non costituiscono accettazione FE-12.
