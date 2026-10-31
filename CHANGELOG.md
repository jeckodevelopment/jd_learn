# Changelog

## 0.6.0-dev

- Player YouTube/Vimeo opt-in tramite plugin Joomla, SDK ufficiali caricati dopo consenso e grant server.
- Pubblicazione video con durata verificata e snapshot immutabile; aula con nuove regioni CSS e materiali corso/lezione.
- Sessioni, sequenze e replay, copertura unica, budget temporale server, resume concorrente e controllo ACL corrente.
- Test dominio, integrazione Joomla/database, endpoint HTTP e mock del trasporto browser; upgrade da 0.5 con storico conservato.
- Cloudflare Stream/HTML5, metadati remoti, prove SDK reali e test di carico restano da completare.

## 0.5.0-dev

- Materiali di corso/versione e lezione: upload, metadati, ordine, sostituzione immutabile e rimozione del collegamento in bozza.
- Storage fuori webroot, allowlist e controlli MIME/firme/archivi bounded, quarantena e scansione ClamAV opzionale via socket locale.
- Download con accesso corrente, ownership/parent, SHA256, attachment/nosniff/no-store e Range/HEAD; materiali facoltativi senza credito didattico.
- Pubblicazione include hash dei materiali e richiede asset pronti; nuove regioni CSS e override documentati.
- Offload, cleanup, preview e riuso asset ancora da completare.

## 0.4.0-dev

- Iscrizione automatica/ripresa e aula per lezioni di testo, con login nativo Joomla.
- Completamento idempotente e transazioni per iscrizione, contatori obbligatori e data di conclusione separata dallo stato di accesso.
- Identità e livelli aggiornati dal core a ogni richiesta, anche dopo revoca con sessione aperta.
- Test con processi concorrenti, revoca/ripristino, ownership, CSRF e flusso HTTP studente.
- Modalità approvazione, player/materiali, quiz, attestati e integrazioni restanti ancora in sviluppo.

## 0.3.0-dev

- Pubblicazione validata dei corsi di testo tramite workflow e plugin Publishing Joomla; transazione unica, versione immutabile, hash ed edizione iniziale.
- Catalogo paginato e scheda pubblica con programma, filtro categoria/discendenti, autorizzazioni correnti e nuovi ID CSS.
- Test integrativi workflow/veto/immutabilità/visibilità e rendering HTTP; risultati effettivi nella PR.
- Video, materiali, iscrizioni e fruizione studenti ancora in sviluppo.

## 0.2.0-dev — 31 ottobre 2026

- Aggiunti servizio applicativo DI, prima bozza didattica, editor del programma e comandi MVC nativi.
- Implementati moduli e lezioni testo/video, obbligatorietà, modifica, riordino e rimozione con controlli delle dipendenze.
- Controlli centralizzati di ACL, parent, revisione aggregata/elemento e immutabilità delle versioni pubblicate; query e transazioni brevi.
- Integrazione editor/filtro HTML core, provider dal registro estensibile e normalizzazione delle sorgenti.
- Nuovi test integrativi e HTTP del flusso completo. Nessuna pubblicazione o fruizione studenti ancora attiva.

## 0.1.0-dev — 31 ottobre 2026

Primo incremento di sviluppo, non release del sistema e-learning completo.

- Importate le specifiche funzionali, tecniche, sicurezza/provider, servizi/endpoint e matrici di collaudo concordate.
- Aggiunti package Joomla, libreria contratti, componente MVC, schema37tabelle/53FK e installer conservativo con verifiche dello schema trattenuto.
- Implementata gestione dei corsi in bozza con categorie, livelli, lingua, asset ACL, check-in/out e controllo delle revisioni.
- Inclusi i quattro plugin video con registrazione lazy e validazione locale delle sorgenti; riproduzione esplicitamente non disponibile.
- Aggiunte regole dominio iniziali, traduzioni, WAM, prime regioni CSS pubbliche e guida agli override.
- Predisposte build riproducibile e CI PHP8.3/8.4, MySQL8.4/MariaDB10.6, installazione/aggiornamento Joomla, test MVC e rendering HTTP.
- Pubblicazione, fruizione, quiz persistenti, attestati e integrazioni rimanenti restano nello stato documentato in IMPLEMENTATION_STATUS.md.
