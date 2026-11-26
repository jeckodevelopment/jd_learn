# CORE-12–16 — controllo implementativo e delimitatori delle route SEF

## Intervento del 26 novembre 2026

La ricognizione del codice esistente evidenzia servizi già implementati per i provider video (CORE-12), l'indicizzazione Finder (CORE-14), i workflow editoriali (CORE-15) e il controllo di versioni/checkout (CORE-16). Questa PR **non** dichiara completati i relativi criteri di accettazione estesa.

### Difetto riproducibile CORE-13

Il decoder delle sette route pubbliche supporta ID numerici, `ID:alias` nativi e `ID-alias` legacy. La regex precedente ammetteva un secondo carattere `:` nel corpo dell'alias, cosicché `42:alias:extra` risolveva erroneamente l'ID 42 anziché invalidare il segmento.

Il router ora esclude il separatore `:` dall'alias; gli slug Unicode (`42:opera-à-italiana`) e gli alias composti con trattini continuano a essere supportati. Il decoder restituisce 0 per segmenti irregolari, come già avviene con overflow e suffissi di query/fragment.

### Evidenza CI

La regressione `tests/secdev02-core13-router-segments.php` prova, per sette decoder, sia i casi validi sia `42:alias:extra`, `42:alias::extra`, `42-alias:extra`. Nessun nuovo servizio parallelo al router Joomla.

### Perimetro e collaudo

Gli stati CORE-12–16 rimangono **Parziale** secondo la matrice canonica. Nessuna percentuale è aumentata per il solo rafforzamento del parser.

**Collaudo manuale: NON ESEGUITO**. Su Joomla 6.2.1, provare link SEF e non SEF per corso, categoria, docente e percorso; accessi diretti malformati e URL associati IT/EN, breadcrumb, ACL e cambi di alias. Per CORE-12, CORE-14–16 restano le prove su provider, motore Finder, workflow ed editing concorrente previste dal testbook.
