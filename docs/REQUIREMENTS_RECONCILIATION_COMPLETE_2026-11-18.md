# Riconciliazione requisiti JD Learn 1.0.0 — inventario completo (18/11/2026)

## Risultato della tranche 9

I **73 requisiti 1.0.0** rimasti senza scomposizione sono stati inseriti in `docs/FUNCTIONAL_CRITERIA_1.0.0.json`. Il registro ora contiene criteri per **214/214 requisiti in scope** (su 249 righe totali, 35 interamente in fase 2). Il registro comprende **757 criteri**, inclusi quelli delle tranche precedenti.

La presente modifica chiude **soltanto l'inventario dei criteri**, non l'audit semantico di ogni singolo criterio e non l'accettazione funzionale. I nuovi criteri sono intenzionalmente `unassessed`, senza evidenze inventate. Le funzioni e i vincoli di fase 2 menzionati nelle descrizioni baseline non costituiscono accettazione 1.0.0: prima di dichiarare `implemented` o `missing`, occorre controllare la colonna `Criteri 1.0.0` e i residui nel CSV. I criteri delle tranche precedenti non sono stati rivalutati in questa PR.

## Governo della metrica

La nuova guardia in `tests/functional-progress.py` esige la copertura completa del perimetro 1.0.0 ma lascia `functional_implementation_percent = null` e `progress_status = not_yet_measurable` fino a quando ogni criterio non ha avuto verifica semantica ed evidenza, senza `unassessed`. La stima macroarea 79,56% è storica, **non** va pubblicata come misura riconciliata. Mai contare il numero di PR, gli esiti CI o il mero numero di requisiti scomposti come implementazione.

## Cosa resta

1. Verificare individualmente 757 criteri sul codice, sui test e sulla documentazione, dando prova per ciascuno implementato; marcare `missing` soltanto dopo verifica del gap effettivo.
2. Risolvere ambiguità e perimetro misto mediante la matrice ufficiale `REQUIREMENTS_STATUS.csv`: eventuali criteri di fase 2 devono essere esclusi dal denominatore 1.0.0 con un'estensione esplicita dello schema prima di calcolare la percentuale.
3. Aggiornare di conseguenza CSV, piano PR, sintesi implementativa e dashboard. Tenere CI e collaudi reali Joomla su assi separati.
4. Prima del rilascio: chiudere i gate di qualità, sicurezza/privacy, installazione/upgrade e accettazione effettiva.

## Comportamenti da testare

- JSON valido, 214 ID in scope univoci, criteri con ID univoci e descrizioni.
- Perimetro fase 2 escluso, nessun credito funzionale attribuito ai nuovi criteri senza prova.
- `functional_implementation_percent` ancora `null`; invariabilità delle evidenze e dei risultati precedenti.
- Test completo di struttura e regressione progetto; nessun collaudo Joomla manuale richiesto da una PR documentale.

Nessun riferimento ad ambienti o soggetti di prova nella documentazione.
