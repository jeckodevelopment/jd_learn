# PRIV-CONSENT — Integrazione consensi JD Learn con Joomla Privacy (1.0.0)

Stato: **analisi funzionale e piano approvati, integrazione del registro consensi Joomla da verificare e implementare**. Non dichiarare questa voce completata prima del collaudo Joomla.

## Risultato dell'analisi del core

Il backend Joomla offre **Utenti → Privacy → Consensi**, una lista di consensi registrati nel core, **Privacy → Funzionalità delle estensioni**, per le capacità dichiarate dai plugin, e richieste di **esportazione/rimozione** governate dagli eventi `onPrivacyExportRequest`, `onPrivacyCanRemoveData` e `onPrivacyRemoveData`. Non esiste un'aggregazione automatica di qualsiasi tabella consensi delle estensioni. Il registro nativo è principalmente progettato per i consensi all'informativa del sito. L'integrazione di consensi applicativi addizionali richiede una verifica delle **API pubbliche supportate** sul minor Joomla target e su quello corrente; non utilizzare le tabelle private del core come contratto di integrazione.

Riferimenti: https://guide.joomla.org/help-pages-references/privacy/privacy-consents ; https://guide.joomla.org/user-guide/privacy/privacy-privacy-setup ; https://docs.joomla.org/J3.x:Integrate_Extensions_with_the_Privacy_Component/en (documentazione storica degli eventi, verificare le firme Joomla 6).

## Stato attuale JD Learn

- `component/administrator/src/Application/CourseAnnouncementConsent.php` è l'autorità per il consenso MAIL-11 in `#__jlms_mail_consents`: richiesta `pending`, conferma double opt-in `confirmed`, revoca `revoked`, origine, versione testo e timestamp. La richiesta successiva può sostituire la riga precedente: **non** è uno storico immutabile completo.
- `plugins/privacy/jdlearn/src/Extension/Jdlearn.php` dichiara il dominio nei capabilities, esporta `state`, `origin`, `policy_version`, `requested_at`, `confirmed_at`, `revoked_at`, verifica i blocchi alla rimozione ed elimina `#__jlms_mail_consents` durante la rimozione.
- Da verificare esplicitamente anche `#__jlms_mail_unsubscribe_tokens`, eventuali job/campagne/outbox, audit e dati di consenso rimasti: non presumere che tutti i residui siano già rimossi o anonimizzati.
- Il core Joomla gestisce il consenso generale della privacy policy; **non sostituirlo** con il consenso facoltativo MAIL-11, né equipararli. Quest'ultimo non è consenso cookie/video.

## PR implementative proposte

| ID | Priorità | Deliverable | Criterio di accettazione |
|---|---|---|---|
| PRIV-C1 | P0 | Verifica Joomla 6 delle API supportate per registrazione/consultazione di consensi aggiuntivi e del comportamento del registro `Privacy → Consensi` | Prova su Joomla target, conclusione ADR con compatibilità; nessuna modifica core |
| PRIV-C2 | P0 | Visibilità admin del consenso MAIL-11 corrente e delle evidenze disponibili, tramite integrazione supportata se esiste; altrimenti vista JD Learn dedicata e collegamento dagli strumenti privacy, se consentito | Utente, finalità, stato, origine, versione informativa e timestamp visibili con ACL adeguata; nessun accesso anonimo |
| PRIV-C3 | P0 | Review e completamento esportazione, anonimizzazione/rimozione dati correlati (token revoca, campagne/outbox/audit) con idempotenza e retention documentata | Test reali su account con stato pending/confirmed/revoked e code pendenti, senza token segreti nell'export |
| PRIV-C4 | P1 | Guida amministratore IT/EN su Joomla Privacy, consensi JD Learn, limiti, revoche, gestione retention e risoluzione blocchi | Manuale verificato, link dalla guida contestuale; differenza consenso privacy sito, MAIL-11 e cookie |
| PRIV-C5 | P0 | Test upgrade, doppie operazioni, revoca dopo esportazione e autorizzazioni, compatibilità plugin Joomla e accuratezza della consultazione | Testbook e CI per ogni caso, accettazione manuale Joomla registrata |

## Regole tecniche

1. **Una sola fonte di verità:** `#__jlms_mail_consents` fino a migrazione esplicitamente approvata. Non inviare email in base a copie sincronizzate/denormalizzate nel registro Joomla. Solo `confirmed` abilita MAIL-11; la revoca deve fermare gli invii successivi anche se esistono log storici.
2. **Registro core:** dopo test delle API pubbliche, scegliere tra proiezione compatibile o pannello JD Learn. Non inserire direttamente righe in `#__privacy_consents` presumendone stabilità né sovrascrivere i record del consenso generale del sito.
3. **Storico e retention:** distinguere l'evidenza corrente dallo storico degli eventi; definire minimizzazione e periodo di conservazione col titolare del sito. Eventuali registri immutabili non devono causare consenso persistente dopo revoca.
4. **Export e removal:** includere la prova corrente pertinente, rimuovere o dissociare i token e gli identificativi compatibilmente con le dipendenze; evitare export di token/hash/sessioni; documentare eventuali blocchi legittimi e la loro risoluzione.
5. **Privacy e sicurezza:** usare event subscriber Joomla, query parametrizzate, ACL server-side, no PII in log, niente modifica del core, test su database e Joomla reali.

**Da eseguire nella fase SEC-P3 e prima della 1.0.0.** La stesura del piano non incrementa la percentuale globale né conferma la compatibilità della proiezione nel registro nativo.
