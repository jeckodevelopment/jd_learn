# Tracciabilità requisiti ↔ Pull Request

Il registro canonico è [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv). **Ogni PR che modifica runtime o packaging deve aggiornarlo prima del merge**: la colonna `PR implementazione` contiene solamente numeri separati da `;`, senza URL o prefisso `#`. La correzione di una regressione registra il requisito interessato ma non aumenta automaticamente la copertura.

Nel corpo della PR indicare ID, scopo, modifiche verificabili e **Comportamenti da testare** in Joomla. Aggiornare separatamente evidenze, residui, CI e stato implementativo soltanto quando effettivamente supportati. L'accettazione Joomla manuale rimane indipendente.

## Controlli CI

1. `python3 scripts/check_requirements_csv.py` valida il formato.
2. `python3 tools/check.py` valida il registro rispetto a baseline approvata, fonti e riepilogo.
3. `python3 tests/pr-requirements-coverage.py` verifica la logica del gate.
4. `python3 scripts/check_pr_requirements_coverage.py --base-ref origin/main --pr NUMERO` è eseguito automaticamente sulle PR: per codice in `component/`, `libraries/`, `plugins/`, `packages/` e tooling di build richiede sia una modifica del CSV sia la nuova associazione dell'effettivo numero PR. Il controllo **non** dimostra la correttezza semantica del requisito, che resta responsabilità di review.

Le PR solo documentali non registrano copertura applicativa fittizia e non aumentano la percentuale globale.

## Riconciliazione storica

La PR #259 aveva normalizzato la sintassi delle associazioni; tale validatore, da solo, non imponeva alle successive PR runtime di aggiornare il CSV. La presente riconciliazione completa gli ID MAIL-01–10 approvati, collega PR recenti omesse e introduce il controllo preventivo per il futuro. Tutti gli ID precedenti restano conservati.
