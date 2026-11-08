# Profili e associazioni docente

Creare un profilo, collegare facoltativamente l’account Joomla e associarlo ai corsi dalla scheda Docenti. Il form non richiede una categoria. I campi si gestiscono da JD Learn → Campi aggiuntivi nella sidebar: il filtro contesto nativo di com_fields include Docenti (`com_jlms.teacher`) oltre a Corsi e Lezioni.

La segreteria può definire campi testo, email o altri tipi installati, con lingua, accesso e ACL native. La presentazione di base, la foto e il sito rimangono disponibili. La pubblicazione dei valori aggiuntivi rispetta le ACL dei campi. Il docente collegato può modificare il proprio profilo con core.edit.own e i valori dei campi autorizzati con core.edit.value. La segreteria conserva jlms.teachers.manage e i normali permessi core.

Le nuove voci di menu Pagina docente e Corsi di un docente usano il profilo selezionato. I corsi elencati sono solo quelli visibili all’utente corrente. La pagina completa include presentazione, foto e campi aggiuntivi. Le associazioni preesistenti e le categorie tecniche restano conservate per compatibilità.

Le schede pubbliche mostrano il profilo corrente. Gli snapshot delle versioni didattiche già pubblicate rimangono congelati per lo storico degli iscritti.


## Gestione amministrativa e conteggio corsi

La lista Docenti usa Search Tools e toolbar Joomla. La colonna Corsi conta le associazioni ai corsi che l’utente può gestire; il collegamento apre Corsi con il filtro Docente selezionato. La lista Corsi offre lo stesso filtro, combinabile con ricerca e filtri esistenti. I risultati restano limitati ai corsi gestibili dall’utente e le etichette della vista sono tradotte nella lingua amministrativa. La prova completa è in [QA_TESTBOOK.md, AC-133](QA_TESTBOOK.md#ac-133--conteggio-corsi-e-filtro-docente-issue-117).
