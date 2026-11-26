# LES-01–04 — chiusura del perimetro implementativo (26 novembre 2026)

## Decisione e perimetro

Il lotto di sicurezza LES-02 uniforma i contenuti che JD Learn memorizza e rende in pubblico/privato, senza reimplementare i servizi di account, CDN o provider Joomla.

- **LES-01:** 4/4 criteri implementati; nessun residuo tecnico nel programma, negli allegati versionati e nelle lezioni obbligatorie/facoltative.
- **LES-02:** il filtro degli editor nativi di corso, percorso, docente e lezione viene imposto indipendentemente dai permessi del salvatore. Corpo di lezione e trascrizioni usano ora l'allowlist \`CoursePresentation::html\` *dopo* il filtro del contesto Joomla, con seconda sanificazione in aula e nell'anteprima pubblica per le versioni storiche. La protezione si applica anche alle URL con credenziali e ai collegamenti \`href\` o \`rel\` non quotati che possono essere emessi dagli editor. L'incorporamento video resta consentito **solo** nei provider registrati, non tramite iframe arbitrari.
- **LES-03:** la consegna autorizzata di materiali e Range MP4 è implementata. Il criterio \`public_storage\` verifica topologie di web server/proxy effettive, non un nuovo storage applicativo da scrivere: la CI prova il contenitore cifrato/guard e le negazioni HTTP, ma non certifica ogni hosting.
- **LES-04:** il componente non offre transcoding né CDN illimitata; i limiti di upload/provider sono applicati, e \`capacity_policy\` riguarda dimensionamento e benchmark sull'ambiente configurato.

## Evidenza del lotto

| Superficie | Correzione o prova automatica |
| --- | --- |
| Corpo lezione, editor Joomla | \`CurriculumService::saveLesson\`, \`tests/joomla-curriculum.php\` (test HTML attivo e URL credenziali con editor privilegiato) |
| Trascrizione audio/video | \`CurriculumService::saveLesson\`, \`LearningService::classroom\` (allowlist a scrittura e lettura) |
| Aula: versioni storiche | \`LearningService::classroom\`, \`tests/joomla-learning.php\` (fixture storica contaminata, nessuna esecuzione del markup) |
| Anteprima pubblica: versioni storiche | \`CatalogService::publicLessonPreview\`, \`tests/joomla-publication.php\` (fixture corrotta, nessuna fuga di credenziali) |
| Link esterni e referrer | \`EditorialLinkSafety::secure\`, \`tests/run.php\` (href/rel quotati e non quotati, userinfo, query, fragment, protocolli) |
| Presentazione corso, docente, percorso | \`CoursePresentation::html\`, \`TeacherModel::save\`, \`PathwayModel::save\`, \`tests/joomla-course-presentation.php\`, \`tests/joomla-teachers.php\`, \`tests/joomla-pathways.php\` (regressioni preesistenti) |
| Provider autorizzati | \`PublicationService\`, \`tests/joomla-video.php\` e \`tests/video-http.py\` (contratti/provider preesistenti) |
| Materiali privati e hosting | \`tests/materials-http.py\`, \`tests/html5-http.py\`, \`ProtectedMaterialStorage\`; hosting reale da collaudare |

**Nota di precisione:** il superamento dei singoli test dimostra gli scenari indicati, non ogni possibile plugin/editor/hosting non distribuito con JD Learn. Eventuali estensioni che generano HTML dopo la sanitizzazione JD Learn, provider esterni e proxy sono soggetti al collaudo Joomla e all'audit del sito. Nessun dato di accettazione manuale è stato inventato.

## Esito dei residui

I due criteri LES-02 \`arbitrary_embeds\` ed \`external_credentials\` hanno ora una correzione implementativa e regressioni di sicurezza aggiuntive; la chiusura di sviluppo è **candidata** e richiede la CI verde sul commit della PR. I due criteri LES-03 \`public_storage\` e LES-04 \`capacity_policy\` sono **dipendenze di hosting/operatività**, non nuove funzioni JD Learn. I quattro criteri precedentemente \`unverifiable\` mantengono tale stato nel registro di accettazione estesa, finché non è dimostrata l'intera formulazione; non forzare la percentuale globale per aver aggiunto regressioni.

## Collaudo manuale (separato)

**NON ESEGUITO.** Vedi \`docs/QA_TESTBOOK.md\`. Provare editor Super User/Editor/docente, paste da editor Joomla/estensioni, formato corso/percorso/docente e lezioni in aula/preview, corpo e trascrizione audio/video, link a siti esterni, proxy HTTPS e CDN, accesso diretto allo storage, carico storage/banda. Registrare su Joomla 6.2.1 le combinazioni che funzionano e i difetti riproducibili. Un KO che riguarda JD Learn riapre un bug tecnico; non delegare all'utente attività di sviluppo.
