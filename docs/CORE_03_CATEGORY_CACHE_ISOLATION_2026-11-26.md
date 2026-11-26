# CORE-03 — separazione della cache dei risultati categorie

## Correzione implementativa (26 novembre 2026)

Il modello Joomla `CategoriesModel` esegue query il cui risultato dipende
dall'identità e dalla lingua correnti (livelli Joomla, pubblicazione,
filtri ereditati e lingua). A differenza di `CoursesModel` e
`TeachersModel`, non personalizzava `getStoreId()` per l'isolamento
delle cache del modello.

L'override introdotto aggiunge ID identità e tag locale al
`getStoreId()` nativo, preservando l'ordinamento, il parent ID e la
paginazione del core. La prova `tests/joomla-categories.php` ricava
il cache key del medesimo modello con identità guest ed editor: deve
essere diverso; tornando a guest deve essere identico al primo.

## Limiti delle evidenze

La verifica dimostra il confine del cache key, non attesta ogni
possibile modalità di caching di hosting e proxy. I controlli ACL
continuano a essere eseguiti nelle query delle categorie.

CORE-03 e SECDEV-02 restano **Parziale** fino alle verifiche previste.
Nessuna accettazione manuale viene attribuita.

## Collaudo manuale Joomla

Stato: **NON ESEGUITO**

- Su Joomla 6.2.1, creare una categoria privata visibile solo a un
  livello di accesso dedicato e una categoria pubblica.
- Accedere prima come utente autorizzato, poi come guest e altro utente:
  la categoria privata non deve apparire agli utenti non autorizzati.
- Ripetere dopo cambio lingua, revoca accesso e aggiornamento della
  categoria, con cache Joomla eventualmente attivata.
