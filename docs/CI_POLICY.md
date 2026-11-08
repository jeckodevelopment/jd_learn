# CI: verificare il candidato prima del merge

Politica operativa del 7 novembre 2026. Cambia il momento e l’ampiezza della verifica; non elimina casi funzionali dalla suite. La versione applicativa è indicata nello stato corrente.

| Fase | Controlli | Integrazioni |
|---|---|---|
| PR in bozza, anche con più commit | Test della selezione CI, build, struttura, traduzioni e tracciabilità | Non avviate |
| PR di sola documentazione | Gli stessi controlli leggeri, anche sulle guide incluse nel pacchetto | Non avviate |
| Candidato runtime pronto per il merge | Controlli leggeri, intera suite implementata su PHP 8.3/MySQL 8.4 e PHP 8.4/MariaDB 10.6, prova Chromium | Due ambienti più browser |
| Milestone/rilascio o rischio di compatibilità | Avvio manuale con matrix=full: tutte le quattro coppie PHP/database più browser | Quattro ambienti più browser |
| Merge in main | Riutilizzo degli esiti del candidato verificato | Nessuna ripetizione automatica |

## Prevenzione dei difetti ricorrenti

Le cause emerse e la checklist prima di dichiarare pronto un candidato sono raccolte in [INTEGRATION_FAILURE_PREVENTION.md](INTEGRATION_FAILURE_PREVENTION.md). Consultarla quando si modifica accesso, iscrizione, pubblicazione, audit, schema o integrazione con API native Joomla; registrare nuove cause solo dopo averle correlate a una correzione e a una prova.

## Procedura

Aprire la PR in bozza, preparare e raggruppare le modifiche, aggiornare subito la documentazione e controllare localmente quanto disponibile. Marcarla pronta per la revisione solo quando il candidato è completo: l’evento ready_for_review avvia la verifica di integrazione.

Se occorrono altre modifiche di implementazione dopo l’avvio, riportare prima la PR in bozza e raggruppare le correzioni; il passaggio successivo a pronta avvia la nuova verifica necessaria. Anche un push accidentale su una PR già pronta invalida il precedente candidato e viene verificato: non si deve poter considerare valido un esito su codice superato. La concorrenza annulla l’esecuzione obsoleta sullo stesso riferimento.

Prima del merge controllare esiti reali e commit verificato, confronto con il candidato e assenza di cambiamenti rilevanti della base. Se codice, test, build, workflow o base runtime sono cambiati, verificare il nuovo candidato. Una registrazione degli esiti che modifica solo documentazione esterna al pacchetto può riutilizzare le prove precedenti dopo confronto esplicito; indicare sempre il commit realmente provato. Non dichiarare passati i job saltati.

Per avviare la copertura estesa: Actions → JD Learn verification → Run workflow → selezionare il branch/candidato e matrix=full. Il risultato manuale deve riferirsi al codice da integrare e va registrato; il workflow manuale richiede il file presente nel branch predefinito. Una verifica standard non certifica le due coppie incrociate omesse. Nessuna schedulazione notturna implicita.

## Selezione e limiti

`tools/ci_plan.py` classifica come documentazione solo docs/, README.md, CHANGELOG.md, AGENTS.md, LICENSE e component/administrator/docs/. Tutti i percorsi sconosciuti e le modifiche a CI/test/build sono trattati come runtime da verificare. I controlli leggeri costruiscono e controllano comunque il pacchetto. Il filtro è una politica CI, non una protezione sostitutiva dei permessi o del controllo prima del merge.

I cinque job precedenti erano quattro ambienti di compatibilità e una prova browser; non cinque test equivalenti. Nei candidati ordinari restano tre job costosi e un job leggero. L’intera suite implementata rimane nei due ambienti standard; la matrice completa resta disponibile prima dei passaggi di qualità. La prova browser corrente usa una fixture controllata e non sostituisce il collaudo completo Joomla/provider reali.

## Consumi GitHub

Repository verificata pubblica; il workflow usa il runner standard ubuntu-24.04. Il report GitHub espone gross_amount, discount_amount e net_amount: quest’ultimo è il fatturabile dopo gli sconti, inclusi quelli dei runner standard pubblici. GitHub dichiara gratuito il compute dei runner standard sulle repository pubbliche, mentre runner maggiorati e storage hanno regole distinte. Il valore di utilizzo lordo non va confuso con l’importo netto fatturabile; la conversione a repository pubblica non cancella utilizzi precedenti.

Per un riscontro contabile usare Billing → Actions → View details, filtrare repository, periodo e SKU e confrontare utilizzo, sconti e totale netto. Non dedurre un addebito dal solo contatore e non dichiarare assenza di addebiti senza il dettaglio dell’account. Nessun accesso alla fatturazione è stato effettuato in questo incremento.

Fonti ufficiali consultate il 7 novembre 2026: [fatturazione Actions](https://docs.github.com/en/billing/concepts/product-billing/github-actions), [runner standard](https://docs.github.com/en/actions/how-tos/write-workflows/choose-where-workflows-run/choose-the-runner-for-a-job), [eventi PR](https://docs.github.com/en/actions/reference/workflows-and-actions/events-that-trigger-workflows), [campi dei report](https://docs.github.com/en/billing/reference/billing-reports).

## Verifica della politica corrente

[PR #48](https://github.com/jeckodevelopment/jd_learn/pull/48), codice `14b3451fa64e49051890889d8b0a8b49b23fb32d`: [run in bozza](https://github.com/jeckodevelopment/jd_learn/actions/runs/42386414314) con solo il job leggero superato e integrazioni/browser saltati; [run del candidato pronto](https://github.com/jeckodevelopment/jd_learn/actions/runs/42386442389) con due ambienti, Chromium e job leggero superati. Otto test di politica e prove locali della CLI/eventi/output coprono anche la selezione manuale delle quattro coppie. Il nuovo avvio manuale full non è stato eseguito: nessuna nuova certificazione delle coppie omesse viene dedotta da questo incremento. Codice applicativo e checksum del pacchetto0.37 invariati; la precedente matrice completa rimane nel registro delle prove.
