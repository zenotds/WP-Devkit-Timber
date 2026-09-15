# Framework di sviluppo — WP DevKit Timber v7.5 (Zeno / Bizen)

Base **riutilizzabile** per temi WordPress + ACF Pro + Timber 2 + Tailwind 4.
Distillata da temi di produzione.
Questo file descrive le convenzioni **stabili del framework**: in un nuovo progetto resta invariato.

> Lo stato **specifico del progetto corrente** (inventario, decisioni, ID reali) vive in `PROJECT.md`
> (questa cartella), importato qui sotto. Tenerlo aggiornato è parte del lavoro, non un extra.

@PROJECT.md

Rispondere a Francesco **in italiano**.

## Filosofia

- **Flexible content di default**: i layout di pagina si costruiscono con ACF Flexible Content
  (`components/block-*.twig`). I blocchi Gutenberg (`blocks/`) sono un'opzione per-progetto,
  attivabile in `functions/config.php` — anche in setup misti (Gutenberg solo su pagine specifiche).
  La scelta è di sviluppo, a inizio progetto: nessun toggle runtime.
- **Il design è una linea guida, non una specifica**: XD/PDF indicano direzione e gerarchia;
  le misure si adattano ai breakpoint Tailwind. Mai pixel-perfect a ogni costo.
- **Utility-first con giudizio**: Tailwind nei template; CSS custom solo per pattern ricorrenti
  (menu, form, bottoni) o cose che le utility non esprimono bene.
- **Accessibilità non negoziabile**: aria-*, focus management, `prefers-reduced-motion`,
  niente pattern che nascondono contenuto al focus.
- **Performance di default**: immagini AVIF/WebP lazy, font self-hosted con preload, script defer,
  niente librerie quando bastano CSS/Alpine.
- **Commenti asciutti**: una riga che dice cosa fa il blocco e gli eventuali requisiti.
  Niente parafrasi della riga successiva. Mai newline dentro `{# #}` / `/* */`.
- **Verifica via utente, non browser headless**: implementare, riassumere, lasciar verificare
  visivamente. Con watch attivo (BrowserSync) NON lanciare `npm run build` a ogni modifica.

## Struttura

```
functions/   PHP a responsabilità singola: config, setup, twig, acf, forms, menus, enqueue, custom, avif, blocks
             (+ logic.php da creare per query/logica di dominio del progetto)
templates/   Twig: base.twig → partial/ (header, menu, footer, macros, pagination) → components/
             (block-*.twig, un file per layout flexible) → html/ tools/ + cartelle feature di progetto
blocks/      Blocchi Gutenberg ACF (opzionali): un folder per blocco con block.json + fields.json + twig + css
library/     Dispensa: moduli pronti ma NON attivi (twig + fields.json + extra) — si copia dentro ciò che serve
dev/css/     styles.css (importer + @theme) → base/ layout/ partial/ components/ plugins/
dev/js/      scripts.js (entry + init) + custom/custom.js (utility riusabili)
assets/      output compilato — mai editare a mano
acf-json/    field group versionati + CPT/tassonomie/options di progetto
```

- PHP: `StarterSite extends Timber\Site`; contesto globale con `settings` (ACF options via
  `get_fields('options')`), menu, breadcrumbs Yoast, `request` (GET/POST sanitizzati).
- **Naming**: il codice framework ha nomi senza prefisso progetto (`theme_*`, `acf_*`, `editor_*`,
  `custom_*`, `hidelabel_*`) → si copia com'è. Ciò che è specifico del sito usa il prefisso del
  progetto: funzioni di dominio in `functions/logic.php` (es. `mobi_*`) e costanti-dato.
- CPT e tassonomie via **ACF JSON** (`post_type_*.json`, `taxonomy_*.json`), non in PHP.

## Configurazione per-progetto (i "seam")

Ogni valore da cambiare a inizio progetto ha UN punto di modifica (checklist completa in `START.md`):

| Cosa | Dove |
|---|---|
| Namespace blocchi, flag Gutenberg | `functions/config.php` (THEME_NAMESPACE, GUTENBERG_*) |
| Chiavi API (GMaps…) | `wp-config.php` (`GMAPS_API_KEY`) — MAI nel tema versionato |
| Proxy BrowserSync, browser, watch | `devkit.config.json` (`esbuild.js` NON si tocca: resta identico tra progetti) |
| Palette e tipografia | blocco `@theme` in `dev/css/styles.css` (token funzionali, vedi CSS) |
| Palette editor WYSIWYG | `editor_color_palette()` in `functions/acf.php` (allineata ai token) |
| Font | `dev/css/base/fonts.css` + preload in `functions/enqueue.php` |
| FontAwesome Pro | aggiunta manuale: CSS in `dev/css/fontawesome/`, woff2 in `assets/webfonts/`, scommentare gli import in `styles.css` |

## Build

- `npm run watch` (dev + BrowserSync) / `npm run build` (prod: minify + bump versione in style.css).
- Entrypoint per convenzione: ogni `.js` top-level in `dev/js/` e ogni `.css` top-level in `dev/css/`
  diventa un bundle `assets/{js,css}/<nome>.min.*`. Un file in più = un bundle in più.
- Build su `esbuild.context()` riusato: il rebuild è incrementale. Il context si ricrea da solo
  quando cambia l'elenco degli entrypoint (file aggiunto o rimosso in `dev/js/` o `dev/css/`).
- **Cambi CSS iniettati senza reload** (`bs.reload("*.css")`): la pagina non perde lo stato —
  menu aperti, modali, posizione di scroll. Twig/PHP/JS fanno reload pieno.
- Sourcemap solo in sviluppo (in produzione esporrebbero i sorgenti) e mai committati.
  Gli asset compilati invece SÌ, i lockfile npm/composer pure.
- Niente autoprefixer: Tailwind 4 fa prefixing e nesting da sé (Lightning CSS).
- `normalizeFontPaths()` riscrive i percorsi webfont dei CSS compilati in `../webfonts/`.
- Il flag `DEBUG` di scripts.js è iniettato da esbuild (`NODE_ENV`): log in dev, silenzio in prod.
- `wp-cli` dal tema con `--path=../../..` — usarlo per verificare/seedare campi ACF e contenuti.

## CSS / Tailwind 4

- **Config via `@theme` in `dev/css/styles.css`** — niente tailwind.config.js.
- **Token funzionali, mai nomi colore**: `--color-accent` (brand), `--color-light` (bande chiare),
  `--color-dark` (bande scure/testi forti), `--color-darker`, `--color-body` (testo corrente),
  `--color-error` (validazione form), `--color-focus` (anello di focus, deve restare visibile su
  ogni sfondo). Varianti derivate con `oklch(from var(--color-accent) ...)` se servono.
  Zero classi della palette Tailwind di default (`slate-*`, `neutral-*`, `sky-*`…) nel codice.
- **Niente `@apply`**: nei file CSS si scrive CSS, usando i token come custom property —
  `padding: calc(var(--spacing) * 4)`, `color: var(--color-accent)`, `font-size: var(--text-sm)`,
  `border-radius: var(--radius-md)`. Le utility stanno nei twig, non dentro il CSS.
- **Cascata a layer espliciti**, dichiarati negli `@import` di styles.css:
  `base` (reset) → `components` (tutto il CSS del tema e dei plugin) → `utilities` (Tailwind) →
  `overrides`. Conseguenza pratica: **una utility nel twig batte sempre il CSS di componente**.
  In `overrides` ci va solo ciò che deve vincere per forza: oggi il solo `layout/section-bg.css`
  (l'inversione testi di `data-bg="dark"` deve battere un `text-accent` scritto nel markup).
- **Stato di un componente e suo stile base nello STESSO file.** Se lo stato sta nel CSS
  (`.nav-item.active`) e la base nelle utility del twig (`after:scale-y-0`), lo stato non vince
  più: le utility sono in un layer superiore. Vale per l'underline del main-menu e per l'hover
  del mobile-menu, entrambi ora interamente in `partial/`.
- Tipografia fluida: `--text-hero/h1…h6/lead/quote` con `clamp()` (rif. 1920→375px), line-height nei
  token. Tailwind 4 emette solo i token usati.
- **Ritmo verticale** (`layout/common.css`): il padding lo porta `.main > *`
  (`padding-block: var(--section-pad)`); gli sfondi `data-bg` dipingono e si saldano tra sezioni
  adiacenti. Full-bleed (`page-header`, `block-panel`) → `padding-block: 0`, spazio interno dal
  contenuto. Due moduli senza sfondo consecutivi: il secondo dimezza il padding-top (gap 1.5×);
  primo modulo bianco dopo banda `data-bg`: padding-top 1.5×. **Niente spaziatura verticale nei
  twig dei moduli.** Sezioni edge-to-edge di progetto: aggiungerle alle esclusioni in common.css.
- **`data-bg="dark"` inverte i testi in bianco** su tutta la sezione (`layout/section-bg.css`).
  Elementi con superficie chiara propria (card/accordion `bg-light`) marcati `.surface`: dentro
  `[data-bg="dark"]` ripristinano testo scuro/accent. Immagine di sfondo additiva: `.has-bg-image`
  sulla sezione + `.bg-media` sulla picture (grayscale+multiply).
- **`[x-cloak]{display:none!important}` è VITALE**: presente in `base/base.css` E inline in
  `html/head.twig` (anti-FOUC prima del CSS esterno). Non rimuoverla.
- Scope: globale in `partial/`, di modulo in `components/` (un file per block-* che ne ha bisogno,
  import nella sezione Components di styles.css, sempre con `layer(components)`).
  Classi stato/aggancio JS in kebab.

## Twig

- `base.twig` con blocchi `html_head / custom_scripts / extra_head / header / content / cta / footer / extra_js`.
- Dispatcher flexible: loop su `post.meta('content')` → `components/block-<acf_fc_layout>.twig`
  (`partial/page-content.twig`). NIENTE `ignore missing`: un layout senza twig deve fallire rumorosamente.
- Macro in `partial/macros.twig`: `intro()`, `image()` (picture responsive AVIF/WebP, `sizes` per
  breakpoint, `atf: true` per fetchpriority), `mp4()` (poster nativo per LCP), `embed()`.
- Filtri custom (functions/twig.php): `|svg`, `|slug`, `|size`, `|video_src/provider/id`, `|toavif`,
  `|avif_src/webp_src/best_src`; funzioni `get_field()`, `uniqueid()`, `module_posts()`.
- CF7: `{% apply shortcodes %}[contact-form-7 id="{{ form.ID }}"]{% endapply %}`, campo ACF
  `post_object` su `wpcf7_contact_form`.
- Campi immagine `return_format: url` si stampano diretti; i campi immagine dei moduli usano
  `return_format: id` + `get_image()` + macro `image()`.

## Moduli (flexible content)

Il boilerplate è una TRACCIA, non un tema completo: attivi solo i due moduli universali.

- **free** — testo/wysiwyg + bottone, opz. 2 colonne. È il riferimento del contratto modulo.
- **media** — media full-width (immagine/gallery/video/embed, viste carousel/contained/fullbleed).

Gli altri 14 (columns, panel, cards, icons, numbers, accordion, faq, cta, form, contacts,
separator, shortcode, code, posts) sono in **`library/modules/`**, pronti ma inerti:
si INSTALLANO copiandoli dentro quando servono — mai partire riscrivendoli da zero,
mai cancellare dal tema ciò che non serve (non c'è: si aggiunge, non si toglie).
Procedura di installazione in `library/README.md`.

**Contratto modulo** (vale per ogni nuovo modulo):
- Wrapper: `<section class="block-x" id="{{ content.section_id|default('section-' ~ loop.index) }}"
  data-loop="{{ loop.index }}"{% if bg != 'none' %} data-bg="{{ bg }}"{% endif %}>`.
- Opzioni standard: `bg` (select none|light|dark) + `section_id`. Intro ricorrente: `title` +
  `tag` (select h2/h3, in admin larghezze 70/30) + `subtitle` (`text-lead`) + `text` (`typo-r`).
- Raggio media/card `rounded-xl`, media senza ombre. Video embed = campo `oembed`/url.
- **`{% set %}` vs inline**: `set` SOLO per `bg`/`tag`, valori computati o usati più volte;
  print singoli inline. Niente `|default('testo')`: l'elvis `?:` solo per fallback a un altro campo.
- Card factory via macro `_self.card()` — gli `import` top-level NON entrano nello scope delle
  macro: reimportare i macros DENTRO la macro.

**Workflow nuovo modulo di progetto:**
1. Gruppo ACF "Modulo: X" in admin (active: false, location dummy su `post`) → ACF lo salva in acf-json/.
2. Aggiungerlo al flexible "Contenuti" come layout con **clone seamless** del gruppo.
3. Creare `components/block-<name>.twig` (name = acf_fc_layout).
4. Seed di un esempio sulla pagina libreria via wp-cli (`update_sub_field` coi field key).
5. Varianti di estrazione per CPT di progetto: copiare `theme_module_posts()` in `logic.php`
   col prefisso progetto.

## ACF

- Options pages: Opzioni (parent) → Anagrafica, Opzioni Tema, Opzioni Avanzate. Tutto in
  `$context['settings']`. In admin preferire fisarmoniche (`type: accordion`) alle tab top-level.
- **Niente placeholder / default_value testuali / instructions ridondanti** nei campi.
- Personalizzazioni in functions/acf.php: pattern `*testo*` → `<span class="alt">`, `unique_id`
  sulle righe repeater/flexible, location rule `menu_level`, WYSIWYG toolbar custom (no H1),
  GMaps key da `GMAPS_API_KEY`.
- Editor: fonte unica `editor_color_palette()` (da creare per progetto, vedi Ricette) allineata
  ai token `@theme`.

### Gotcha ACF (importanti — pagate care, non ripeterle)

- **Sync / `modified`**: editando un JSON a mano, settare `modified` a un `date +%s` FRESCO a ogni
  edit. Se `modified` ≤ DB, ACF non mostra "Sincronizza" e l'admin resta sulla versione DB mentre
  il runtime usa il JSON. In admin sincronizzare PRIMA di aprire/salvare il gruppo (salvare
  riscrive il JSON dal DB e perde le modifiche a mano).
- **Collisione nomi**: `get_field`/`acf_get_field` by name risolvono al PRIMO campo con quel name
  ovunque registrato → nomi univoci per i campi options. In dubbio usare la chiave.
- **`unique_id` filter**: SOLO su `repeater`/`flexible_content`, id DENTRO ogni riga. Su tutti gli
  array corrompe i repeater annidati (fatal `offset on string`).
- **wp-cli + group/option**: un singolo sub-field di un `group` per chiave a volte non attecchisce →
  riscrivere l'intero gruppo con `update_field(group_key, [...], 'option')`. `add_row` con selettore
  annidato può andare in fatal → `update_sub_field(["content", N, "campo"], $array)`.
- **Chiavi**: hash random stile ACF (`field_<hex>`), mai chiavi semantiche/sequenziali —
  evitano ripetizioni e conflitti tra gruppi e tra progetti. Creando i gruppi in admin
  le genera ACF; nei JSON scritti a mano generarle random (`secrets.token_hex(7)`).
- **Nomi file JSON parlanti**: `group_<slug-del-titolo>.json` (es. `group_modulo-free.json`),
  garantiti dal filtro `acf/json/save_file_name` in acf.php. Conseguenza: titoli dei
  gruppi UNIVOCI, o due gruppi si sovrascrivono lo stesso file.

## JS

- **Alpine per lo stato UI** (dropdown, offcanvas, modali `x-trap.inert.noscroll`, search, accordion
  collapse). Eventi window tra componenti (`$dispatch('popup')` + `x-on:popup.window`).
- `scripts.js`: init con `safeInit('Nome', fn)` (try/catch isolato + log), `reducedMotion` rispettato
  globalmente (Lenis/reveals/parallax non partono), hook SOLO via data-attribute o classi:
  `[data-loop]` (reveal), `[data-parallax]`, `[data-visual]` (video sfondo), `[data-countup]`,
  `[data-typ]` (redirect CF7), `.media-slider` (+`data-per-view`), `.posts-slider`,
  `.popup-trigger` / `a[href$="#popup"]`.
- Reveal: trigger per-elemento con skip above-the-fold (niente flash al reload) — non tornare a
  `ScrollTrigger.batch`.
- **Bundle caricato UNA volta sola** (solo `functions/enqueue.php`, `async defer`): niente tag
  manuali in head.twig/footer.twig. Doppio load = doppia init vLite/Swiper (provider re-throw,
  dots desync).
- Librerie standard: GSAP+ScrollTrigger, Lenis, Swiper, MixItUp(+multifilter, per i filtri archivio),
  vLitejs (+youtube/vimeo/volume/mobile), VenoBox, CountUp. `custom/custom.js`: Autohide,
  HoverIntent, SmoothScroll, Sticky (opt-in, importare solo ciò che serve).
- Non scrivere JS per ciò che CSS/Alpine fanno meglio.

## Gutenberg / editor (opzionale)

- Gated da `functions/config.php` (`GUTENBERG_ENABLED` ecc.). Blocchi custom: un folder per blocco
  in `blocks/` (block.json + fields.json + twig + css + preview.png), auto-discovery e render
  generico in `functions/blocks.php`, scaffolder `npm run make:block -- <slug> "<Titolo>"`.
- **editor-styles gated su Gutenberg**: con editor classico gli editor-styles iniettano Tailwind
  nell'admin → bleed. `enqueue.php` è solo frontend (`wp_enqueue_scripts`).

## Form (CF7)

- functions/forms.php: autop off (on nelle mail), spam nativo off, markup checkbox/radio/acceptance
  in `.form-toggle` con **id prefissati dallo unit-tag CF7**: senza prefisso, due form nella stessa
  pagina collidono su `privacy`/`marketing` e la label spunta la checkbox sbagliata.
- Markup: `.form-group` (+`.full`), `.form-section`, `.check-group`, `.send-group > .send-btn`.
- `[submit]` genera `<input>`: niente pseudo-elementi → l'effetto fill `.btn` non funziona; usare
  fallback colore o `<button type="submit">` raw.

## Bottoni

- Nel devkit `.btn` (`partial/buttons.css`) è volutamente minimo: uppercase, bordo tenue, fondo
  `--color-light`, hover pieno `--color-dark`; variante `.btn-accent`. È una base da rivestire,
  non il bottone finale — l'aspetto vero arriva dalla grafica di progetto.
- Ricetta per il fill animato (pattern ricorrente nei progetti): `::before` con `scale-x` da
  sinistra e `-inset-px` (sovrasborda 1px, ritagliato da `overflow-hidden`) per evitare il seam
  bianco subpixel. Varianti per sfondo scuro/accent: mantenere la struttura, adattare i colori.

## SEO & contenuti

- Yoast breadcrumbs re-markuppati come `<li><a>` (functions/custom.php).
- Archivi tassonomia = landing crawlabili (H1/testo da campi term); filtri client-side MixItUp
  (`posts_per_page: -1`) o chip-link server-side se l'archivio è grande.
- Voce menu ≠ H1 di pagina. Footer: voce "Credits" verso il sito dello studio,
  `target="_blank" rel="nofollow noopener"`.

## Lettura materiali designer

- **XD**: zip → parse `artwork/<artboard>/graphics/graphicContent.agc` (JSON). Desktop spesso con
  `syncRef` non risolvibili: partire dagli artboard mobile. "UI Elements" = design system.
- **PDF**: `pdftoppm` + `pdftotext`. Cercare "Indicazioni generali".
- Verificare sempre i valori sul sito reale con `curl`/wp-cli prima di "fixare" a voce.

## Flusso di lavoro

1. Inizio progetto: segui `START.md` (checklist completa dei seam).
2. Design system dall'XD → `@theme` + font self-hosted.
3. Header + footer + menu → CPT/tassonomie → archivi → single → moduli flexible → blog.
4. Con watch attivo basta salvare; altrimenti `npm run build` e grep sul min.css per i token attesi.
5. Commit frequenti, messaggi imperativi con scope chiaro.
6. Ogni decisione/gotcha di progetto → annotarla in `PROJECT.md` subito.

## Ricette

Snippet opzionali tenuti fuori dai sorgenti. Copiarli dove indicato quando servono.

### ACF: filtro relationship/post_object limitato a un page template (functions/acf.php)

```php
function acf_rel_sample($args, $field, $post_id)
{
    $args['meta_key'] = '_wp_page_template';
    $args['meta_value'] = ['template-name.php'];
    return $args;
}
add_filter('acf/fields/relationship/query/name=field_name', 'acf_rel_sample', 10, 3);
// idem con acf/fields/post_object/query/name=...
```

### TinyMCE: palette colori brand (functions/acf.php, dentro `wysiwyg_tinymce_settings`)

```js
// Colori allineati ai token @theme di dev/css/styles.css
mceInit.textcolor_map = [
    '609422', 'Accent',
    '22262a', 'Dark',
];
```

### Enqueue: brand color da opzioni ACF come CSS variable (functions/enqueue.php)

```php
// Abbinare a `--color-accent: var(--brand-color)` nel blocco @theme
if (function_exists('get_field') && $brand_color = get_field('color', 'options')) {
    wp_add_inline_style('theme-styles', ":root { --brand-color: {$brand_color}; }");
}
```

### Swatch colore nei select ACF (campi `bg`/`color`)

In `functions/acf.php`, su hook `acf/input/admin_footer`: una mappa valore→hex allineata ai token
del progetto, applicata alle option dei select via JS e ri-eseguita su
`acf.addAction('ready')` e `acf.addAction('append')` (senza il secondo, le righe aggiunte a caldo
a un repeater restano senza swatch).

### Popup richiesta info

`partial/popup.twig` con Alpine (`x-on:popup.window`, `x-trap.inert.noscroll`) + campi options
`popup_*`. I trigger sono già nel devkit: `.popup-trigger`, `a[href$="#popup"]` e
`initPopupTriggers` in `scripts.js` — manca solo il partial.
