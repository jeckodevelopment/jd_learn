# Dashboard amministrativa JD Learn

La voce radice **JD Learn** apre la dashboard del componente. I riquadri sono collegamenti di sola navigazione alle gestioni Joomla esistenti: Corsi, Percorsi formativi, Categorie corsi, Categorie dei percorsi, Banca domande, Docenti, Campi aggiuntivi e Plugin video, in quest’ordine. Percorsi formativi e categorie dei percorsi compaiono solo con `jlms.pathways.manage` (e, per le categorie, i permessi Joomla nativi). Ogni riquadro usa un'icona ed è mostrato solo se l'utente dispone dei permessi richiesti per quella destinazione. In particolare, Banca domande richiede anche `jlms.quiz.manage`; Categorie corsi e Campi aggiuntivi richiedono i permessi nativi e l'accesso a JD Learn. I permessi delle destinazioni sono comunque riapplicati dal componente o da Joomla.

Nel sottomenu laterale di **JD Learn**, la prima voce **Dashboard** riapre la pagina principale; **Corsi** segue subito dopo; **Percorsi formativi** è visibile solo con il permesso dedicato. Il plugin di sistema `plg_system_jlmsmenu` filtra anche il sottomenu nativo per i permessi specifici: Banca domande richiede `jlms.quiz.manage`; Categorie e Campi applicano i permessi Joomla nativi e quelli del contesto JD Learn; Plugin video richiede `core.manage` su `com_plugins`. Le altre voci JD Learn richiedono `core.manage` su `com_jlms`. I controlli d’accesso di Joomla e del componente restano attivi sugli URL diretti. Il plugin viene attivato all’installazione del pacchetto e un aggiornamento conserva l’eventuale scelta di disattivarlo.

Il riepilogo espandibile elenca sempre YouTube, Vimeo, Cloudflare Stream e video locali. Indica per ciascuno se non è installato, è disattivato, non è disponibile, richiede configurazione o è pronto; non mostra credenziali né contatta i provider. La dashboard non aggiunge statistiche, attestati, report o nuovi flussi amministrativi: tali funzioni restano fuori da questa implementazione e seguono la pianificazione di fase 2.

## Collaudo manuale

Su Joomla 6 di prova, con account autorizzato e account con permessi limitati:

1. Aprire **Componenti → JD Learn**. Atteso: appare la dashboard, non l'elenco corsi.
2. Verificare che i riquadri mostrino icone, etichette tradotte e destinazioni Corsi, Categorie corsi, Docenti, Banca domande, Campi aggiuntivi e Plugin video.
3. Verificare nell’ordine Corsi, Percorsi, Categorie corsi, Categorie dei percorsi, Banca domande, Docenti, Campi aggiuntivi e Plugin video. Nel riepilogo provider verificare lo stato di ogni estensione: disattivare temporaneamente un provider, poi riattivarlo e verificarne la configurazione.
4. Usare un account con accesso al componente ma privo di `jlms.quiz.manage`: il riquadro Banca domande non deve apparire e l'URL diretto deve essere negato. Verificare analogamente i permessi per categorie e campi.
4. Verificare l'uso da tastiera, layout a finestra stretta e stato espandibile dei provider video.
6. Cambiare lingua amministrativa fra italiano e inglese e verificare tutte le etichette.
7. Su un sito aggiornato dalla 0.41.23-dev, fare clic sulla voce radice JD Learn: deve aprire la dashboard. Verificare inoltre che i link dei riquadri si aprano senza `&amp;` letterale e che l'introduzione sia pertinente in entrambe le lingue.
8. Nel sottomenu JD Learn verificare che **Dashboard** sia la prima voce, preceda **Corsi** e apra la dashboard.
9. Registrare separatamente le voci del sottomenu visibili all'account limitato: il filtraggio per permesso specifico non è ancora implementato.

Registrare ogni esito in `docs/QA_TESTBOOK.md`, scenario issue #113 / AC-132.
