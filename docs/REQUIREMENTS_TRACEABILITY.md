# Tracciabilità requisiti → Pull Request

Il registro dei requisiti è [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv). La colonna **PR implementazione** contiene soltanto numeri di Pull Request, nell'ordine registrato, separati dal punto e virgola, per esempio `209;210;211;213`. Non contiene URL, simboli `#`, commenti o testo descrittivo. Il campo vuoto significa **PR non ricostruita o non ancora associata** e **non** dimostra che il requisito non sia implementato. Non attribuire una PR sulla base della sola presenza di codice o di un commit.

La matrice distingue esplicitamente **implementazione**, **copertura automatica** e **accettazione Joomla manuale**. La sola PR o CI superata non prova l'accettazione manuale. Le PR che riguardano più requisiti possono comparire in più righe. Le correzioni successive possono essere aggiunte alla colonna, senza cancellare la storia precedente.

## Regola per ogni nuova PR

1. Nel corpo della PR indicare gli **ID requisiti** interessati, l'ambito effettivo e la sezione **Comportamenti da testare** (prove Joomla concrete, oppure «non applicabile» per PR solo documentali).
2. Aggiornare la colonna `PR implementazione` dei requisiti interessati prima del merge, aggiungendo l'ID della PR; aggiornare separatamente stato, evidenze, limiti e residui soltanto quando giustificato.
3. Eseguire `python3 scripts/check_requirements_csv.py` e verificare la GitHub Action **Requirements CSV integrity**. Il controllo verifica struttura, ID, unicità e formato dei numeri PR; **non** certifica l'esistenza delle PR su GitHub né la completezza semantica della mappatura.
4. Non modificare la percentuale di avanzamento funzionale per un intervento soltanto documentale.

## Ripristino storico #259

L'issue #259 normalizza gli URL preesistenti in soli ID e corregge le celle CSV prive di quoting nelle righe VID-03, CORE-22, EDGE-02 e MAT-04. I riferimenti non ricostruibili restano vuoti. L'integrità sintattica non equivale a una verifica dell'effettiva relazione semantica tra PR e requisito: questa rimane una revisione distinta.
