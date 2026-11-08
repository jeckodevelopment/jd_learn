# Come leggere `REQUIREMENTS_STATUS.csv`

La matrice contiene **210 righe requisito**: 209 con perimetro “Rilascio 1” e una differita. All’8 novembre 2026 risultano **174 “In corso”, 36 “Da implementare”, 0 “Completato”**. “Criteri completi superati” è “No” per tutte le righe.

Questo non significa che non sia stato sviluppato nulla. “In corso” raccoglie requisiti con implementazione parziale e/o criteri residui: la colonna “Evidenza e limiti” descrive ciò che è già coperto e “Criteri residui” ciò che manca. “Da implementare” indica che il percorso richiesto non è disponibile o non è ancora stato avviato. La colonna “Verifica parziale” descrive l’evidenza disponibile (per esempio CI), non l’accettazione completa.

Un requisito può diventare “Completato” solo dopo aver soddisfatto **tutti** i criteri inclusi nel perimetro e registrato l’evidenza richiesta, comprese le prove manuali previste. Perciò una funzione presente, un test automatizzato superato o una PR integrata non bastano da soli. I requisiti misti si valutano sul sottoinsieme della prima release indicato in [RELEASE_SCOPE_MATRIX.md](RELEASE_SCOPE_MATRIX.md); le parti di fase 2 non sono gate della prima stabile.

`tools/check.py` controlla la struttura e la tracciabilità del CSV; non certifica la completezza funzionale. `docs/IMPLEMENTATION_EVIDENCE.json` contiene l’indice delle evidenze di build/CI e delle accettazioni integrali. Attualmente il registro delle accettazioni integrali è vuoto, da cui il conteggio pari a zero. Non si cambia lo stato per inferenza: ogni aggiornamento deve citare build, prova e limiti aggiornati.

Per le prove manuali su Joomla si usa il [testbook](QA_TESTBOOK.md), che registra per ogni PR percorso, precondizioni, risultato atteso ed esito. Gli esiti non ancora eseguiti restano “Da eseguire”; una CI verde non li trasforma in superati.
