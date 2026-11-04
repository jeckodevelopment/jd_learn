# Permessi e stato dell’editor —0.30 (M01/e)

L’elenco amministrativo usa le autorizzazioni Joomla di ogni corso, prima di contare e paginare: core.edit oppure core.edit.own del proprietario, con ereditarietà di categoria e negazioni esplicite. È richiesto core.manage per entrare nell’amministrazione; l’identità viene riletta e i diritti aggiornati. La cache nativa di righe/query/totali incorpora l’ambito autorizzato corrente: la stessa istanza non può riutilizzare titoli dopo una revoca. Nessun corpo didattico, biografia o risultato viene caricato per questo filtro.

Per determinare l’ambito vengono lette soltanto coppie id/autore in batch da500, con valutazioni ACL native. Questa prima implementazione privilegia la correttezza della paginazione; il costo complessivo della scansione sul catalogo ampio va misurato nella fase prestazioni, senza attribuirgli un benchmark già superato.

Il comando Nuovo è disponibile anche per chi ha core.create su almeno una categoria corsi, pur senza il permesso globale. Il selettore estende CategoryField nativo: mostra categorie valide per la creazione e mantiene la categoria reale del corso modificabile anche quando non è una nuova destinazione consentita. La categoria corrente viene letta dal record autorizzato, non assunta da un valore POST. Il server continua a verificare lo scope di creazione/spostamento; le categorie nel cestino non sono destinazioni valide.

La scheda Permessi effettivi mostra i diritti correnti di creazione, modifica, cambio stato e configurazione. Il cambio stato richiede anche una versione valida, la transizione del workflow e il plugin Publishing: il riepilogo non dichiara che la pubblicazione sia già disponibile. Programma e pubblicazione mantiene la diagnostica specifica. I livelli di accesso degli studenti restano separati da questi permessi amministrativi.

Chi possiede core.options/core.admin può aprire le Opzioni native del componente dal riepilogo e dalla toolbar di elenco/dashboard. Le ACL ereditate si configurano nel componente e nelle categorie tramite Joomla. Nella0.30 la scheda Permessi dei corsi già salvati include RulesField di Joomla, con la sezione course di access.xml. Serve core.admin sul corso, un asset valido e un corso libero da checkout altrui. Il campo viene mostrato anche sui corsi pubblicati per gestire i diritti correnti. Fields/History e il collaudo CORE rimangono nelle rispettive fasi.

I dati didattici dei corsi già pubblicati o bloccati da un altro operatore sono in sola lettura, con campi disabilitati e senza Salva/Applica. Il messaggio del checkout è distinto dall’immutabilità della versione; la richiesta forgiata resta rifiutata dal modello senza cambiare dati o lock. Il rilascio di blocchi avviene solo attraverso gli strumenti nativi autorizzati; nessun check-in automatico di altri operatori. Gli errori copertina/alt conservano il messaggio validato specifico.

La descrizione di Modalità di avvio chiarisce i percorsi realmente operativi: automatico con diritto corrente, esplicito tramite ammissione amministrativa; approvazione ancora riservata al futuro workflow ISC. I valori esistenti non vengono migrati o aperti implicitamente. Il calendario e i dati pubblicati restano immutabili come negli incrementi precedenti.

Test: joomla-editor-access.php verifica deleghe, proprietà/negazioni per-record, paginazione/totali, cache della stessa sessione, scope di salvataggio, checkout ed errori; http-smoke.py copre elenco/nuovo/salvataggio di un delegato categoriale e rifiuto di uno spostamento forgiato, riepilogo/opzioni e sola lettura; reinstall conserva grant/deny/lock. Esiti nella PR del build. Non completa M01 o il collaudo interattivo/accessibilità; nessuna accettazione integrale dei requisiti dedotta da questa suite.

## Uso del campo nativo

Aprire un corso salvato, quindi la scheda Permessi effettivi. Il campo nativo elenca gruppi e azioni con ereditato, consentito e negato. Ogni modifica invia immediatamente la richiesta a com_config, con il token di sessione Joomla. Annulla chiude il corso e mantiene le modifiche già salvate dal core. L’editor rilegge i diritti alla richiesta successiva.

Il form conserva l’asset ricavato dal record sul server. Rules e asset_id vengono esclusi dal salvataggio dei dati didattici; le regole salvate dal controller nativo sono conservate. Gli utenti privi di core.admin vedono soltanto il riepilogo. Il controller Joomla applica inoltre i propri controlli sui gruppi dell’operatore, gruppi Super User e modifiche alle proprie autorizzazioni.

Le prove HTTP chiamano il controller nativo come il campo Rules e verificano grant, negazione, ripristino dell’ereditarietà, applicazione alla sessione dell’editor, rifiuto senza token o permesso e conservazione dopo Salva. La CI non sostituisce il collaudo interattivo e di accessibilità del campo.
