# Calcolo dell'avanzamento per macroarea (baseline 1.0.0)

Questa nota descrive la rappresentazione introdotta dalla PR #683, in aggiunta alla fonte canonica `docs/REQUIREMENTS_STATUS.csv` e all'audit `docs/FUNCTIONAL_CRITERIA_1.0.0.json`.

## Metodologia

- Il perimetro corrente comprende 214 requisiti; ogni requisito viene assegnato a **una sola** delle nove macroaree, attraverso il prefisso del suo ID. L'assegnazione fallisce se un prefisso è sconosciuto o duplicato.
- **Peso della macroarea**: requisiti in scope dell'area diviso 214. I pesi sono informativi e sommati coprono il 100% del perimetro.
- **Sviluppo comprovato**: media delle frazioni dei criteri classificati `implemented` per ciascun requisito dell'area. I criteri `unverifiable` o `unassessed` valgono zero nel minimo supportato dalle evidenze, non sono automaticamente assenti dal codice.
- **Collaudo avviato**: quota dei requisiti dell'area con stato di accettazione Joomla diverso da `Non eseguita`; **accettazione completa**: quota con accettazione `Superata`. Questi dati non derivano dal colore della CI.
- **Lacune** e **non verificabili** sono numeri di criteri, non numeri di requisiti. Un valore non verificabile non va automaticamente interpretato come funzionalità mancante.

Le percentuali per area servono a individuare il lavoro residuo e non a dichiarare la completezza globale della release. Il 54,12% rappresenta un **minimo di implementazione con evidenza** nella baseline corrente; una percentuale funzionale finale rigorosa richiede la risoluzione dei criteri non verificabili. La precedente stima del 79,56% è solo storica e non confrontabile con la nuova metodologia.

## Aggiornamento e controlli

1. Modificare matrice requisiti e criteri solo dopo aver verificato evidenze di implementazione e test pertinenti.
2. Rigenerare gli aggregati con `python tools/functional_audit.py --sync`, quindi eseguire `python tools/functional_audit.py --check`.
3. Verificare la ripartizione delle nove macroaree con `python tools/functional_progress.py`.
4. Controllare che la dashboard usi `implementation_progress.macroareas` e mostri separatamente lo sviluppo, la copertura dei collaudi e l'accettazione.
5. Non attribuire a una PR documentale, alla sola CI verde o al semplice merge un avanzamento funzionale o un collaudo manuale.

Questa è documentazione di governance del progetto, non documentazione di prodotto Joomla. Non introduce nuove funzionalità, non cambia versione o schema e non certifica l'accettazione manuale.
