# Registro strutturato dei collaudi Joomla — JD Learn 1.0.0

Fonte macchina: [MANUAL_QA_EVIDENCE.json](MANUAL_QA_EVIDENCE.json). Questo registro accompagna, **non sostituisce**, `REQUIREMENTS_STATUS.csv`, `QA_TESTBOOK.md` e il piano di rilascio.

## Scopo e semantica

Ogni voce rappresenta **uno scenario verificato** su una **build identificata**, non l'accettazione completa di uno o più requisiti. Le colonne `Accettazione Joomla` nella matrice restano la fonte ufficiale dello stato *aggregato*; una voce con `result=passed` e `scope=partial` non può aggiornare da sola lo stato in `Completata`.

- `id`: identificativo stabile dello scenario.
- `requirement_ids`: uno o più codici della matrice esistente, verificabili.
- `result`: `passed`, `failed`, `blocked` o `not_run`. Non registrare `passed` in assenza di conferma effettiva.
- `scope`: `partial` o `full`; `full` è ammissibile solo quando tutti i criteri di quel requisito sono stati coperti da evidenza e la matrice è allineata.
- `tested_at`: data ISO della verifica, non del merge o della CI.
- `build.version`: versione installata realmente verificata.
- `build.artifact_url`: link all'artefatto installato, se noto; `build.commit`: commit di riferimento, se documentato. Se identificazione incerta, non inventare la build: registrare `null` e descrivere la lacuna.
- `evidence_url` e `evidence_note`: fonte verificabile e perimetro della verifica, includendo esplicitamente ciò che **non** è stato accettato.

## Procedura dopo un collaudo reale

1. Installare la build identificabile, annotando versione e link all'artefatto.
2. Eseguire lo scenario riportato nel testbook, registrare esito e data.
3. Collegare un'evidenza depurata da dati riservati (PR, report, nota di test); **non** inserire URL completi delle installazioni di prova, nominativi reali, cookie o credenziali.
4. Aggiungere o aggiornare una voce nel JSON con PR documentale tracciata.
5. Aggiornare `REQUIREMENTS_STATUS.csv` solo se il risultato giustifica una variazione dello stato aggregato. La sola esecuzione di un test parziale **non** giustifica accettazione completa.
6. I dati esposti nella dashboard sono di sola lettura e riportano la data e il riferimento all'artefatto, quando noti. Dopo un nuovo merge, i collaudi storici non diventano automaticamente non validi, ma va valutata l'eventuale necessità di ripetere gli scenari modificati.

## Registro iniziale

È stata trascritta **una sola verifica documentata** dal piano: il download dell'attestato PDF preesistente con logo PNG dopo installazione dell'artefatto della PR #566, confermato il 17 novembre 2026. Resta **parziale** rispetto ai requisiti CERT-03/05/08; non prova gli ulteriori scenari, compresa la conferma dello stato backend `ready`.

Non sono stati creati esiti fittizi per requisiti senza prova e non sono stati cambiati stati nella matrice.

## Comportamenti da testare

- [ ] Un caso presente nel registro appare nella scheda Collaudi associato ai requisiti corretti, con versione, data ed evidenza.
- [ ] I requisiti senza casi tracciati mostrano esplicitamente che mancano evidenze strutturate.
- [ ] Un caso `partial/passed` non trasforma il requisito in `Accettato`.
- [ ] Un `failed` o `blocked` è visibile e non si trasforma in stato completato.
- [ ] I collegamenti agli artefatti sono apribili senza divulgare dati di siti di prova.
- [ ] Una build più recente non cancella la cronologia dei casi precedenti.
