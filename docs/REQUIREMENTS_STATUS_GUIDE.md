# Registro canonico dei requisiti JD Learn

La **fonte di verità** del perimetro, dell'implementazione e della tracciabilità è [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv). Il file contiene **229 ID** dopo l'addendum approvato del 14 novembre 2026: 219 righe precedenti, incluse CERT-01–08 e MAIL-11, più **MAIL-01–10**, che erano state approvate ma non registrate singolarmente. Le definizioni approvate sono in [MAIL_TEMPLATES_1.0.0_APPROVED.md](MAIL_TEMPLATES_1.0.0_APPROVED.md) e nel [piano 1.0.0](STABLE_1.0.0_PR_PLAN.md). L'addendum è soltanto **additivo**: tutti gli ID e criteri storici restano invariati.

## Stato corrente e interpretazione

I conteggi **calcolati dalle righe**, non aggiornati manualmente, sono in [REQUIREMENTS_RECONCILIATION.json](REQUIREMENTS_RECONCILIATION.json). La riconciliazione iniziale registra 187 requisiti parziali, 17 completati a livello implementativo e 25 non iniziati. Nessun requisito è stato dichiarato integralmente accettato in Joomla. L'aggiunta delle righe non modifica da sola la stima pesata ~79% delle nove macroaree; occorre un ricalcolo rigoroso degli incrementi applicativi e dei relativi pesi.

Le tre dimensioni sono indipendenti:

- **Stato implementazione**: Non iniziata / Parziale / Completata nel sottoinsieme approvato per 1.0.0. Non trasformare automaticamente Parziale in Completata soltanto perché la CI passa.
- **Verifica automatica**: Non eseguita / Superata / Fallita, con copertura ed evidenza specifiche. Una vecchia esecuzione CI non prova il codice appena modificato.
- **Accettazione Joomla**: Non eseguita / Parziale / Superata / Fallita. Superata richiede un report esplicito del testbook ed evidenza associata; non è dedotta da una PR o da un test fixture.

Gli incrementi MAIL-01–10 sono stati inizializzati in modo prudente: il collegamento a PR già integrate non prova da solo ogni criterio. Le verifiche specifiche sono da effettuare e le nuove righe non incrementano la percentuale. Il requisito MAIL-03 (nuove lezioni/cambi didattici rilevanti) non ha ancora una PR implementativa specifica identificata.

## Manutenzione obbligatoria per ogni PR

1. **Prima del merge**, ogni PR che modifica codice applicativo, plugin, librerie, manifest o packaging deve aggiornare il CSV e aggiungere il **numero effettivo della PR** nella colonna `PR implementazione` delle righe coinvolte. Il numero va inserito una sola volta, separato da `;`, senza `#` o URL.
2. Verificare e aggiornare, quando pertinente, `Stato implementazione`, `Fonti implementazione`, `Commit riscontrato`, `Evidenza e limiti`, `Residui implementativi 1.0.0`, `Verifiche residue 1.0.0`, copertura e prove automatiche; non inventare accettazione Joomla o incremento percentuale.
3. Registrare nella PR gli ID requisiti, il perimetro e la sezione **Comportamenti da testare** con azioni/risultati sul sito Joomla. Le PR puramente documentali non devono dichiarare copertura di codice non modificato.
4. Eseguire `python3 scripts/check_requirements_csv.py`, `python3 tools/check.py` e `python3 tests/pr-requirements-coverage.py`. In GitHub Actions, il gate di piano controlla che **ogni PR runtime** abbia modificato il CSV e aggiunto il proprio numero ad almeno una riga. È una garanzia di **tracciabilità minima**, non di attribuzione semantica corretta: la revisione umana deve comunque confrontare requisiti, codice, test, issue e perimetro.
5. Dopo il merge, aggiornare le evidenze dell'esito CI e il riferimento al pacchetto realmente installabile, senza sovrascrivere o inventare prove precedenti. L'aggiornamento del CSV fatto nella PR soddisfa la tracciabilità prima dell'integrazione; lo stato post-merge può essere documentato separatamente.

Le PR di bug fix e regressione **sono comprese** nella regola se cambiano runtime: registrano il requisito riparato anche quando l'avanzamento funzionale resta invariato. Le PR solo README, changelog, piano, testbook e guide non devono duplicare storico di implementazione nel CSV.

## Controlli e limiti

- `scripts/check_requirements_csv.py`: struttura, unicità degli ID e formato delle PR.
- `tools/check_status.py`: confronto matrice/corpus approvato, fonti, stati, conteggi ed evidenze.
- `scripts/check_pr_requirements_coverage.py`: impedisce merge di PR runtime senza una **nuova associazione** del numero PR al CSV. Non può rilevare automaticamente se si è scelto il requisito giusto.
- `docs/QA_TESTBOOK.md`: unico registro dell'accettazione manuale e degli scenari ancora da testare.

È necessario un controllo funzionale puntuale ulteriore delle singole righe, in particolare per MAIL, NOT e integrazioni Joomla. La presente riconciliazione **non dichiara** il completamento di questa attività di accettazione.
