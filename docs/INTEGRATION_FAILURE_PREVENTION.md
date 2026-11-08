# Prevenire i fallimenti ricorrenti della CI

Aggiornato l’8 novembre 2026 sulla base delle PR #62, #64, #65 e #70 e dei relativi run Actions. Questo registro raccoglie cause e controlli preventivi; per la selezione dei job e la procedura di merge fa fede [CI_POLICY.md](CI_POLICY.md).

## Cosa mostrano le evidenze

| Incremento | Evidenza osservata | Lezione operativa |
|---|---|---|
| PR #70 — controlli del corso | I primi candidati runtime hanno fallito entrambi gli ambienti DB nei test di accesso/iscrizione e di ammissione manuale/audit. I commit correttivi successivi hanno separato il blocco delle nuove ammissioni dall’accesso degli iscritti e corretto il passaggio dell’attore al servizio di audit. La CI standard finale è passata sul commit integrato. | Un controllo di disponibilità va applicato all’operazione corretta, non indistintamente a tutte le azioni del corso. Verificare ruoli e attore registrato anche attraverso il percorso HTTP/Controller, non solo nel servizio isolato. |
| PR #62 — ripresa lezione | I primi candidati hanno fallito i test su storico del dashboard, accesso corrente e scelta di sola lettura per “Riprendi”; la correzione ha selezionato la prima attività obbligatoria incompleta quando manca una ripresa valida. | Tradurre la regola funzionale in una tabella di stati limite e coprire dati assenti, storici o non più accessibili. |
| PR #64 — ritiro dal catalogo | I candidati iniziali hanno fallito integrazione su telemetria video/accesso corrente e installazione del pacchetto Joomla. | Ogni nuova regola d’accesso va controllata su tutte le azioni già avviate e sul pacchetto installato/aggiornato, non solo sulla pagina modificata. |
| PR #65 — campi nativi | Più candidati hanno fallito le verifiche sull’impatto della pubblicazione e sui campi personalizzati nativi, in entrambi i database. | Riprodurre il comportamento Joomla nativo e verificare l’intero ciclo lettura/modifica/pubblicazione; un test mock o solo di struttura non basta a provare l’integrazione Joomla. |
| PR #70 — molti run ravvicinati | Nella sequenza Actions compaiono run falliti, riusciti e cancellati mentre il candidato veniva modificato e rimesso in verifica. Alcuni run leggeri riusciti non contenevano le integrazioni; non sono prove equivalenti al candidato runtime completo. | Contare e interpretare gli esiti per commit e job. Cancellazioni e job saltati non sono né fallimenti applicativi né successi. Raggruppare le correzioni prima di riavviare la matrice. |

Evidenze: [PR #70](https://github.com/jeckodevelopment/jd_learn/pull/70), [run integrato PR #70](https://github.com/jeckodevelopment/jd_learn/actions/runs/42428496835), [PR #62](https://github.com/jeckodevelopment/jd_learn/pull/62), [PR #64](https://github.com/jeckodevelopment/jd_learn/pull/64), [PR #65](https://github.com/jeckodevelopment/jd_learn/pull/65). I nomi dei job documentano l’area che ha fallito; le lezioni qui sopra sono ricavate anche dai commit correttivi e dai casi di regressione aggiunti. Un run fallito su entrambi i database indica con buona probabilità una causa condivisa nell’applicazione o nel setup comune; non dimostra da solo quale sia la causa.

## Checklist prima di scrivere il codice

1. **Rendere verificabile il requisito.** Scrivere prima criteri di accettazione e casi positivi, negativi e di confine. Per modifiche a stati/accesso, elencare separatamente catalogo, ammissioni, utenti già iscritti, aula, progressi e operazioni didattiche.
2. **Mappare ogni ingresso.** Cercare tutti i percorsi che possono raggiungere l’operazione: HTTP, form amministrativo, azioni del roster, iscrizione automatica/manuale, preassegnazione, import, retry e comandi interni. Centralizzare la regola di dominio o documentare perché un percorso è escluso.
3. **Verificare identità e autorizzazione end-to-end.** Controllare ACL, proprietario/docente/studente, accesso ricalcolato al momento dell’azione, token CSRF, actor ID e propagazione dei parametri fino all’audit. Per audit/transizioni, verificare esito e rollback nella stessa transazione.
4. **Scrivere prima o insieme il test di regressione.** Il test deve fallire sul comportamento precedente e passare con la correzione. Preferire l’HTTP contro Joomla e la base dati reale della suite quando il difetto attraversa Controller, modello, ACL, schema o serializzazione. Aggiungere un test unitario solo se verifica un invariante distinto.
5. **Controllare schema e installabilità.** Se cambiano tabelle o manifest, verificare SQL pulito, aggiornamento dalla release precedente, installazione/upgrade del pacchetto costruito e corrispondenza tra sorgenti del componente e guide incluse nel pacchetto.
6. **Fare una revisione incrociata della patch.** Confrontare comportamento richiesto, controller/servizi, viste/form, migrazioni, traduzioni, test e guide. Cercare accessi non protetti o involontariamente bloccati, input persi, default diversi tra create/update, e conseguenze su dati storici.
7. **Eseguire i controlli locali disponibili.** Per codice: `python3 tools/check.py`, `python3 tools/build.py`, `git diff --check`, lint PHP e test PHP pertinenti (incluso `php tests/run.php) se l’ambiente li supporta. Se manca PHP/MySQL, annotarlo: i controlli leggeri non sostituiscono la matrice CI.
8. **Tenere la PR in bozza finché il candidato è completo.** Raggruppare implementazione, regressioni e documentazione. Prima di marcarla pronta controllare che i test descrivano il comportamento finale e che la CI sia stata selezionata come runtime; non usare un run leggero della bozza come prova d’integrazione.
9. **Dopo la matrice, congelare il candidato.** Se si modifica codice, test, workflow, build o base rilevante, considerare scaduta la precedente integrazione e lanciare una nuova verifica sul nuovo SHA. Prima del merge controllare esplicitamente i job richiesti, il SHA verificato e che nessun job necessario sia saltato.
10. **Raccogliere gli esiti senza rumore.** Evitare passaggi ripetuti pronto/bozza e push parziali su PR pronta: GitHub annulla run obsoleti e ne avvia di nuovi. Un fallimento va diagnosticato per job, step, test e SHA; le correzioni correlate si raggruppano prima della nuova matrice.

## Criterio per dichiarare un candidato pronto

Per modifiche runtime, la PR può essere presentata per la revisione solo quando:

- ogni comportamento nuovo o corretto ha almeno un test di regressione pertinente;
- sono stati considerati gli ingressi alternativi e gli invarianti di autorizzazione/storico;
- build e controlli statici locali disponibili sono verdi;
- descrizione PR include **Collaudo manuale Joomla** con prerequisiti, passi e risultato atteso;
- il candidato pronto ha esito verde nei due ambienti previsti e nel browser, sullo stesso SHA da integrare; eventuali job saltati sono esplicitati;
- sono aggiornati stato, guida, changelog e mappatura requisiti richiesti dal tipo di cambiamento.

Questa procedura riduce gli errori prevedibili; non garantisce che il primo run sia sempre verde. La CI resta il controllo indipendente e ogni nuovo fallimento va aggiunto a questo registro solo quando è stata identificata una causa riutilizzabile, insieme alla sua prevenzione e all’evidenza.
