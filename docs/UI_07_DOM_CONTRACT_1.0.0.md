# UI-07 — Mappe DOM pubbliche versionate (contratto 1.0.0)

La fonte versionata leggibile dalle verifiche CI è [FRONTEND_DOM_CONTRACTS_1.0.0.json](FRONTEND_DOM_CONTRACTS_1.0.0.json). Registra **tutte le 17 viste con layout `default.php`**, relativo percorso di override Joomla, radice, ID letterali e classi letterali `jlms-*`. Le viste condividono alcuni partial: gli ID di partial condizionali non fanno parte dell'inventario statico del singolo file e sono descritti in [FRONTEND_CONTRACT_INVENTORY.md](FRONTEND_CONTRACT_INVENTORY.md). La mappa non descrive l'intero DOM renderizzato: ID interpolati per record, markup di Joomla e markup prodotto da partial o estensioni dipendono dal runtime.

## Regole di compatibilità

- Versione del contratto: **1.0.0**. Per una modifica additiva del contratto aggiornare mappa, guida e test nello stesso cambiamento. Per rimuovere o rinominare un hook pubblico introdurre prima un alias compatibile ove possibile e documentare migrazione e versione incompatibile, senza mutare silenziosamente la baseline.
- Le radici sono per *pagina*, non identificatori globali: `dashboard` e `mycourses` utilizzano `#jlms-dashboard` in pagine distinte. Il partial di navigazione personale può essere renderizzato una sola volta per pagina; non inserirlo due volte negli override.
- Gli ID per le schede ripetute devono derivare da identificatori stabili unici, non da titoli, e i partial riutilizzati non devono emettere ID duplicati. Mantenere `aria-*`, i controlli JS e gli attributi `data-*` descritti nell'inventario esistente.
- Override nativo: `templates/NOME/html/com_jlms/VISTA/default.php`; non modificare file installati, non eludere autorizzazioni, token, filtri server o accesso ai materiali privati.

## Due personalizzazioni autonome da verificare

**Personalizzazione A — catalogo.** Nel CSS del template Joomla:

```css
#jlms-courses .jlms-course-card { border-radius: 1rem; }
```

**Personalizzazione B — area studente.** In un altro CSS di template, indipendente dal primo:

```css
#jlms-dashboard .jlms-my-course { padding: 1.5rem; }
```

Il test `python3 tests/frontend-dom-contracts.py` verifica staticamente che i selettori siano ancora supportati e che la mappa corrisponda alle classi e agli ID dei layout. Il test **non** dimostra la cascata effettiva in Joomla: provare separatamente i due CSS con template/override differenti, moduli sulla stessa pagina, responsive, browser, IT/EN e accessibilità.

## Verifiche e limiti

Il controllo impedisce la deriva dei contratti statici e la mancata mappatura di nuove viste `default.php`. Le dipendenze JS di video, audio, quiz e aula rimangono protette da `tests/frontend-contract-hooks.py`. Aggiungere CI e collaudo browser Joomla prima di considerare `UI-07` accettato manualmente; in particolare l'unicità degli ID a runtime con moduli e override personalizzati è una prova distinta. Non dedurre il completamento WCAG dalla sola scansione delle sorgenti.


## Regressione DOM su Joomla effettivo — tranche runtime

Il test `tests/frontend-dom-runtime.py` aggiunge un parser HTML riutilizzabile che verifica l'unicità degli ID JD Learn nel **documento renderizzato**, compresi moduli e partial: gli attributi `id` duplicati sono individuati senza distinguere l'origine dell'elemento. Controlla anche che i selettori di due personalizzazioni indipendenti abbiano davvero una relazione antenato-discendente valida: `#jlms-courses .jlms-course-card` per il catalogo e `#jlms-dashboard .jlms-my-course` per i corsi dello studente.

Il parser è chiamato nelle suite HTTP Joomla `tests/categories-http.py` (catalogo, categorie, dettaglio categoria, con SEF attivato e disattivato) e `tests/dashboard-http.py` (vista riepilogo, elenco dei corsi e navigazione opzionale). Ogni suite gira su un'installazione Joomla temporanea con dati controllati; le fixture negative vengono testate anche nel preflight di `tools/check.py`.

**Limiti residui:** la regressione automatica non copre ancora tutte le 17 viste su ogni combinazione di record, moduli, override di template o CMS di terze parti. La resa visiva effettiva di due CSS autonomi e l'accessibilità multibrowser restano collaudi manuali. Non promuovere il criterio UI-07/versioned_maps a implementato sulla sola copertura parziale runtime.

## Inventario completo dei partial e regole di composizione — UI-07

Il contratto versione **1.0.0** include ora, oltre alle 17 viste, **tutti e quattro i partial PHP distribuiti** in `component/site/tmpl/dashboard/`. Per ciascuno il JSON indica percorso, ID/classi letterali e insieme preciso delle viste che lo includono; `tests/frontend-dom-contracts.py` controlla entrambi i versi della relazione e fallisce quando appare un nuovo partial non censito.

| Partial | Viste consumatrici | ID pubblici letterali | Note di compatibilità |
| --- | --- | --- | --- |
| `_personal_navigation.php` | dashboard, mycourses, mycertificates, preferences | `#jlms-personal-navigation` | Condizionale al parametro del menu; massimo una navigazione per documento |
| `_courses.php` | mycourses | `#jlms-my-courses`, `#jlms-dashboard-empty`, `#jlms-dashboard-empty-catalog` | ID dinamici delle schede da iscrizione, univoci per utente/pagina |
| `_certificates.php` | mycertificates | Nessun ID letterale; classi delle schede | I link non devono ignorare i controlli ACL del controller |
| `_preferences.php` | preferences | `#jlms-course-announcement-preferences`, `#jlms-course-announcement-title`, `#jlms-mail-consent-state` | Gli stati del consenso sono alternativi, non duplicazioni visibili contemporaneamente |

Nessun modulo Joomla è distribuito come parte del package base (il manifest `packages/pkg_jlms.xml` lo rende verificabile). Moduli di terze parti possono comparire nella stessa pagina: i controlli su HTML renderizzato `tests/frontend-dom-runtime.py` rifiutano la duplicazione di identificatori `jlms-*` fra componente, partial e contenuto di modulo; le suite HTTP Joomla esercitano i percorsi core già predisposti. L'assenza di un modulo nel package **non dimostra** la compatibilità di moduli/override esterni non installati.

### Contratto di modifica e personalizzazione

I selector CSS A (`#jlms-courses .jlms-course-card`) e B (`#jlms-dashboard .jlms-my-course`) rimangono indipendenti, senza rimozione di hook preesistenti. Una modifica incompatibile a ID, classi o `data-*` richiede prima la strategia di alias/migrazione, l'aggiornamento della mappa e della guida, nonché un changelog e una versione contrattuale esplicita. Un override di template non può aggirare ACL, CSRF o accesso alle risorse private.

**Implementazione del contratto DOM:** le 17 mappe di vista, i quattro partial, le dipendenze di inclusione, le personalizzazioni indipendenti e i casi negativi di collisione sono coperti da test automatici. **Accettazione Joomla ancora aperta:** verificare su template reali le varianti condizionali, componenti e moduli insieme, browser/mobile, CSS nel contesto della cascata e accessibilità con gli scenari AC-34 e AC-93. Non attribuire a queste verifiche statiche un risultato manuale o una certificazione WCAG.
