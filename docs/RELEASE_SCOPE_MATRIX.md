# Mappatura del perimetro della prima versione stabile

Riferimento decisionale: [RELEASE_SCOPE.md](RELEASE_SCOPE.md). Questa mappa applica il perimetro approvato alla specifica funzionale 1.5 e alla matrice requisiti/test. Mantiene gli ID e il testo baseline; quando un requisito contiene più funzionalità, specifica il sottoinsieme incluso e rinvia gli altri criteri. Le righe indicate come parziali non sono accettate finché gli scenari non sono eseguiti sul sottoinsieme esplicitato.

## Inclusi nella prima versione stabile

| Area | Requisiti e scenari | Perimetro incluso |
|---|---|---|
| Gestione e aggiornamento corsi | AUT-01, AUT-02, AUT-04, AUT-05, SCR-13; AC-20, AC-48, AC-53, AC-70, AC-80, AC-86, AC-96, AC-119–127 | Creazione/pubblicazione, modifica ordinaria dei corsi pubblicati, revisione editoriale Joomla, aggiornamento del programma in nuova versione, lezioni/materiali, associazione docente e gestione amministrativa intuitiva. Il flusso non deve richiedere di ritirare o ricreare il corso per modificarlo. |
| Lezioni, completamento e progressi | AUT-03 (solo contenuti inclusi), LES-01–04, PRO-01–06 (solo attività non quiz), SCR-06–08; AC-02, AC-05, AC-07, AC-08, AC-30, AC-36, AC-37, AC-43–44, AC-52, AC-66–67, AC-79, AC-81, AC-89, AC-91, AC-95 | Lezioni testo/video e materiali, attività obbligatorie, regole di completamento supportate, sequenza configurata, protezione degli accessi e progressi affidabili. La validazione non deve imporre quiz o attestati a un corso che non li usa. Rettifiche/reset amministrativi dei progressi e i criteri quiz-specifici sono fuori dal perimetro core. |
| Provider video e materiali | VID-01–08, PVD-01–06, MAT-01–08, PBASE-01–04, SCR-08; AC-25, AC-27–31, AC-91, AC-93, AC-95, AC-97–103, AC-107, AC-109, AC-117–118 | YouTube, Vimeo, Cloudflare Stream e media locali previsti; player incorporato, consenso, autorizzazioni, durata/progresso e materiali essenziali. Il collaudo deve coprire provider e browser reali autorizzati, non solo fixture o mock. |
| Catalogo, scheda corso e area studente | CAT-01, CAT-02, CAT-04, STU-01–04, SCR-01–08; AC-03, AC-16, AC-26, AC-32, AC-41–44, AC-52, AC-60, AC-69, AC-79, AC-85, AC-89–91, AC-93 | Scoperta e consultazione autorizzata dei corsi, docenti, aula, navigazione, ripresa e progressi. Restano applicabili i criteri di privacy e accesso. Per CAT-03 e SCR-04 non viene introdotto qui un nuovo rinvio: restano nella baseline finché non saranno oggetto di decisione esplicita. |
| Iscrizioni e roster essenziali | ISC-01 (avvio automatico e singola gestione manuale; escluso import), ISC-02 (attiva/sospesa e riattivazione), ISC-04–08, LIFE-01–03, LIFE-09, REP-02 (parziale), SCR-14 (parziale); AC-02, AC-11, AC-12, AC-25–26, AC-40, AC-53–54, AC-60, AC-63, nuovo AC-128 | Ammissione singola, sospensione/riattivazione e controlli di accesso. L’elenco per corso mostra stato, avanzamento e date operative di iscrizione/attività, con permessi coerenti. Nessun esito quiz, conteggio attestati, KPI aggregato, operazione massiva o esportazione è necessario per il roster core. |
| Requisiti trasversali e rilascio | Requisiti applicabili di M10–M12: privacy/Joomla, sicurezza, ACL, accessibilità, UI, prestazioni, integrità dati e installazione/upgrade; scenari della baseline relativi ai flussi inclusi | Restano obbligatori per tutte le funzioni incluse. Un requisito trasversale non passa alla fase 2 perché una funzione opzionale è rinviata. Il collaudo di rilascio verifica i flussi inclusi e l’upgrade con dati esistenti. |

## Rinviati alla fase 2

| Area | Requisiti e scenari | Trattamento |
|---|---|---|
| Quiz e banca domande | QUI-01–08, SCR-09–11; AC-06–07, AC-45–46, AC-51, AC-59, AC-80–81, AC-90 per i criteri quiz | Sviluppo funzionale congelato. Il codice già presente può restare installato; si correggono solo blocchi, vulnerabilità o rischi d’integrità. Il rilascio core non deve richiedere un quiz. |
| Attestati | ATT-01–05, SCR-12; AC-09, AC-47, AC-83, AC-87, AC-94 | Nessun requisito di emissione, modello, download, verifica o revoca nella prima stabile. Nascondere o rendere indisponibili i flussi non operativi. |
| KPI e report avanzati | REP-01, REP-03–05; porzioni analitiche di REP-02/SCR-14 e LIFE-06; AC-56, AC-88 e parte reportistica di AC-94 | Rinviare dashboard, analisi per coorte, export CSV e dettaglio di quiz/attestati. Mantenere il roster operativo ristretto descritto sopra e il controllo accessi AC-12. |
| Iscrizioni massive e ciclo avanzato | Sottoinsieme import di ISC-01, ISC-03, LIFE-04–08, DAT-06, NOT-01–02 e operazioni massive di SCR-14/SCR-15; AC-55, AC-57–58, AC-74, AC-92 | Rinviare import/export CSV, batch, approvazioni articolate, edizioni/finestre, proroghe e notifiche affidabili. Non rinviare iscrizione singola, sospensione/riattivazione, revoca accesso o conservazione dello storico. |
| Rettifica/reset dei progressi | PRO-07; AC-23 e AC-58 per il reset/rivalutazione | Rinviare i comandi amministrativi non essenziali; conservare le garanzie di audit e integrità su ogni dato modificato dal sistema. |

## Scenario specifico del roster core

AC-128 è aggiunto al [catalogo dei test](specifications/Catalogo_test_LMS_1.0.csv) per verificare la parte essenziale di REP-02 e SCR-14: un operatore autorizzato consulta il roster del corso assegnato con stato iscrizione, avanzamento e date operative; può compiere le sole azioni singole autorizzate; non riceve dati quiz/attestati, KPI o export. L'accesso a un corso fuori ambito resta negato secondo AC-12. Il test è pianificato, non ancora eseguito.

## Regola per gli scenari misti e lo stato

Un test baseline che combina funzioni core e di fase 2 va separato in asserzioni verificabili: la parte core resta condizione della release; la parte differita viene tracciata come fase 2 e non blocca l'accettazione core. Gli scenari esistenti non sono cancellati né marcati superati per effetto di questa decisione. Il catalogo e `REQUIREMENTS_STATUS.csv` devono continuare a distinguere copertura, esecuzione e limiti del build. Le assegnazioni storiche delle milestone in `RELEASE_PLAN.json` non sostituiscono questa mappa di perimetro.
