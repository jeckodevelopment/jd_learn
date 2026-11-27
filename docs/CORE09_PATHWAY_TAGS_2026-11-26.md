# CORE-09 — tag nativi per i percorsi

Questa modifica estende ai percorsi l'integrazione di tag Joomla già usata per corsi e profili docente.

- Il form percorso usa il campo core Tag.
- PathwayTable implementa TaggableTableInterface e TaggableTableTrait con type alias com_jlms.pathway.
- EditorialInstaller registra o aggiorna il content type UCM del percorso, inclusi i campi item ID, descrizione, ACL, categoria e lingua necessari a TagsHelper.
- PathwayModel carica la selezione esistente, importa il behaviour taggable nel salvataggio custom e ammette l'input tags nell'allowlist protetta.
- La fixture Joomla verifica registrazione UCM, assegnazione, reidratazione, rimozione e riassegnazione effettive dell'associazione.

CI #4747 è verde 4/4: PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6, Chromium e gate documentali. CORE-09/tags_context è stato riconciliato come implemented per il perimetro software di corsi, docenti e percorsi. Sviluppo documentato aggiornato a 65,04% (510 implementati, 9 mancanti, 238 non verificabili); collaudo manuale invariato a 4,67% (10/214 con esito, zero completi). Il collaudo manuale Joomla resta separato e in attesa: AC-19/AC-64 su assegnazione/rimozione/riassegnazione, ricerca tag, ACL, lingua, visibilità e upgrade sul pacchetto installato.
