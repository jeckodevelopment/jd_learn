# Categorie pubbliche e multilingua

Le categorie dei corsi rimangono record nativi `com_categories`, estensione `com_jlms.course`. Il pacchetto registra il tipo `com_jlms.course.category` nel gestore Associazioni multilingua. Il form e il salvataggio delle traduzioni sono quelli del modello categorie Joomla; i legami usano il contesto core `com_categories.item`. Non vengono introdotte tassonomie, tabelle o editor paralleli.

## Gestione e navigazione

Creare le categorie dal gestore Joomla con gerarchia, ordinamento, stato, lingua, accesso e permessi. Per le traduzioni configurare lingue sito, lingue contenuto, home e modulo Selettore lingua come descritto in MULTILINGUAL.md. Selezionare le associazioni nel form nativo delle categorie oppure nel gestore Associazioni, tipo categorie dei corsi. Le sezioni domande, docenti e percorsi non sono registrate come categorie dei corsi e non hanno collegamenti pubblici attraverso queste viste.

Il gestore Menu dispone ora di due tipi: **Categorie dei corsi**, con categoria superiore facoltativa, e **Categoria dei corsi**, con categoria obbligatoria. Il catalogo e le schede corso collegano la pagina della rispettiva categoria. `view=categories` elenca i figli immediati della radice; `parent_id` seleziona un ramo. `view=category&id=…` mostra descrizione, figli immediati e corsi pubblici della categoria e dei suoi discendenti visibili.

L'elenco principale usa la paginazione Joomla, con limite massimo100. Nella scheda categoria i corsi usano la paginazione del catalogo; i figli hanno pagine da20 e parametro separato `subcategories_limitstart`. I totali contano soltanto record visibili. Le categorie vuote visibili vengono mostrate; non vengono indicati conteggi globali di figli o corsi nascosti. La descrizione nativa è resa come testo con escaping, senza eseguire HTML o plugin contenuto. La resa ricca del contenuto categoria resta fuori da questo incremento.

Le viste alimentano il Pathway nativo con antenati visibili e categoria corrente; per mostrarlo pubblicare il modulo Breadcrumbs del template. I segmenti categoria sono numerici e funzionano con SEF attivo/disattivo. Alias completi nei percorsi, canonicalizzazione dei vecchi slug e copertura SEO generale restano da completare.

## Accesso corrente e lingua

Il servizio catalogo rilegge l'utente Joomla e i livelli di accesso e verifica sezione, stato, lingua e accesso della categoria e di tutti gli antenati. Un URL diretto a un ramo non visibile risponde404; la radice globale Joomla non è una categoria pubblica del componente. La lista corsi mantiene il filtro promozionale distinto dal livello dei contenuti. Le descrizioni delle lezioni e i media privati non fanno parte della risposta.

I link lingua per `category` e `categories` puntano alla categoria associata, conservando il tipo di vista. Il link interno usa il tag lingua completo (per esempio `it-IT`), così MenuRules seleziona la voce della lingua di destinazione; il filtro Joomla lo converte nel codice SEF pubblico. Sorgente e destinazione devono essere categorie dei corsi visibili nella lingua corrente/destinazione, con antenati visibili e lingua contenuto pubblicata/accessibile. I collegamenti non usano la cache statica delle associazioni. Una traduzione mancante o riservata lascia al selettore Joomla il fallback nativo a menu/home. Non vengono trasferiti iscrizioni o progressi, né associati implicitamente i corsi dei due rami.

Gli aggiornamenti della tassonomia seguono il lifecycle e le autorizzazioni core Joomla, anche per categorie pubblicate. Non si applica alle categorie il blocco dei gruppi di traduzioni dei corsi pubblicati della0.14. Spostamenti e cambi di accesso/stato/lingua cambiano subito la visibilità pubblica dei discendenti. La scrittura delle categorie usa i lock e la semantica nativa del core: non viene dichiarata una transazione unica con gli storici didattici. Eliminazione/ripristino con corsi e storici collegati richiedono ancora il workflow di gestione delle dipendenze previsto nelle specifiche.

## Override, prestazioni e verifica

Override nativi `templates/NOME/html/com_jlms/categories/default.php` e `category/default.php`. Le regioni seguono FRONTEND_CSS.md; WAM `com_jlms.site`, variabili esistenti, nessuno stile globale del template. Mantenere escaping, router, filtri di accesso e paginazioni indipendenti negli override.

Le letture usano query paginabili sulle categorie native con filtro degli antenati, senza caricare tutto l'albero o eseguire una query per ciascun figlio. Non viene introdotto un lock di lettura comune agli studenti. Il Pathway è limitato a100 antenati e rifiuta strutture più profonde. Nessun conteggio del contenuto didattico protetto. Il benchmark300/500 studenti rimane da eseguire.

La CI verifica API/form/associazioni native, sezioni separate, antenati, spostamenti reali, paginazione, viste HTTP, descrizioni, selettore lingua e fallback con SEF0/1, e conservazione al reinstall dalla0.14. Gli esiti effettivi sono nelle Actions della PR #15. La copertura completa di rimozione/ripristino, alias/breadcrumbs per ogni tipo di menu, override lingua e browser/accessibilità rimane parziale; CORE-01, CORE-07 e CORE-13 non sono dichiarati completi.
