# Dashboard amministrativa JD Learn

La voce radice **JD Learn** apre la dashboard del componente. I riquadri sono collegamenti di sola navigazione alle gestioni Joomla esistenti: Corsi, Categorie corsi, Docenti, Banca domande, Campi aggiuntivi e Plugin video. Ogni riquadro usa un'icona e viene mostrato solo quando l'utente ha il permesso Joomla corrispondente. I permessi delle destinazioni continuano a essere applicati dai componenti nativi e da JD Learn.

Nel sottomenu laterale di **JD Learn**, la prima voce **Dashboard** riapre la pagina principale; **Corsi** segue subito dopo.

I provider video mantengono il riepilogo espandibile già presente. La dashboard non aggiunge statistiche, attestati, report o nuovi flussi amministrativi: tali funzioni restano fuori da questa implementazione e seguono la pianificazione di fase 2.

## Collaudo manuale

Su Joomla 6 di prova, con account autorizzato e account con permessi limitati:

1. Aprire **Componenti → JD Learn**. Atteso: appare la dashboard, non l'elenco corsi.
2. Verificare che i sei riquadri mostrino icone, etichette tradotte e destinazioni Corsi, Categorie corsi, Docenti, Banca domande, Campi aggiuntivi e Plugin video.
3. Usare l'account limitato: i collegamenti non autorizzati non devono apparire e l'accesso diretto alla destinazione deve restare negato da Joomla.
4. Verificare l'uso da tastiera, layout a finestra stretta e stato espandibile dei provider video.
5. Cambiare lingua amministrativa fra italiano e inglese e verificare tutte le etichette.
6. Su un sito aggiornato dalla 0.41.23-dev, fare clic sulla voce radice JD Learn: deve aprire la dashboard. Verificare inoltre che i link dei riquadri si aprano senza `&amp;` letterale e che l'introduzione sia pertinente in entrambe le lingue.
7. Nel sottomenu JD Learn verificare che **Dashboard** sia la prima voce, preceda **Corsi** e apra la dashboard.

Registrare ogni esito in `docs/QA_TESTBOOK.md`, sezione issue #105.
