# Documentazione JD Learn

Il componente è in sviluppo. Sono disponibili gestione corsi, programma, aggiornamenti con riepilogo/conferma, recupero del programma storico come nuova bozza, lezioni testo/video/quiz, iscrizioni e progressi persistenti. La versione attiva e gli iscritti storici rimangono separati dalle bozze. Guida amministrativa: [COURSE_EDITOR.md](COURSE_EDITOR.md); pubblicazione e versioni: [PUBLICATION.md](PUBLICATION.md).

La distribuzione include i plugin YouTube, Vimeo, Cloudflare Stream e HTML5. Il funzionamento operativo dei tre provider esterni è prioritario insieme ai corsi. Impostazioni privacy del sito, plugin e restrizioni del provider devono consentire il caricamento; il video compare direttamente nella lezione quando autorizzato. Stream usa token API e rinnovo previa verifica remota; HTML5 offre MP4 privati con consegna autorizzata. Configurazione e limiti: [CLOUDFLARE_STREAM.md](CLOUDFLARE_STREAM.md), [HTML5_VIDEO.md](HTML5_VIDEO.md), [VIDEO_PROVIDERS_SPEC.md](VIDEO_PROVIDERS_SPEC.md). Nessuna credenziale reale è inclusa.

Banca domande, composizione e tentativi: [QUIZ_AUTHORING.md](QUIZ_AUTHORING.md), [QUIZ_RUNTIME.md](QUIZ_RUNTIME.md). Le guide descrivono i sottoinsiemi disponibili, non attestano il completamento dell’intero sistema.

[Stato aggiornato e prove](https://github.com/jeckodevelopment/jd_learn/blob/main/docs/IMPLEMENTATION_STATUS.md), [priorità residue](https://github.com/jeckodevelopment/jd_learn/blob/main/docs/NEXT_STEPS.md) e [CSS/override](https://github.com/jeckodevelopment/jd_learn/blob/main/docs/FRONTEND_CSS.md) sono mantenuti nella repository a ogni incremento.

- [Dashboard amministrativa](ADMIN_DASHBOARD.md)
