# Associazioni multilingua dei corsi — incremento 0.14

JD Learn implementa `AssociationServiceInterface`/`AssociationServiceTrait` e un `AssociationExtensionHelper` per il tipo `com_jlms.course`. I collegamenti sono salvati dal `AdminModel` nativo nella tabella Joomla `#__associations`, contesto `com_jlms.item`. Nessuna tabella parallela o modifica al core. Il gestore Associazioni multilingua può elencare i corsi; l'editor di destinazione rimane il form MVC del componente, con i suoi vincoli di bozza.

## Configurazione e authoring

1. Installare le lingue sito Joomla necessarie e pubblicare le rispettive lingue contenuto. Attivare il plugin core Sistema - Filtro lingua e la sua opzione Associazioni elementi.
2. Configurare le home page e le voci menu per ciascuna lingua con i gestori nativi. Pubblicare il modulo core Selettore lingua nella posizione del template desiderata.
3. Creare e salvare ciascun corso in bozza nella lingua corretta, con categoria e livelli di accesso Joomla appropriati. `Tutte (*)` identifica un contenuto comune, non una traduzione associabile.
4. Riaprire una bozza e scegliere gli altri corsi in **Traduzioni del corso**, un campo per lingua. Salvare, poi pubblicare ciascun corso tramite il workflow del programma. Le traduzioni possono essere pubblicate in momenti diversi: quelle non pubblicate non compaiono fra i collegamenti pubblici.

Il selettore usa campi lista Joomla con titoli e ID, lingua, permessi e checkout. Il limite attuale è 2000 candidati per lingua; oltre tale limite il form segnala il problema e non tronca silenziosamente la scelta. Un selettore modale con ricerca per cataloghi editoriali più grandi resta da implementare.

Non è ancora disponibile la modifica dei collegamenti di un gruppo già pubblicato: impostarli prima di pubblicare. Il blocco comprende i membri collegati, anche quando si vuole rimuovere un legame da una bozza ancora aperta. Cambiare la lingua di un corso associato richiede prima di scollegare il gruppo, mentre è tutto in bozza. Per spostare una traduzione fra gruppi bisogna prima scollegarla dal gruppo precedente; il salvataggio non sottrae corsi ad altri gruppi. Questa limitazione non equivale a una gestione editoriale completa post-pubblicazione.

## Autorizzazione e atomicità

Il salvataggio richiede sessione/CSRF del controller nativo, identità corrente non bloccata, `core.edit` oppure `core.edit.own` per ogni corso coinvolto, checkout compatibile e revisione della bozza sorgente. I target devono esistere, avere la lingua dichiarata, essere distinti e non appartenere a un altro gruppo. I membri del vecchio gruppo sono controllati anche quando il form li rimuove. Il campo associazioni non è accettato se il meccanismo core è disabilitato.

Una breve transazione serializza i salvataggi editoriali sulla riga asset del componente e blocca i corsi coinvolti; il salvataggio core e i collegamenti fanno parte della stessa transazione. Non vengono acquisiti questi lock durante la lettura pubblica o la fruizione degli studenti. La revisione ottimistica è quella del corso sorgente: tra modifiche valide effettuate da membri diversi vale l'ordine di serializzazione, come per un gruppo condiviso; non esiste un token di revisione separato del gruppo. Nessuna chiamata di rete nella transazione.

## Navigazione pubblica

Joomla Language Filter e `mod_languages` chiamano l'helper registrato. Per la vista `course` esso restituisce il link alla scheda tradotta, con codice lingua e senza riutilizzare l'Itemid sorgente. Il router nativo seleziona il menu della lingua di destinazione. Ogni lettura verifica visibilità corrente di sorgente e destinazione, pubblicazione, finestre temporali, livello catalogo, categoria e antenati, lingua contenuto pubblicata e relativo accesso. I collegamenti non impiegano la cache statica delle associazioni, per evitare di riusare risultati precedenti dopo modifiche nello stesso processo.

Se manca una traduzione visibile il componente non restituisce quel collegamento: il modulo e il filtro Joomla applicano il fallback nativo alle associazioni menu o alla home della lingua. Lingue sito non installate, home mancanti e lingue non accessibili sono filtrate dal modulo core. Lo stile e l'override del selettore lingua appartengono a `mod_languages`.

Le viste `classroom`, `quiz` e gli altri contesti non espongono associazioni a iscrizioni o tentativi. Ciascun corso tradotto mantiene programma, materiali, iscrizioni, progressi e risultati indipendenti. Il cambio lingua non attribuisce gruppi, non concede accesso al contenuto e non trasferisce completamenti.

## Verifiche e perimetro residuo

La CI predispone corsi e lingue con le API native e installa il pacchetto italiano ufficiale 6.0.0v1 (pagina di provenienza: https://downloads.joomla.org/cms/joomla6-italian/6-0-0-1). Verifica associazioni tramite core, form, input manomessi, account bloccato, ACL target, checkout, gruppi separati, concorrenza fra richieste native, rimozione, pubblicazione, visibilità corrente, modulo lingua, gestore Associazioni, navigazione SEF attiva/disattiva e conservazione al reinstall. Gli esiti effettivi sono quelli delle Actions.

Restano da completare associazioni delle categorie nelle viste dedicate, editing dei gruppi pubblicati, selettore ricercabile, traduzioni di altri oggetti, routing completo con alias/breadcrumbs, override lingua end-to-end e collaudo browser/accessibilità. CORE-07 e CORE-13 rimangono parziali. Nessuna equivalenza didattica automatica, benchmark di carico o certificazione della patch Joomla corrente.
