# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] 2026-11-05
### Added
- Toolbar flottante in fondo alla pagina, di proprietà di cz-core (caricata da `mu-plugins/cz-core.php`), che sostituisce quella di cz-continue-reading (`.czcr-toolbar`).
- API PHP: `cz_toolbar_add_button( $id, $args )` e `cz_toolbar_remove_button( $id )`, da chiamare sull'hook `cz_toolbar_buttons`. Ogni pulsante ha etichetta, icona SVG, zona (`left` / `center` / `right`), ordine, classi e attributi aggiuntivi, ed è reso come `<button class="cz-toolbar__btn" data-cz-toolbar="<id>">`.
- Pulsanti di base: Home (sinistra), Su e Stampa (centro). Stampa emette l'evento annullabile `cz-toolbar:print` su `document` e, se nessuno lo annulla, avvia `window.print()`.
- Filtro `cz_toolbar_enabled` (default: singoli articoli).
- Layout a tre zone: su mobile sinistra, centro e destra della barra; da 768px un'unica fila centrata. Solo i pulsanti intercettano i tocchi, gli spazi vuoti della barra no.
- Toolbar nascosta in stampa.
