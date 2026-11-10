# JD Learn Credentials — Addendum funzionale e tecnico T-008–T-015

**Versione 1.0 — 10 novembre 2026 · Stato: decisioni funzionali approvate, non implementate.**  
Collegato a [specifica funzionale](specifications/JD_LEARN_CREDENTIALS_FUNCTIONAL_SPEC.md), [progettazione tecnica](specifications/JD_LEARN_CREDENTIALS_TECHNICAL_DESIGN.md) e [piano PR](CREDENTIALS_IMPLEMENTATION_PLAN.md).  
**Esclusione rigorosa:** tutte le decisioni riguardano una release **successiva a JD Learn 1.0.0**; non modificano la baseline dei 210 requisiti, i criteri di accettazione della 1.0.0 o la percentuale di avanzamento.

## Decisioni approvate

| ID | Decisione vincolante | Impatto primario |
|---|---|---|
| **T-008** | Un corso può assegnare **più badge**, ognuno con modello, emittente, regole di idoneità, validità e politica di emissione configurabili separatamente. Un percorso può assegnare badge propri. | Mapping molti-a-molti fra target didattici e regole badge |
| **T-009** | Uno studente può ricevere **nuove emissioni dello stesso badge per nuove edizioni o risultati effettivamente distinti**. Gli eventi duplicati non devono duplicare la medesima emissione; policy amministrativa per riemissioni. | Idempotenza su achievement, destinatario e istanza di risultato |
| **T-010** | **Identità pubblicabile dello studente distinta da identità/account Joomla**. Niente email privata o ID utente Joomla negli URL pubblici. Nome e attributi visibili soggetti a policy privacy; identificatori tecnici pseudonimi dove adatti allo standard. | Separazione private subject ↔ presentation data e credenziale esportata |
| **T-011** | **Emittente configura le regole di pubblicazione e di emissione automatica** in modo distinto: l'automatismo di rilascio è indipendente dalla pubblicazione nominativa su web. Per default non pubblicare dati nominativi senza base giuridica/condizione adeguata; verifica pubblica e disponibilità dei dati sono distinte. | Policy per emittente/modello, override consentiti e audit; GDPR |
| **T-012** | **Scadenza facoltativa**, configurata per badge/modello dall'emittente. Snapshot della scadenza all'emissione, senza alterare credenziali precedenti. | Validity window immutabile |
| **T-013** | **Più emittenti per singola installazione Joomla**, con uno predefinito, identificatori/chiavi/policy indipendenti e selezione per badge. | Issuer foreign keys e ACL scoped |
| **T-014** | **Verificabilità nel tempo:** URL stabili, chiavi e status storici, esportazione portabile e procedure di backup, cambio dominio/redirect e migrazione. Nessuna promessa assoluta di disponibilità perpetua. | Lifecycle e continuity runbook |
| **T-015** | **Badge per corso e per percorso nella prima release completa Credentials**; criterio percorso: completamento di **tutti i corsi obbligatori previsti dalla versione/istanza del percorso**, tramite stati didattici consolidati. Il badge del percorso è indipendente dai badge dei singoli corsi. | Motore percorsi e regole; C1 blocca su semantica percorso affidabile |

## Flusso corso → percorso (scenario approvato)

1. L'amministratore pubblica il corso A e il percorso P, formato dai corsi obbligatori A, B e C; associa al corso A un badge BA e al percorso P un badge BP. Ogni target può prevedere più badge distinti.
2. Lo studente completa A; JD Learn registra il completamento affidabile e, se i criteri di BA sono soddisfatti, il job Credentials **emette BA**. Il percorso non è ancora completato.
3. Lo studente completa B e C. JD Learn valuta il completamento del percorso P secondo la versione/istanza di percorso assegnata e lo segnala; il job Credentials **emette BP**, indipendentemente da BA.
4. Se il badge BA non è stato emesso o viene revocato, **questo non deve automaticamente cambiare il risultato didattico** di P. L'idoneità BP dipende dal completamento dei corsi obbligatori, non dal possesso dei badge intermedi (salvo futura regola esplicita).
5. Riprocessare l'evento di completamento non genera BA/BP duplicati; un nuovo completamento di un'edizione o istanza realmente distinta può produrre una nuova credenziale quando consentito dalla regola.
6. Modifiche successive al percorso non cambiano retroattivamente la versione o le prove alla base di BP già firmato.

### Ulteriori dettagli di accettazione

- In backend corso e percorso: sezione nativa **Credenziali digitali** con lista delle regole assegnate e operazioni aggiungi/rimuovi/riordina, scelta emittente e modello, modalità auto/manuale, criterio, scadenza, politica riemissione, visibilità e anteprima del risultato. Un badge su un corso non va automaticamente ereditato dal percorso.
- **Regole multiple:** ciascun badge valuta autonomamente il criterio; supporto ad almeno 2 badge collegati al medesimo corso con condizioni differenti. Non dichiarare supporto a criteri avanzati (quiz/punteggi) finché non implementati e collaudati; mostrarli soltanto quando disponibili.
- **Regola di percorso:** deve valutare la versione/istanza congelata al momento dell'iscrizione o assegnazione e la distinzione tra corsi obbligatori/facoltativi. Casi di migrazione fra versioni vanno gestiti secondo le regole didattiche di JD Learn, senza inferenze opportunistiche.
- **Pubblicazione vs emissione:** il rilascio automatico può essere attivo mentre la scheda nominativa pubblica è disattivata. Separare verifica tecnica di una credenziale presentata da esposizione pubblica di dati personali; la policy non deve renderla «valida» senza controlli crittografici.
- **Immagini badge:** caricamento, non editor grafico. L'interfaccia deve documentare formati supportati, dimensioni consigliate, rapporto 1:1, peso massimo, leggibilità a piccola scala, contrasto e assenza di dati personali sensibili nell'immagine. Le soglie numeriche sono da fissare e testare in fase esecutiva, non presumerle già approvate.
- **Revoca:** permesso ACL dedicato, motivazione obbligatoria, audit e gestione dello stato interoperabile. La revoca badge non modifica automaticamente completamenti didattici.
- **Privato/pubblico:** definire prima di C2 un profilo di credenziale esportabile e un modello di pubblicazione che preservino minimizzazione/portabilità; la possibilità di importare in wallet o mostrare su LinkedIn non conferisce automaticamente consenso a pubblicazione nominativa generalizzata.

## Criteri di collaudo aggiuntivi

| ID | Scenario e risultato atteso |
|---|---|
| **AC-CRE-027** | Due modelli associati allo stesso corso con criteri differenti producono emissioni indipendenti al soddisfacimento dei rispettivi criteri. |
| **AC-CRE-028** | Il badge di A viene emesso quando A è completato; il badge del percorso P (A+B+C obbligatori) viene emesso **solo dopo** completamento di A, B e C. |
| **AC-CRE-029** | La mancata emissione o la revoca del badge di A non impedisce automaticamente il riconoscimento del completamento P; idoneità deriva dai risultati formativi. |
| **AC-CRE-030** | Nuova edizione o risultato distinto consente emissione successiva secondo policy; replay dello stesso completamento non duplica. |
| **AC-CRE-031** | Emissione automatica attiva con pagina nominativa privata non espone dati riservati su URL anonimi, OG o API. |
| **AC-CRE-032** | Due emittenti configurati in una installazione rilasciano badge con rispettive identità e chiavi/policy, senza cross-accesso di gestione non autorizzato. |
| **AC-CRE-033** | Modello con scadenza e modello senza scadenza producono credenziali con finestre corrette; cambi di modello non alterano credenziali già firmate. |
| **AC-CRE-034** | Upload immagine supportata valido, rifiuto formati/size non ammessi, istruzioni visibili e risultato leggibile nei contesti previsti. |
| **AC-CRE-035** | Nome pubblicabile/pseudonimo e collegamento privato a Joomla sono coerenti; nessuna email privata né ID Joomla è esposta senza giustificazione. |
| **AC-CRE-036** | Dopo upgrade, rotazione e migrazione dominio pianificata, link/chiavi/status per vecchie credenziali rimangono verificabili. |

## Allineamento con il piano delle PR

I nuovi scenari si assorbono nelle **21 PR già pianificate**, senza ampliare automaticamente il numero:
- **CRE-03/04:** più emittenti, policy, chiavi e mapping.
- **CRE-05:** badge multipli, scadenza, immagini e istruzioni.
- **CRE-06:** criteri corso/percorso e scenario A→P; requisito di uscita C1.
- **CRE-07/08:** emissione automatica, nuove edizioni e idempotenza.
- **CRE-09/12/13:** revoca motivata e ciclo di vita.
- **CRE-10/14/15:** verifica duratura, privacy e identità separata.
- **CRE-16/17/19:** pubblicazione selettiva, area studente, LinkedIn e istruzioni UX.
- **CRE-20/21:** test AC-CRE-027–036 e collaudo finalizzato.

**Promemoria:** le 3 PR C5 restano dedicate a wallet/API avanzate, regole didattiche ulteriori e importazione esterna. L'emissione per percorso appartiene già alla prima release completa, non a C5.
