# PROJECT.md — stato del progetto corrente

> Template vuoto: compilarlo a inizio progetto e tenerlo aggiornato AD OGNI decisione o gotcha.
> È il log vivo del progetto, importato da CLAUDE.md. Le convenzioni framework NON vanno qui.

## Progetto

- **Cliente / sito**:
- **URL locale (proxy BrowserSync)**:
- **URL produzione**:
- **Materiali design**: (path XD/PDF/Figma)
- **Plugin chiave attivi**: ACF Pro, CF7, Yoast, … (WPML? WooCommerce?)

## Design system applicato

- Palette (`@theme`): accent = …, light = …, dark = …, darker = …
- Font: … (woff2 in assets/webfonts/, pesi precaricati: …)
- Note tipografiche / scostamenti dalla scala di default:

## Contenuti

- **CPT**: (nome → slug, campi principali, file acf-json)
- **Tassonomie**:
- **Options pages**: campi aggiunti oltre ad Anagrafica/Opzioni Tema/Avanzate:
- **Menu**: location usate e struttura

## Moduli

- Moduli installati dalla dispensa `library/`: (elenco)
- `sizes` riviste dopo l'installazione? (vedi `library/README.md`; vanno rifatte se il container prende una max-width)
- **Moduli custom di progetto**: (nome → gruppo ACF → twig → note)
- Pagina libreria moduli (seed): (ID/slug)

## Sistema contenuti

- [ ] Solo flexible content (default)
- [ ] Gutenberg attivo su: … (GUTENBERG_ALLOWED_SLUGS/IDS in functions/config.php)

## Decisioni prese

<!-- Data → decisione → perché. Es: "2026-07-10 — showroom come CPT e non pagine: servono in archivio filtrabile" -->

## Gotcha di progetto

<!-- Trappole incontrate QUI (quelle generali stanno in CLAUDE.md → Gotcha ACF) -->

## TODO / Roadmap

- [ ]
