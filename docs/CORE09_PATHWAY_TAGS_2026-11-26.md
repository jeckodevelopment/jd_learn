# CORE-09 — tag nativi per i percorsi

Questa modifica estende ai percorsi l'integrazione di tag Joomla già usata per corsi e profili docente.

- Il form percorso usa il campo core Tag.
- PathwayTable implementa TaggableTableInterface e TaggableTableTrait con type alias com_jlms.pathway.
- EditorialInstaller registra o aggiorna il content type UCM del percorso, inclusi i campi item ID, descrizione, ACL, categoria e lingua necessari a TagsHelper.
- PathwayModel carica la selezione esistente, importa il behaviour taggable nel salvataggio custom e ammette l'input tags nell'allowlist protetta.
- La fixture Joomla verifica registrazione UCM, assegnazione, reidratazione, rimozione e riassegnazione effettive dell'associazione.

Il criterio CORE-09 resta unverifiable e non viene promosso finché CI completa sulle due matrici. Il collaudo manuale Joomla resta separato: ricerca tag, ACL di accesso e comportamento della scheda percorso devono essere verificati sul pacchetto installato.
