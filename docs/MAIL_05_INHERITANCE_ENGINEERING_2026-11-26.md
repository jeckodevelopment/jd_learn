# MAIL-05 — Matrice di ereditarietà delle preferenze email (26 novembre 2026)

## Intervento applicativo

Il criterio MAIL-05 distingue gli interruttori globali di Joomla dalle preferenze per corso. Sono supportati **16 eventi** dal servizio `MailPreferences`; 15 presentano un interruttore per corso e un interruttore globale nel form Joomla. L'evento `announcement` segue il flusso dedicato con consenso esplicito e non viene aggiunto arbitrariamente agli altri interruttori.

Un contenuto `mail_preferences` non-array memorizzato nei parametri del corso viene ora rifiutato in modo sicuro (false) anziché essere letto come mappa. Le configurazioni valide conservano la precedenza prevista: `inherit` segue il valore globale; `enabled` e `disabled` prevalgono per il singolo corso. `merge` conserva le opzioni di corso non legate alla posta e `inherit` rimuove l'override.

## Prove implementative

- `tests/joomla-smoke.php`: prova in Joomla installato 16 eventi × 2 stati globali × 3 stati per corso; verifica anche `merge`, ripristino ereditarietà, conservazione opzioni e valori corrotti.
- `tests/mail05-event-contract.py`: blocca derive fra la lista eventi, 15 campi globali e 15 campi per corso nel manifest, con fixture negative.
- `tools/check.py`: invoca il gate nel preflight.

## Significato della percentuale

Il 63,76% attuale è un **minimo dimostrato**, non la percentuale stimata di funzioni programmate. L'eventuale promozione del criterio tecnico `MAIL-05/inheritance` richiede **CI verde e aggiornamento dei file generati di audit**; non autorizza alcuna accettazione manuale e non risolve i 240+ criteri estesi non verificabili.

## Comportamenti da collaudare manualmente

In Joomla 6.2.1, cambiare le preferenze globali e per corso per ogni evento di comunicazione, verificare la visualizzazione corretta dei form in italiano e inglese e invocare i task con utenti consenzienti e non consenzienti; l'evento annuncio deve rispettare il proprio doppio opt-in. Verificare gli invii effettivi SMTP con i template installati. **Stato: non eseguito.**
