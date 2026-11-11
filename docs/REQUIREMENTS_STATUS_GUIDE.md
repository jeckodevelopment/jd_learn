# Come leggere `REQUIREMENTS_STATUS.csv`

Ricognizione iniziale del **10 novembre 2026** sulla baseline main. Matrice aggiornata dopo PR #229 su main `d02aece` / runtime **0.41.74-dev**; la suite completa con ClamAV reale è verificata, mentre l'accettazione manuale del sito resta distinta. I 210 ID, descrizioni, scenari e perimetri originari restano invariati.

| Dimensione | Conteggio | Significato |
|---|---:|---|
| Implementazione completata | 14 | Nessun residuo di codice identificato nel sottoinsieme core esplicitato |
| Implementazione parziale | 167 | Codice/documentazione presente, con delta o completezza puntuale ancora da riscontrare |
| Implementazione non iniziata | 29 | Percorso specifico non disponibile; uno schema non è una funzione |
| Interamente 1.0.0 | 133 | Criteri baseline inclusi |
| Misto | 40 | Sottoinsieme core e criteri differiti distinti nella stessa riga |
| Interamente fase 2 | 37 | Non entra nei gate/denominatori core |
| Verifica automatica superata, copertura parziale | 169 | Passano le asserzioni della suite citata, non tutti gli scenari baseline |
| Verifica automatica non eseguita | 41 | Nessuna prova automatica specifica registrata; include riscontri solo documentali |
| Accettazione Joomla parziale | 2 | CORE-20/SCR-13: sola dashboard AC-132 confermata dall’utente l’8 novembre |
| Accettazione Joomla non eseguita integralmente | 208 | Nessun esito integrale registrato |
| Requisiti integralmente accettati | 0 | Nessun report completo: “Criteri completi superati” resta No |

I conteggi comprendono anche il codice esistente in fase 2; non sono una percentuale dello sviluppo core. Il [riepilogo verificabile](REQUIREMENTS_RECONCILIATION.json) è confrontato automaticamente con le righe.

## Dimensioni indipendenti

**Stato implementazione** usa Non iniziata / Parziale / Completata. Completata riguarda esclusivamente i **Criteri 1.0.0** dichiarati quando il requisito è misto; non dichiara conclusa la parte di fase 2. Non richiede un collaudo manuale superato. In assenza di riscontro puntuale si mantiene Parziale anche quando non è stata individuata una nuova lacuna runtime concreta: non si inventa lavoro né si deduce completezza dalla sola presenza di un servizio.

**Verifica automatica** usa Non eseguita / Superata / Fallita, con copertura ed esecuzione. Le 169 righe Superata hanno tutte copertura **Parziale**: fonti di test e [CI standard #210](https://github.com/jeckodevelopment/jd_learn/actions/runs/42913999069), sui due stack PHP/database e fixture Chromium. La CI #210 è l’ultima esecuzione della suite completa; la copertura delle righe resta parziale e non costituisce accettazione integrale dei requisiti. Test citato, scenario previsto e scenario integralmente superato sono informazioni diverse.

**Accettazione Joomla** usa Non eseguita / Parziale / Superata / Fallita. La conferma AC-132 non accetta CORE-20 o SCR-13 integralmente e non si estende alle modifiche successive. Procedure/esiti sono nel [testbook](QA_TESTBOOK.md); una CI verde non li trasforma in superati.

**Criteri completi superati** resta il gate integrale: richiede un report nel registro `full_requirement_acceptance` di [IMPLEMENTATION_EVIDENCE.json](IMPLEMENTATION_EVIDENCE.json), oggi vuoto. **Verifica parziale**, conservato, classifica il riscontro CI/documento/nessuno e non è un secondo esito di accettazione.

## Perimetro, PR e residui

Perimetro 1.0.0, Criteri 1.0.0 e Criteri fase 2 applicano [RELEASE_SCOPE.md](RELEASE_SCOPE.md) e [RELEASE_SCOPE_MATRIX.md](RELEASE_SCOPE_MATRIX.md). I trasversali restano obbligatori sui flussi inclusi. AC-131 è la sola eccezione amministrativa alla sospensione quiz. Quinto provider, import/batch, attestati e KPI non diventano gate core. Per CAT-03/SCR-04 non è introdotto un rinvio.

**PR implementazione** raccoglie PR integrate già citate nelle evidenze e PR associate ai commit che modificano le fonti. **Commit riscontrato** identifica l’ultima modifica delle fonti nella baseline: non è necessariamente la PR originaria della funzione, né prova ogni criterio. Una ricostruzione mancante è dichiarata. Le PR #204 Finder e #207 metadati canonical/social sono integrate; CORE-14 è ora parziale per implementazione, mentre la verifica automatica specifica e l’accettazione Joomla restano non eseguite.

**Residui implementativi 1.0.0** indica codice/documentazione da completare oppure un riscontro puntuale ancora necessario. **Verifiche residue 1.0.0** conserva scenari, prove Joomla/provider/browser, benchmark e limiti della precedente evidenza, da applicare al solo core; i riferimenti storici possono citare funzionalità ormai presenti, che non vanno reimplementate. **Residui fase 2** mantiene distinto il lavoro rinviato. **Criteri residui** riepiloga le tre colonne, senza aggiungere un backlog.

## Mantenimento e avanzamento

Ogni incremento aggiorna righe, fonti, PR/commit, esecuzione dei test e limiti, insieme al riepilogo JSON. `tools/check.py` verifica baseline immutata, fonti presenti, stati indipendenti, evidenze e conteggi. `tests/requirements-status.py` impedisce accettazione dedotta dalla CI, completamento con residui di codice e fase 2 trasformata in gate core.

Avanzamento main **77% stimato**: media delle sette aree di [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md), 536/7, fase 2 esclusa. La PR #217 completa il riuso materiali in bozza; la PR #221 corregge la #219 ripristinando consegna PHP compatibile con Apache/LiteSpeed, portando M04 all’89%. La PR #222 aggiunge tag Joomla nativi ai corsi e la #223 associazioni multilingua ai corsi pubblicati; area integrazioni Joomla al 92%. Accettazione Joomla manuale AC-20/AC-48 ancora pendente. I collaudi manuali restano separati. Non si assegna un peso arbitrario del 50% a Parziale e non si usa 14/210 come avanzamento. La settima area comprende preparazione/verifica del rilascio: è una stima storica per aree, non una misura di codice scritto o di accettazione Joomla.
