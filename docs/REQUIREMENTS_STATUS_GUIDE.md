# Registro canonico dei requisiti JD Learn

La **fonte di verità** del perimetro, dell'implementazione e della tracciabilità è [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv). Il file contiene **244 ID** dopo l'addendum approvato del 14 novembre 2026: 219 righe precedenti, incluse CERT-01–08 e MAIL-11, più **MAIL-01–10**, **DOC-01–10** e i gate **SEC-P1–SEC-P5**, già approvati ma non registrati singolarmente. Le definizioni approvate sono in [MAIL_TEMPLATES_1.0.0_APPROVED.md](MAIL_TEMPLATES_1.0.0_APPROVED.md) e nel [piano 1.0.0](STABLE_1.0.0_PR_PLAN.md). L'addendum è soltanto **additivo**: tutti gli ID e criteri storici restano invariati.

## Stato corrente e interpretazione

I conteggi **calcolati dalle righe**, non aggiornati manualmente, sono in [REQUIREMENTS_RECONCILIATION.json](REQUIREMENTS_RECONCILIATION.json). Al 15 novembre 2026 risultano 198 requisiti parziali, 17 completati a livello implementativo e 29 non iniziati. L'accettazione Joomla è parziale per 7 requisiti e non eseguita per 237; la verifica automatica è superata per 179 e non eseguita per 65. Nessun requisito è stato dichiarato integralmente accettato in Joomla. La stima ponderata sulle nove macroaree è 79,56% (80% arrotondato), secondo [IMPLEMENTATION_EVIDENCE.json](IMPLEMENTATION_EVIDENCE.json); dopo la PR #464 l'area email è al 72% (+2 punti). Conteggi del CSV e stima macroarea sono indicatori distinti.

Le tre dimensioni sono indipendenti:

- **Stato implementazione**: Non iniziata / Parziale / Completata nel sottoinsieme approvato per 1.0.0. Non trasformare automaticamente Parziale in Completata soltanto perché la CI passa.
- **Verifica automatica**: Non eseguita / Superata / Fallita, con copertura ed evidenza specifiche. Una vecchia esecuzione CI non prova il codice appena modificato.
- **Accettazione Joomla**: Non eseguita / Parziale / Superata / Fallita. Superata richiede un report esplicito del testbook ed evidenza associata; non è dedotta da una PR o da un test fixture.

I criteri MAIL, DOC e SEC sono valutati con prudenza: il collegamento a PR integrate non prova da solo ogni criterio. Le verifiche specifiche restano necessarie e l'avanzamento si aggiorna solo quando una modifica implementativa giustifica un incremento della macroarea. La PR #464 ha aggiornato di 2 punti l'area email grazie ai producer di contesto approvati, senza attribuire accettazione Joomla/SMTP. In particolare il requisito MAIL-03 (nuove lezioni/cambi didattici rilevanti) non ha ancora una PR implementativa specifica identificata.

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
