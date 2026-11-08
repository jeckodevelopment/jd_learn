# Dashboard amministrativa JD Learn

La voce radice **JD Learn** apre la dashboard del componente. I riquadri sono collegamenti di sola navigazione alle gestioni Joomla esistenti: Corsi, Categorie corsi, Docenti, Banca domande, Campi aggiuntivi e Plugin video. Ogni riquadro usa un'icona e viene mostrato solo quando l'utente ha il permesso Joomla corrispondente. I permessi delle destinazioni continuano a essere applicati dai componenti nativi e da JD Learn.

I provider video mantengono il riepilogo espandibile già presente. La dashboard non aggiunge statistiche, attestati, report o nuovi flussi amministrativi: tali funzioni restano fuori da questa implementazione e seguono la pianificazione di fase 2.

## Collaudo manuale

Su Joomla 6 di prova, con account autorizzato e account con permessi limitati:

1. Aprire **Componenti → JD Learn**. Atteso: appare la dashboard, non l'elenco corsi.
2. Verificare che i sei riquadri mostrino icone, etichette tradotte e destinazioni Corsi, Categorie corsi, Docenti, Banca domande, Campi aggiuntivi e Plugin video.
3. Usare l'account limitato: i collegamenti non autorizzati non devono apparire e l'accesso diretto alla destinazione deve restare negato da Joomla.
4. Verificare l'uso da tastiera, layout a finestra stretta e stato espandibile dei provider video.
5. Cambiare lingua amministrativa fra italiano e inglese e verificare tutte le etichette.

Registrare ogni esito in `docs/QA_TESTBOOK.md`, sezione issue #105.
