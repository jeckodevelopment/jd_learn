# Riconciliazione requisiti 1.0.0 — tranche 8 (18 novembre 2026)

## Ambito

Questa tranche scompone 12 ulteriori requisiti di rilascio in 51 criteri atomici nel registro `FUNCTIONAL_CRITERIA_1.0.0.json`. Le aree sono: QUI-02, REP-02, ACL-DET-01/04, STA-01/03 e SCR-01/03.

## Metodo e limiti

Tutti i 51 nuovi criteri hanno stato `unassessed`: sono **domande verificabili**, non evidenze di funzionalità sviluppate, non risultati dei test e non accettazioni Joomla. La classificazione `implemented` è possibile soltanto dopo riscontro puntuale di codice, test ed evidenze. Il perimetro effettivo 1.0.0 deve essere determinato dal CSV canonico: elementi di fase 2 presenti nelle descrizioni originali non costituiscono gate della prima stabile. Nessuna modifica è stata eseguita su runtime, CSV, test, SemVer o percentuale funzionale.

La scomposizione copre ora **141/214 requisiti**; **73** restano da analizzare. Il numero di requisiti scomposti misura il lavoro di audit, **non** la copertura implementativa. La stima storica 79,56% resta non riconciliata e non viene promossa a percentuale ufficiale.

## Passi successivi

1. Verificare i 51 criteri su codice e test, associando evidenze precise e residuali; non attribuire stati `implemented` per sola vicinanza a funzioni esistenti.
2. Continuare la scomposizione dei 73 requisiti residui, privilegiando schermate, lifecycle e casi limite.
3. Solo dopo verifica semantica completa, calcolare un progresso funzionale per requisito, con criteri pubblicati e gestione separata dei test e dell'accettazione manuale.

## Comportamenti da testare

Per questa PR documentale: validità JSON e unicità degli identificativi di requisito/criterio; invariabilità degli stati esistenti; coerenza del conteggio (129 + 12 = 141); assenza di dati privati e URL di ambienti di prova. Nessun collaudo Joomla richiesto per questo cambiamento.
