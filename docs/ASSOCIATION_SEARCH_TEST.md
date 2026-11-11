# Collaudo del selettore traduzioni

Riferimenti: IMP-07, CORE-07, AC-20 e AC-48. Il filtro locale restringe per titolo o ID i corsi già autorizzati elencati nel form. Il limite rimane 2.000 candidati per lingua; il server ripete in modo indipendente i controlli di lingua, ACL, checkout e coerenza del gruppo.

## Comportamenti da testare in Joomla

1. Apri un corso con almeno 20 corsi eleggibili nella lingua di destinazione. Digita una parte del titolo: devono restare selezionabili solo le corrispondenze.
2. Cerca un ID completo o un frammento dell'ID mostrato accanto al titolo e seleziona il risultato atteso.
3. Inserisci un testo senza corrispondenze: l'opzione per scollegare deve restare disponibile e nessun altro corso deve essere selezionabile.
4. Cancella il filtro, seleziona una traduzione e salva. Riapri la scheda e verifica che la selezione sia persistita.
5. Ripeti su due lingue: il filtro di un campo non deve alterare l'elenco dell'altro.
6. Cerca un corso privo dei permessi di modifica e uno in checkout da un altro operatore: nessuno dei due deve comparire fra i candidati.
7. Salva con il filtro attivo e verifica che il server continui a rifiutare lingue errate, target non autorizzati e corsi già collegati a un altro gruppo.
