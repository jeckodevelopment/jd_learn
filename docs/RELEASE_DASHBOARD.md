# Riepilogo progetto/release JD Learn 1.0.0

Generato da `tools/functional_audit.py --sync`; verificato da `--check`. Fonte: `docs/FUNCTIONAL_CRITERIA_1.0.0.json` e `docs/REQUIREMENTS_STATUS.csv`. Il JSON `docs/REQUIREMENTS_RECONCILIATION.json` espone gli stessi indicatori al dashboard di progetto.

## Sviluppo documentato (minimo confermato)

**57.40%** — media a peso uguale della quota di criteri `implemented` documentati per ciascun requisito in scope (214 requisiti).
Il registro contiene 453 criteri implementati con evidenza, 36 lacune accertate, 268 non verificabili e 0 non ancora esaminati. I criteri non verificabili/non esaminati non entrano nel numeratore: il valore è un minimo supportato dalle evidenze, non una stima del codice che potrebbe esistere senza riscontro.

La percentuale funzionale globale rigorosa resta **non determinabile** finché i criteri non verificabili non vengono risolti.

## Avanzamento per macroarea

| Macroarea | Peso | Sviluppo comprovato | Collaudo avviato | Accettati | Lacune | Non verificabili |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Catalogo, frontend e navigazione | 10.75% | 74.41% | 13.04% | 0.00% | 1 | 20 |
| Corsi, versioni e pubblicazione | 9.35% | 70.44% | 0.00% | 0.00% | 0 | 23 |
| Iscrizioni, lezioni e progressi | 8.88% | 70.18% | 5.26% | 0.00% | 0 | 18 |
| Provider e fruizione video | 8.41% | 64.72% | 0.00% | 0.00% | 3 | 21 |
| Materiali, quiz e attestati | 7.94% | 70.59% | 17.65% | 0.00% | 0 | 14 |
| Email e notifiche | 6.07% | 67.31% | 15.38% | 0.00% | 0 | 11 |
| Joomla, ACL e configurazione | 17.76% | 55.48% | 2.63% | 0.00% | 0 | 37 |
| Privacy, sicurezza e compatibilità | 23.83% | 40.65% | 0.00% | 0.00% | 13 | 100 |
| Packaging, documentazione e reporting | 7.01% | 27.22% | 0.00% | 0.00% | 19 | 24 |

I pesi derivano dal numero di requisiti in scope, senza doppio conteggio. Il delta per macroarea richiede una baseline storica calcolata con la stessa metodologia; le vecchie stime non sono confrontabili.

## Collaudo manuale Joomla

**4.67%** — requisiti con almeno un esito registrato (10/214). È copertura per requisito, non percentuale di casi di test superati.
- Parziale / avviato: **10**
- Superato integralmente: **0** (0.00% del perimetro)
- Fallito: **0**
- Non eseguito: **204**

La verifica automatica CI è un asse separato e non modifica il dato di accettazione Joomla. Un esito parziale conta come collaudo avviato; un esito fallito conta nella copertura perché è stato eseguito, ma resta distinto dai superati.
