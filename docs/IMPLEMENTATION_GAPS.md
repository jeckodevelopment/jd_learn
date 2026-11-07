# Problemi specifici e differenze aperte

Stato al **7 novembre 2026**, build **0.38.0-dev**. Questo registro tratta casi specifici; l'elenco completo delle funzioni residue è nello [stato attuale](IMPLEMENTATION_STATUS.md) e nella [matrice](REQUIREMENTS_STATUS.csv).

## Casi aperti

| ID | Requisiti | Differenza attuale | Condizione di chiusura |
|---|---|---|---|
| R-02 | PVD-01/06, CORE-23, VID-01, PBASE-03 | Il registry PHP esiste, ma pubblicazione e client sono ancora limitati ai quattro adapter base | Quinto provider di prova installabile senza patch, con asset/capacità/validazione/WAM autorizzati e completamento centrale invariato |
| R-03 | EDGE-09, ISC-04 | Ritiro dal catalogo e disponibilità dell'aula non sono ancora due comandi indipendenti | Chiudi nuove iscrizioni mantiene la fruizione degli iscritti; Sospendi erogazione la blocca, inclusi quiz aperti; motivo/anteprima/audit |
| R-04 | UI-02, ACC-01, REL-02 | Le prove sugli ID del componente non certificano l'intera pagina Joomla; rilevato in CI un duplicato nativo `guidedtours-popup-content109` | Verifica dell'intero documento nel contesto core/template target e sulla patch corrente, senza modificare il core; collaudo accessibilità completo |
| R-05 | SCR-06, STU-03 | Il fallback di Riprendi usa la prima lezione per ordine, senza preferire l'obbligatoria non completata | Correggere il fallback preservando versione/lingua/accessi; provare attività opzionale, obbligatoria già completata e corso concluso/incoerente |
| R-06 | VID-02/03/08, PRO-04/06, AUT-01/04, SCR-13 | Durata esterna dichiarata/confermata manualmente; soglia di scarto attuale1s/0,5% può bloccare player/progressi. MP4 già misurati e metadati API Stream già disponibili, ma non un authoring automatico completo | Durata acquisita automaticamente, ripiego manuale distinto, tolleranza coerente con il calcolo del completamento e storici conservati; [piano prioritario](VIDEO_DURATION_PLAN.md) |

## Segnalazioni risolte nel percorso indicato

| ID | Risoluzione riscontrata | Cosa rimane separatamente aperto |
|---|---|---|
| R-00 — prima pubblicazione | Comando e diagnostica disponibili; prima pubblicazione confermata dall'utente e coperta dalle regressioni HTTP | Accettazione completa della gestione corsi e del ciclo editoriale; non riaprire il blocco specifico senza una nuova regressione |
| R-01 — preassegnazione singola | Opt-in e avviso consentono l'assegnazione senza concedere gruppi o diritto di fruizione; grant/revoca conserva lo storico | Estendere gli invarianti al CSV e agli altri flussi di iscrizione |

Per chiudere un caso servono comportamento conforme, guida, prove positive/negative e riferimento al build. Una correzione puntuale non accetta automaticamente l'intero requisito. Le evidenze storiche sono conservate nel [registro](IMPLEMENTATION_EVIDENCE.json) e nella cronologia Git.
