# Registro canonico dei requisiti JD Learn

La **fonte di verità** del perimetro, dell'implementazione e della tracciabilità è [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv). Il file contiene **244 ID** dopo l'addendum approvato del 14 novembre 2026: 219 righe precedenti, incluse CERT-01–08 e MAIL-11, più **MAIL-01–10**, **DOC-01–10** e i gate **SEC-P1–SEC-P5**, già approvati ma non registrati singolarmente. Le definizioni approvate sono in [MAIL_TEMPLATES_1.0.0_APPROVED.md](MAIL_TEMPLATES_1.0.0_APPROVED.md) e nel [piano 1.0.0](STABLE_1.0.0_PR_PLAN.md). L'addendum è soltanto **additivo**: tutti gli ID e criteri storici restano invariati.

## Stato corrente e interpretazione

I conteggi **calcolati dalle righe**, non aggiornati manualmente, sono in [REQUIREMENTS_RECONCILIATION.json](REQUIREMENTS_RECONCILIATION.json). Al 15 novembre 2026 risultano 198 requisiti parziali, 17 completati a livello implementativo e 29 non iniziati. L'accettazione Joomla è parziale per 7 requisiti e non eseguita per 237; la verifica automatica è superata per 179 e non eseguita per 65. Nessun requisito è stato dichiarato integralmente accettato in Joomla. La stima ponderata sulle nove macroaree è 79,56% (80% arrotondato), secondo [IMPLEMENTATION_EVIDENCE.json](IMPLEMENTATION_EVIDENCE.json); dopo la PR #464 l'area email è al 72% (+2 punti). Conteggi del CSV e stima macroarea sono indicatori distinti.

Le tre dimensioni sono indipendenti:

- **Stato implementazione**: Non iniziata / Parziale / Completata nel sottoinsieme approvato per 1.0.0. Non trasformare automaticamente Parziale in Completata soltanto perché la CI passa.
- **Verifica automatica**: Non eseguita / Superata / Fallita, con copertura ed evidenza specifiche. Una vecchia esecuzione CI non prova il codice appena modificato.
- **Accettazione Joomla**: Non eseguita / Parziale / Superata / Fallita. Superata richiede un report esplicito del testbook ed evidenza associata; non è dedotta da una PR o da un test fixture.

I criteri MAIL, DOC e SEC sono valutati con prudenza: il collegamento a PR integrate non prova da solo ogni criterio. Le verifiche specifiche restano necessarie e l'avanzamento si aggiorna solo quando una modifica implementativa giustifica un incremento della macroarea. La PR #464 ha aggiornato di 2 punti l'area email grazie ai producer di contesto approvati, senza attribuire accettazione Joomla/SMTP. In particolare il requisito MAIL-03 (nuove lezioni/cambi didattici rilevanti) non ha ancora una PR implementativa specifica identificata.

## Indicatori distinti nel riepilogo release

Il [riepilogo progetto/release](RELEASE_DASHBOARD.md) pubblica due dati indipendenti, rigenerati dal registro e verificati da `tools/functional_audit.py --check`:

- **Sviluppo documentato (minimo confermato)**: media a peso uguale per requisito della frazione di criteri con stato `implemented` e riferimenti verificabili. I criteri `missing`, `unverifiable` e `unassessed` non contribuiscono al numeratore; quindi il dato è un limite inferiore documentato, non un’affermazione che ogni elemento restante sia assente dal codice.
- **Collaudo manuale Joomla**: requisiti con accettazione `Parziale`, `Superata` o `Fallita` divisi per requisiti in scope. È copertura di requisiti con esito registrato; il numero di superati integralmente è esposto a parte. `Non eseguita` resta nel denominatore, senza bloccare la registrazione futura degli esiti manuali.
- La percentuale funzionale globale rigorosa continua a non essere pubblicata finché restano criteri non verificabili. La verifica CI non aggiorna mai l’accettazione manuale.

## Manutenzione obbligatoria per ogni PR

1. **Prima del merge**, ogni PR che modifica codice applicativo, plugin, librerie, manifest o packaging deve aggiornare il CSV e aggiungere il **numero effettivo della PR** nella colonna `PR implementazione` delle righe coinvolte. Il numero va inserito una sola volta, separato da `;`, senza `#` o URL.
2. Verificare e aggiornare, quando pertinente, `Stato implementazione`, `Fonti implementazione`, `Commit riscontrato`, `Evidenza e limiti`, `Residui implementativi 1.0.0`, `Verifiche residue 1.0.0`, copertura e prove automatiche; non inventare accettazione Joomla o incremento percentuale.
3. Registrare nella PR gli ID requisiti, il perimetro e la sezione **Comportamenti da testare** con azioni/risultati sul sito Joomla. Le PR puramente documentali non devono dichiarare copertura di codice non modificato.
4. Eseguire `python3 scripts/check_requirements_csv.py`, `python3 tools/check.py` e `python3 tests/pr-requirements-coverage.py`. In GitHub Actions, il gate di piano controlla che **ogni PR runtime** abbia modificato il CSV e aggiunto il proprio numero ad almeno una riga. È una garanzia di **tracciabilità minima**, non di attribuzione semantica corretta: la revisione umana deve comunque confrontare requisiti, codice, test, issue e perimetro.
5. Dopo il merge, aggiornare le evidenze dell'esito CI e il riferimento al pacchetto realmente installabile, senza sovrascrivere o inventare prove precedenti. L'aggiornamento del CSV fatto nella PR soddisfa la tracciabilità prima dell'integrazione; lo stato post-merge può essere documentato separatamente.

Le PR di bug fix e regressione **sono comprese** nella regola se cambiano runtime: registrano il requisito riparato anche quando l'avanzamento funzionale resta invariato. Le PR solo README, changelog, piano, testbook e guide non devono duplicare storico di implementazione nel CSV.

**Provenienza dei commit:** `Commit riscontrato` può riferirsi alla modifica sorgente riscontrata o a uno snapshot di codice/documentazione che contiene il materiale parziale; non è automaticamente il commit originario della funzionalità. Le nuove righe MAIL usano, ove ricostruito, un merge concreto pertinente. Nessun riferimento generico di baseline può essere usato per dedurre il completamento.

## Controlli e limiti

- `scripts/check_requirements_csv.py`: struttura, unicità degli ID e formato delle PR.
- `tools/check_status.py`: confronto matrice/corpus approvato, fonti, stati, conteggi ed evidenze.
- `scripts/check_pr_requirements_coverage.py`: impedisce merge di PR runtime senza una **nuova associazione** del numero PR al CSV. Non può rilevare automaticamente se si è scelto il requisito giusto.
- `docs/QA_TESTBOOK.md`: unico registro dell'accettazione manuale e degli scenari ancora da testare.

È necessario un controllo funzionale puntuale ulteriore delle singole righe, in particolare per MAIL, NOT e integrazioni Joomla. La presente riconciliazione **non dichiara** il completamento di questa attività di accettazione.

## Misura oggettiva di completamento funzionale 1.0.0 (ricognizione progressiva)

**Non utilizzare il 79,56% storico come percentuale verificata dei criteri soddisfatti.** Quel numero resta una precedente stima di macroaree, non deriva dai singoli criteri. La fonte canonica dei requisiti rimane `REQUIREMENTS_STATUS.csv`; l'audit verificabile dei criteri è avviato in `FUNCTIONAL_CRITERIA_1.0.0.json` e validato da `python3 tools/functional_progress.py`.

- Denominatore: solo requisiti in perimetro `1.0.0` o `Misto`, escludendo `Fase 2`; gli elementi misti sono scomposti per la sola parte 1.0.0.
- Ogni requisito approvato viene scomposto in criteri verificabili, identificati univocamente; ciascuno ha stato `implemented`, `missing` o `unassessed` e riferimenti concreti alle prove nel repository. Lo stato `unassessed` è ignoto, non equivale a zero.
- All'interno del requisito la frazione provvisoria è `criteri implementati / criteri totali` (pesi uguali espliciti). Nel totale ciascun requisito avrebbe lo stesso peso: non contano il numero di PR, test o righe di codice. Pesi alternativi richiedono una decisione esplicita prima del calcolo e non possono essere cambiati retroattivamente per aumentare il risultato.
- **Il totale globale non viene pubblicato finché non sono stati scomposti e valutati tutti i requisiti in scope e risolti gli `unassessed`.** Il programma emette `functional_implementation_percent: null` e misura la copertura dell'audit, evitando una falsa precisione.
- Test CI e accettazione umana sono assi separati: una prova automatica può documentare l'implementazione, ma non costituisce accettazione su un sito reale. Non segnare come implementato un criterio per la sola presenza di un test o di una PR; confrontare anche il codice e i limiti.

La prima tranche audit riguarda LCM-03, LCM-04 e LCM-05; le rispettive frazioni sono **provvisorie** e dipendono dalla successiva conferma della scomposizione. Non rappresentano il completamento generale del software. Il restante catalogo richiede una ricognizione puntuale. Versioning SemVer invariato secondo la governance già approvata (issue #160); questa PR di strumenti/documentazione **non cambia il manifest installabile**.

### Seconda tranche: catalogo e progettazione corsi (17/11/2026)

La ricognizione per criteri è stata estesa a CAT-01–04 e AUT-01–05, oltre a LCM-03–05. I 9 nuovi requisiti sono scomposti in **42 criteri**, di cui **31 con evidenza identificata** e **11 ancora da approfondire**. La somma delle due tranche è **12 requisiti su 214 in perimetro**, con **58 criteri** (42 con evidenza e 16 non ancora valutati). Le evidenze indicate sono riferimenti al codice e ai test disponibili, **non attestazioni automatiche di correttezza integrale**: prima di dichiarare definitivo ogni criterio occorre confrontarne l'intero comportamento con la specifica; i requisiti CAT-04 e AUT-04 rimangono espressamente incompleti. Non pubblicare una percentuale di completamento globale su questo campione; l'indicatore resta `null` fino al completamento dell'audit. Non cambia né il SemVer né l'accettazione manuale Joomla.

### Terza tranche: iscrizioni, aula e progresso (17/11/2026)

Audit esteso a ISC-01/02/04/05/06/07/08, STU-01/02/03/04 e PRO-01/02/03/06. Sono **15 nuovi requisiti in perimetro**, articolati in **47 criteri**: 31 con evidenza di codice/test iniziale e 16 da approfondire. I criteri di compiti/attività della fase 2 non sono inclusi nel denominatore 1.0.0; gli stati `unassessed` non sono implicitamente né implementati né mancanti. Totale ricognizione: **27/214 requisiti** con 105 criteri (73 con evidenza e 32 da approfondire). Ogni classificazione iniziale richiede riesame semantico rispetto a comportamento e specifiche: la sola esistenza di un file o test non ne certifica la copertura. Percentuale globale ancora non calcolabile e nessun collaudo manuale attribuito. SemVer invariato.

### Quarta tranche: lezioni, video e materiali (17/11/2026)

Ricognizione estesa a LES-01–04, VID-01–08 e MAT-01–08: **20 requisiti aggiuntivi, 68 criteri**, dei quali **44 associati a prime evidenze di implementazione** e **24 ancora da approfondire**. Totale audit avviato: **47/214 requisiti 1.0.0, 173 criteri censiti**, di cui **117 con evidenza iniziale** e **56 `unassessed`**. L'evidenza iniziale identifica file e prove da confrontare con la specifica, non costituisce accettazione Joomla o certificazione completa dei comportamenti. In particolare rimangono aperti: combinazioni di permessi e versioni, consenso e browser reali, scenari di revoca token, versioni dei materiali e storage/hosting. Il completamento funzionale globale resta `null`, senza modifica SemVer. L'esistenza di una documentazione di ambito (LES-04, VID-07) non dimostra di per sé la correttezza di tutte le integrazioni operative.

### Quinta tranche: notifiche, attestati core e integrazione Joomla (17/11/2026)

L'audit è esteso a **26 requisiti**: `NOT-01/02`, `CERT-01–08`, `MAIL-01–11`, `JOO-01–05`. Sono **62 nuovi criteri**, **30** con prima evidenza di codice/test e **32 `unassessed`**. Il registro progressivo copre **73/214 requisiti** della baseline 1.0.0 con **235 criteri** (147 con evidenze iniziali, 88 ancora da approfondire). I requisiti `ATT-01–05` appartengono alla fase 2 e non entrano nell'indicatore 1.0.0; gli attestati core `CERT` invece sono in perimetro. L'associazione di un file a un criterio non ne prova automaticamente la corretta implementazione. Rimangono da confermare in particolare invio SMTP reale, ACL di email e PDF, idempotenza dei replay, persistenza degli attestati, aggiornamenti e backup Joomla. Il totale funzionale resta `null` fino a una ricognizione completa; il 79,56% resta stima storica. Nessuna modifica al manifest SemVer né alcun collaudo manuale attribuito.

### Sesta tranche: sicurezza, privacy, configurazione e integrazione core (17/11/2026)

La ricognizione è estesa a **34 requisiti**: `SIC-01/02`, `PRI-01–03`, `CFG-01–06` e `CORE-01–23`, suddivisi in **75 criteri** (36 associati a prime evidenze; 39 ancora `unassessed`). Il registro comprende ora **107/214 requisiti 1.0.0** e **310 criteri** (183 con evidenze iniziali, 127 da approfondire). I riferimenti ai file e ai test indicano candidati da riesaminare nel merito; non certificano automaticamente ciascun criterio. Restano prioritari la verifica ACL per oggetto, la matrice completa di consenso e privacy, i workflow Joomla, il contenuto storico, il rilascio e gli upgrade. Per un completamento funzionale globale attendibile occorre audit completo e validazione semantica: `functional_implementation_percent` rimane `null`, senza modifiche a SemVer o runtime.

### Settima tranche: UI, accessibilità, video progress e prestazioni (18/11/2026)

Aggiunti 22 requisiti (STU-05, PRO-04/05, ACC-01, UI-01–07, PERF-01–11) scomposti in 49 criteri: **20 con prime evidenze individuate** e **29 `unassessed`**. Il registro copre ora **129/214 requisiti** in perimetro, con **359 criteri** (203 con evidenze iniziali, 156 ancora da approfondire). La presenza di codice relativo a paginazione, cache e player **non dimostra** prestazioni o conformità WCAG: in assenza di benchmark p95, test carico e audit accessibilità, i criteri pertinenti rimangono `unassessed`. Gli aspetti espressamente di fase 2 non vengono accreditati alla 1.0.0. I riferimenti iniziali sono da validare nel merito; la percentuale funzionale globale resta `null` e il 79,56% resta una stima storica. SemVer invariato.

## Audit per criterio della 1.0.0

Il [report corrente](FUNCTIONAL_AUDIT_CURRENT.md) e la colonna
`Audit funzionale 1.0.0 per criterio` sono generati dal
[registro dei criteri](FUNCTIONAL_CRITERIA_1.0.0.json). Le fonti già presenti
nella matrice sono piste di ricerca. La classificazione richiede una revisione
puntuale del comportamento, con motivazione e riferimenti al codice o ai test.

- `python3 tools/functional_audit.py --collect 75` elenca un lotto di criteri da esaminare.
- `python3 tools/functional_audit.py --apply percorso.json` applica una lista
  di decisioni con i campi `requirement`, `criterion`, `status`, `reason`
  e `references`. Ogni riferimento ha forma `percorso#simbolo` oppure
  `percorso#L10-L20`.
- `python3 tools/functional_audit.py --check` controlla riferimenti e coerenza dei file generati.
- `python3 tools/functional_progress.py` calcola le frazioni per requisito.

Usare `implemented` per l'implementazione riscontrata, `missing` per una lacuna
accertata e `unverifiable` quando la revisione individua una prova ulteriore
necessaria. `unassessed` indica un criterio ancora da esaminare. Il punteggio
globale resta nullo finché ci sono criteri senza esito determinato. Il report
riporta le lacune e le prove residue. La matrice conserva i campi dedicati
alle evidenze automatiche e all'accettazione manuale Joomla.
