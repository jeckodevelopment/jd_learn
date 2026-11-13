# JD Learn 1.0.0 — Cookie, storage browser e integrazione CMP

**Stato: inventario preliminare da codice (13 novembre 2026). Verifica dinamica nel browser e approvazione privacy ancora pendenti.** Il documento è un supporto agli amministratori del sito; non è un'informativa privacy o cookie universale e non sostituisce la valutazione del titolare/consulente.

## Inventario preliminare e classificazione

| Elemento | Componente che lo gestisce | Tecnologia | Finalità | Persistenza | Categoria/consenso | Evidenza |
|---|---|---|---|---|---|---|
| `jdlearn.video.speed.v1` | JD Learn, `component/media/js/video.js` | `localStorage` dell'origine del sito | Preferenza di velocità del player | Persistente finché il browser/sito non la rimuove; non è impostata una scadenza applicativa | **Da valutare**: preferenza funzionale, non classificarla automaticamente come cookie tecnico/esente | `getItem` e `setItem` nel codice; `docs/VIDEO.md` |
| Cookie di sessione e autenticazione Joomla | Joomla e configurazione del sito | Cookie HTTP | Sessione, autenticazione e protezione funzionale | Nome, attributi e durata dipendono dall'installazione Joomla | Generalmente necessari al funzionamento, da descrivere nell'informativa del sito | Responsabilità del core Joomla; da misurare su installazione concreta |
| Storage/cookie di YouTube, Vimeo, Cloudflare Stream | Servizi esterni, quando caricati | Cookie, storage e richieste esterne possibili | Funzionamento del player e trattamenti del provider | **Da verificare per provider, browser, paese e configurazione** | Valutazione e configurazione CMP del titolare; nessun automatismo basato solo su `youtube-nocookie.com` o `dnt=1` | `docs/VIDEO.md`; SDK/iframe provider |
| Sessioni e progressi, iscrizioni, consenso email | JD Learn lato server | Database, non cookie per definizione | Fruizione corsi e registrazione eventi/consensi | Secondo retention configurata o definita dal titolare | Trattamento dati personali da illustrare nella privacy policy, non presentare come cookie | `plugins/privacy/jdlearn/docs/README.md` e implementazione backend |

**Importante:** una ricerca di `setcookie` o `document.cookie` nel codice non è una prova che non siano emessi cookie. Joomla e SDK di terze parti possono aggiungerli. L'inventario è **provvisorio** fino a registrazione dei cookie effettivi, domini, valori, attributi `Secure`/`HttpOnly`/`SameSite`, durata e chiamate di rete.

## Configurazione del consenso video e integrazione con la CMP

JD Learn **non fornisce una cookie bar propria**. La gestione dei consensi è del sito Joomla e del relativo plugin/CMP; JD Learn prevede il comportamento `site_consent` per i video esterni. L'amministratore deve:
1. Configurare il provider video in JD Learn e scegliere `blocked` o `site_consent` nella policy dei video esterni. Evitare `embed` dove è richiesto un consenso preventivo.
2. Collegare la CMP al bridge JavaScript atteso da JD Learn: `window.JlmsConsent.isAllowed(providerKey)` e un evento `jlms:consent-changed` al cambio di autorizzazione. La CMP deve mappare in modo esplicito ciascun provider/servizio; **il bridge non è fornito automaticamente da qualunque CMP**.
3. Con consenso mancante o negato, verificare che SDK, iframe, richieste e cookie del provider **non** vengano caricati. Con autorizzazione, consentire il player; con revoca, distruggerlo e prevenire nuovi avvii. Le chiamate già effettuate non sono annullabili retroattivamente.
4. Rilevare il comportamento reale in browser desktop e mobile e verificare che sia coerente con la policy del sito e con la sua configurazione CSP. `youtube-nocookie.com` e `dnt=1` riducono alcuni trattamenti ma non costituiscono garanzia di assenza cookie o esenzione dal consenso.
5. Documentare nel banner/informativa i provider realmente abilitati e aggiornare la classificazione a ogni cambio di versione/configurazione.

La preferenza email di nuovi corsi con double opt-in è **distinta** dal consenso ai servizi video/cookie: non usarla come autorizzazione a caricare SDK esterni.

## Scheda per amministratori e sistemi di cookie management

Per ogni tecnologia censita nell'ambiente reale compilare: nome esatto, dominio e path, parte prima/terza, cookie HTTP o local/session storage, finalità, dati conservati, durata/scadenza, attributi, provider responsabile, categoria adottata nella CMP, base giuridica/necessità del consenso valutata, comportamento prima/dopo consenso e revoca, modalità di cancellazione, versione e data della prova. Fornire una tabella pronta per il copia-incolla nelle CMP, **non** presumere identici i nomi dei cookie sui diversi siti Joomla.

Esempio verificato staticamente: `jdlearn.video.speed.v1`, `localStorage`, prima parte, valore numerico della velocità di riproduzione, senza scadenza applicativa impostata. Categoria, obbligo di consenso e durata effettiva su singolo dispositivo **da confermare**.

## Piano di verifica obbligatorio prima della 1.0.0

- Browser nuovo, senza account né consenso, eseguire scansione cookie/storage/network sull'homepage, catalogo, corso, aula e backend.
- Ripetere dopo login, riproduzione HTML5 locale e con ciascuno dei quattro provider abilitati, prima/dopo consenso e dopo revoca.
- Ripetere con CMP non installata, CMP compatibile, blocco di terze parti, mobile e lingue IT/EN. Assicurarsi che il fallback di consenso non consenta caricamenti remoti indesiderati.
- Confrontare con baseline Joomla senza JD Learn per attribuire correttamente i cookie. Misurare nomi, domini, scadenze e attributi reali.
- Verificare `localStorage`, `sessionStorage`, IndexedDB, cache e storage di SDK, non solo `document.cookie`.
- Salvare evidenze redatte (senza token, identificativi, sessioni o dati personali) nel testbook; riportare versione Joomla, browser, provider, policy e CMP.
- Validare classificazione e wording delle informative con il responsabile privacy del sito; prevedere manutenzione per aggiornamenti Joomla, JD Learn e SDK.

**Criterio di accettazione SEC-P4:** inventario completo e verificato, guida di configurazione CMP IT/EN, esempi pratici, nessuna tecnologia terza facoltativa avviata prima delle condizioni di consenso configurate, verifica della revoca e istruzioni di manutenzione. La presenza di questo documento **non** soddisfa da sola tale criterio.
