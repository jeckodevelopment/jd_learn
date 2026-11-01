# Avanzamento di JD Learn

Aggiornamento verificato: 1 novembre 2026. Versione di sviluppo **0.20.0-dev**, incremento in collaudo dopo il riallineamento della0.19; il prodotto non è ancora pronto per la produzione.

Schema attuale: **41 tabelle / 58 FK**. Il pacchetto contiene7estensioni: componente, libreria, quattro provider video e plugin Scheduler. Il database presente non equivale a una funzionalità operativa.

La specifica funzionale1.2 e il progetto tecnico sono il perimetro; il suffisso1.0 nei loro nomi è storico. La [matrice corrente](REQUIREMENTS_STATUS.csv) riporta tutti i210requisiti della [matrice baseline](specifications/Matrice_requisiti_test_LMS_1.0.csv), senza modificarne descrizione, perimetro o scenari previsti.

## Come leggere lo stato

- **In corso:** esiste un sottoinsieme riscontrato, con sorgenti/guide e verifiche parziali indicati. Non significa requisito completato, né esprime una percentuale.
- **Da implementare:** il percorso specifico non è disponibile; una tabella, una funzione vicina o una specifica non lo sostituiscono.
- **CI parziale:** i file elencati sono verifiche della suite implementata, sul build indicato nelle [evidenze](IMPLEMENTATION_EVIDENCE.json). Non attestano automaticamente tutti gli scenari AC/provider della baseline.
- **Solo riscontro documentale:** scelta di perimetro/guida trovata, senza attribuzione di collaudo runtime per quella riga.

Nessun requisito è stato promosso ad accettazione completa in questo riallineamento. La matrice non va usata per calcolare una percentuale di completamento: requisiti di complessità diversa si sovrappongono e un test può verificare solo parte di più requisiti. I requisiti differiti restano separati dal rilascio1.

## Funzioni disponibili e residui

| Area | Percorsi operativi verificati fino alla0.19 | Residui per il primo rilascio |
|---|---|---|
| Pacchetto e installazione | Manifest/DI/MVC, preflight, build riproducibile, installazione/upgrade e conservazione dati sui flussi CI | Update server, backup/ripristino, matrice completa sulla patch corrente e manutenzione di rilascio |
| Utenti e accessi | Identità/sessioni/CSRF, livelli e asset Joomla; ricaricamento dei diritti, blocchi/revoche e controllo diretto per oggetto | MFA/provider reali e copertura di tutte le nuove azioni; integrazione reale con estensione abbonamenti |
| Categorie e lingue | Categorie native, gerarchia/antenati, Pathway e associazioni; corsi associati in bozza, Language Filter e menu SEF | Lifecycle con dipendenze, associazioni dei gruppi pubblicati e selettori completi |
| Authoring e pubblicazione | Bozze, moduli/lezioni testo/video/quiz, ordinamento/parent, editor filtrato, workflow Publishing e snapshot immutabili | Audio/documenti come lezioni autonome, clonazione/nuove versioni complete, equivalenze/migrazioni, prerequisiti e rilascio progressivo |
| Catalogo e aula | Schede/programma pubblici autorizzati, filtri categorie, aula protetta, materiali e completamenti persistenti | Ricerca/ordinamenti/SEO e metadati completi, percorsi aggregati, navigazione/UX/accessibilità complete |
| Area personale | Storico del proprietario con filtri/pagine, apprendimento distinto dall’accesso, Riprendi/Rivedi e lingua originale | Attestati e attività dettagliate; fallback di ripresa R-05 |
| Video | Quattro plugin base, consenso, copertura unica/ripresa, Stream API/token, MP4 privati PHP limitato/Nginx | Provider aggiuntivi senza patch R-02, sottotitoli/trascrizioni/velocità/configurazioni complete, firma locale/cache Stream, collaudo SDK/provider/browser reale |
| Materiali | Allegati distinti corso/lezione, upload con limiti/allowlist/quarantena, scanner opzionale e download autorizzato/Range | Riuso/preview/metadati e ciclo versioni completi, offload/pulizia/conservazione e scanner reale |
| Iscrizioni | Avvio automatico; elenco amministrativo per corso; sospensione/riattivazione; ammissione manuale singola auto/explicit con anteprima, motivo, conferma, capienza e audit | Preassegnazione esplicita0.20 in collaudo; CSV, richieste/approvazioni/rifiuti, annullamenti, edizioni aggiuntive, finestre/proroghe e selettori ricercabili |
| Quiz | Banca/revisioni, composizione/pubblicazione, pool/snapshot, tentativi/autosave/CAS, grading/feedback, scadenza server; tempi individuali, annullamenti e proroghe con audit | Rivalutazioni, disponibilità specifiche, anomalie/attestati, UI/accessibilità e collaudo completo |
| Attestati | Solo schema e specifica | Modelli/PDF, idoneità/emissione, download, verifica pubblica opzionale, revoca/sostituzione e anomalie |
| Audit e task | Writer com_actionlogs su quiz/iscrizioni; plugin Scheduler per scadenze quiz | Plugin/coverage Action Logs generali, job/outbox/lease/retry, notifiche, Mail Templates e purghe |
| Report | Elenco iscritti con filtri/versione/utente/stato/avanzamento e date UTC | Aggregati/KPI, dettagli studente/quiz/attestati, filtri/CSV export e dati temporanei protetti |
| Core Joomla restante | Integrazioni già operative riportate sopra, WAM, override, form/menu e lingue | Plugin Privacy export/removal/capabilities, Finder, Tags, Custom Fields, Content History, Media Manager e tutte le azioni core pertinenti |
| Qualità e scala | Test PHP/database/Joomla/HTTP/JS, negativi, richieste concorrenti e upgrade della suite implementata | WCAG/manuali browser/mobile, analisi statica/stile/scan e revisione sicurezza completa, provider live, recovery e carico300/500 |

I limiti specifici e le differenze rispetto alla specifica sono nel [registro aperto](IMPLEMENTATION_GAPS.md). Il ritiro dal catalogo non implementa ancora le due operazioni distinte previste da EDGE-09; i controlli sugli ID del componente non certificano l’unicità di tutta la pagina Joomla.

## Verifiche effettive

La [CI del build0.19](https://github.com/jeckodevelopment/jd_learn/actions/runs/41308203822) è risultata verde su PHP8.3/8.4 × MySQL8.4/MariaDB10.6, con Joomla6.0.0 e pacchetto italiano nativo. Comprende la suite esistente, l’upgrade dalla0.18 e il reinstall con dati conservati. Il minimo Joomla6.0.0 usato nella CI non è una raccomandazione di patch per la produzione.

Per il blocco manuale sono verificati41controlli del servizio,23asserzioni HTTP oltre alle richieste negative e4controlli di conservazione per ambiente; non sono41requisiti di prodotto accettati. Il [registro delle evidenze](IMPLEMENTATION_EVIDENCE.json) fissa commit/albero, job e hash del pacchetto, distinguendo la prova storica dalla documentazione aggiornata.

Non sono eseguiti i benchmark300studenti/60min e500/10min, né una verifica completa di ASVS/WCAG, provider reali, patch corrente e recovery. I test concorrenti su pochi processi provano specifici invarianti, non la capacità su centinaia di studenti.

## Prossimi passaggi

Il [piano esecutivo residuo](NEXT_STEPS.md) specifica dipendenze e condizioni di accettazione. Priorità successiva: concludere le verifiche della preassegnazione e poi import CSV, mantenendo scope, dati esistenti e gruppi invariati; job amministrati per i volumi previsti. Privacy e sicurezza accompagnano ogni incremento e non sono rinviate alla sola verifica finale.

## Cronologia e guide

La [cronologia originale](IMPLEMENTATION_HISTORY.md) conserva le note dei singoli incrementi; i vecchi «da completare» vanno letti nel loro contesto temporale. Per configurare e usare i percorsi operativi consultare le guide elencate nel [README](../README.md). La vendita/gestione abbonamenti rimane fuori perimetro; compiti, live, gruppi/classi e interoperabilità mantengono le fasi differite della baseline.

## Incremento0.20 in collaudo

[PREASSIGNMENT.md](PREASSIGNMENT.md): opzione esplicita disattivata per default, avviso di accesso mancante, assegnazione senza gruppi e successiva fruizione soltanto dopo diritti correnti. Test di incremento predisposti; l’evidenza storica0.19 resta distinta fino alla verifica delle Actions. Nessuna nuova tabella.
