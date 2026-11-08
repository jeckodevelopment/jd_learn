# Disponibilità operativa del corso

Dal roster degli iscritti al corso, aprire aprire **Disponibilità del corso**. I quattro comandi sono indipendenti:

- **Chiudi nuove ammissioni** blocca gli avvii automatici e le assegnazioni manuali/preassegnazioni. Il corso resta visibile secondo pubblicazione, lingua e livelli Joomla; iscrizioni già esistenti restano fruibili se i loro normali controlli di accesso lo consentono.
- **Apri nuove ammissioni** ripristina i percorsi di ammissione già previsti da modalità, edizione, lingua, ACL e capienza.
- **Sospendi erogazione** blocca le azioni didattiche protette per tutte le iscrizioni del corso senza modificarne stato o progressi. Quiz e risposte salvate restano memorizzati; i quiz temporizzati seguono comunque la loro scadenza server. Le risorse già trasferite al browser e i token provider già emessi non sono revocati retroattivamente.
- **Riprendi erogazione** ripristina la fruizione, subordinata agli accessi Joomla correnti, allo stato dell’iscrizione e alle finestre temporali. Non riapre tentativi chiusi né altera timer, risposte o progressi.

Ogni comando GET mostra un’anteprima con iscritti attivi/completati e tentativi quiz aperti. Per applicare la modifica servono motivo, conferma e token CSRF Joomla. Il POST verifica di nuovo autorizzazione per corso, revisione e hash dell’anteprima in transazione. Se lo stato è cambiato, la richiesta va ripreparata. Il comando imposta un valore esplicito e non è un toggle al reinvio.

Lo storico dedicato registra operazione, valore precedente/nuovo, motivo, attore, revisione e data UTC; la stessa transazione aggiorna il corso e lo storico e verifica la scrittura nell’Action Log nativo Joomla. Se uno dei due audit fallisce, la modifica viene annullata. Non vengono assegnati gruppi Joomla, toccate le iscrizioni individuali o inviate notifiche. Le due leve sono indipendenti: sospendere l’erogazione non chiude da solo le ammissioni.

La funzione è introdotta nella 0.41.9-dev, integrata in main con la PR #70 (merge 70f6ed7). La CI standard e l’upgrade automatico del pacchetto su sito effimero sono superati (run 42428496835); il collaudo manuale Joomla resta da completare. Le risorse già consegnate a sistemi esterni, il controllo live dei provider e il collaudo browser non sono certificati da questa interfaccia.
