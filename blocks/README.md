# Blocchi Gutenberg ACF + Timber

Ogni cartella è un blocco autonomo e copia-incollabile tra progetti:

```
blocks/<slug>/
├── block.json    # registrazione (apiVersion 3, render via bizen_block_render)
├── <slug>.twig   # template Timber del blocco
├── fields.json   # field group ACF (salvato qui automaticamente)
├── style.css     # CSS per-blocco: frontend + iframe editor (opzionale)
└── preview.png   # screenshot mostrato nell'inserter (opzionale)
```

## Attivazione

In `functions/blocks.php` imposta:

```php
define('GUTENBERG_ENABLED', true);
define('GUTENBERG_CUSTOM_BLOCKS_ENABLED', true);
```

Per setup misti (blocchi solo su alcune pagine, flexible content sulle altre)
usa `GUTENBERG_ALLOWED_SLUGS` / `GUTENBERG_ALLOWED_IDS`.

## Creare un nuovo blocco

```bash
npm run make:block -- hero "Hero"
```

Oppure a mano:

1. Duplica `blocks/section` → `blocks/<slug>`
2. Rinomina `section.twig` → `<slug>.twig`; in `block.json` aggiorna `name`
   (`bizen/<slug>`), `title`, `icon`, `keywords`
3. **Elimina `fields.json`** dalla copia (le chiavi ACF duplicate confliggono),
   poi crea il field group da admin ACF con location `Blocco == <Titolo>`:
   ACF lo salva da solo nella cartella del blocco
4. Sostituisci `preview.png` con uno screenshot reale del blocco

## Context disponibile nel Twig

| Variabile | Contenuto |
|---|---|
| `fields` | tutti i campi ACF del blocco (`get_fields()`) |
| `block` | array del blocco (name, id, anchor, align, className, data…) |
| `block_id` | anchor scelto nell'editor o fallback `block-{id}` |
| `classes` | `block-<slug>` + className + align precalcolati |
| `is_preview` | `true` nell'editor |
| `content` | inner blocks renderizzati (frontend) |
| `post_id` | ID del post in editing |

## Note

- **InnerBlocks**: richiede `"supports": { "jsx": true }` in block.json;
  usa `<InnerBlocks />` nel twig.
- **CSS nell'editor**: con apiVersion 3 l'editor è in iframe. Gli stili del tema
  arrivano via `add_editor_style()` (vedi `setup.php`); quelli per-blocco via
  `"style": "file:./style.css"` in block.json. Non usare
  `enqueue_block_editor_assets` per il CSS dei blocchi: carica fuori dall'iframe.
- **Anteprima inserter**: il pattern `example.attributes.data.is_example` in
  block.json fa mostrare `preview.png` al posto del render reale.
