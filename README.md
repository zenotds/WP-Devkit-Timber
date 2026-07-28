# Zeno's WP DevKit | Timber Edition

Base di sviluppo per temi WordPress costruita su Timber 2, Tailwind CSS 4 ed esbuild.
Opinionata: fa poche scelte, ma le fa in modo esplicito e le documenta.

## ✨ Caratteristiche

- 🌲 **Timber 2.x** — template Twig, logica PHP separata dalla vista
- 🎨 **Tailwind CSS 4.x** — config CSS-first via `@theme`, token funzionali, cascata a layer espliciti
- ⚡ **esbuild** — rebuild incrementali, CSS iniettato senza ricaricare la pagina
- 🧩 **Libreria moduli** — 16 moduli ACF flexible content: 2 attivi, 14 in dispensa da cui copiare
- 🧱 **Blocchi Gutenberg ACF** — boilerplate opzionale (API v3) renderizzato da Timber
- 🖼️ **TimberAVIF** — conversione AVIF/WebP on demand con coda in background
- 📦 **Script modulari** — moduli ES6 tree-shakeable per i pattern UI ricorrenti

## 🚀 Avvio rapido

### Prerequisiti

Node.js 22+, PHP 8.3+, Composer, WordPress 7.0+

### Installazione

1. **Dipendenze**
   ```bash
   npm install
   composer install
   ```

2. **URL di sviluppo**

   `devkit.config.json` è l'unico file di build da toccare per progetto. `esbuild.js` resta
   invariato, così si aggiorna dal devkit senza conflitti:
   ```json
   {
     "proxy": "https://tuo-sito.test",
     "browser": ["default"]
   }
   ```

3. **Sviluppo** — `npm run dev`
4. **Produzione** — `npm run build`

### Comandi

| Comando | Alias | Cosa fa |
|---|---|---|
| `npm run dev` | `watch` | Watch + BrowserSync, rebuild incrementale |
| `npm run build` | `prod` | Build minificata + bump di versione in `style.css` |
| `npm run make:module -- <slug> "<Titolo>"` | | Scaffold di un modulo flexible |
| `npm run make:block -- <slug> "<Titolo>"` | | Scaffold di un blocco Gutenberg |

## 📁 Struttura

```
tuo-tema/
├── acf-json/             # Field group, CPT e tassonomie versionati
├── assets/               # Output compilato (non editare a mano)
├── blocks/               # Blocchi Gutenberg ACF, uno per cartella (opzionale)
├── dev/
│   ├── css/              # Sorgenti CSS (Tailwind)
│   ├── js/               # Sorgenti JS (moduli ES6)
│   ├── make-block.mjs    # Scaffolder blocchi
│   └── make-module.mjs   # Scaffolder moduli flexible
├── functions/            # Logica PHP, un file per responsabilità
│   ├── acf.php           # Setup e personalizzazioni ACF
│   ├── avif.php          # TimberAVIF: conversione AVIF/WebP
│   ├── blocks.php        # Blocchi Gutenberg / ACF
│   ├── config.php        # Scelte per progetto (namespace, flag Gutenberg)
│   ├── custom.php        # Varie
│   ├── enqueue.php       # Enqueue di script e stili
│   ├── forms.php         # Utility per i form
│   ├── menus.php         # Registrazione menu
│   ├── setup.php         # Timber Starter e contesto globale
│   └── twig.php          # Filtri e funzioni Twig
├── library/              # Dispensa: moduli pronti ma inerti finché non li copi
├── templates/            # Template Twig
├── devkit.config.json    # Config di build per progetto
└── START.md              # Checklist nuovo progetto (da eliminare a setup finito)
```

Rispetto allo starter theme di Timber: `views/` → `templates/`, `src/` → `functions/`,
partial PHP modulari e `base.twig` a blocchi. Tutti i partial sono opzionali.

## 🎯 Stack

**Core** — [Timber](https://timber.github.io/docs/) ^2.0, [Tailwind CSS](https://tailwindcss.com/) ^4.x,
[esbuild](https://esbuild.github.io/), [PostCSS](https://postcss.org/)

**Librerie incluse** — elenco completo in `package.json`:
[Alpine.js](https://alpinejs.dev/) (stato UI),
[GSAP](https://greensock.com/gsap/) (animazioni),
[Swiper](https://swiperjs.com/) (caroselli),
[vLitejs](https://vlite.js.org/) (video MP4/YouTube/Vimeo),
[VenoBox](https://veno.es/venobox/) (lightbox),
[CountUp.js](https://inorganik.github.io/countUp.js/) (contatori),
[Lenis](https://lenis.darkroom.engineering/) (smooth scroll).

`dev/js/custom/custom.js` aggiunge utility opt-in — Autohide, HoverIntent, SmoothScroll,
Sticky — da importare solo se servono.

## 📝 Gestione contenuti

Due sistemi intercambiabili: si sceglie a inizio progetto, e possono convivere abilitando
Gutenberg solo su post specifici.

### ACF Flexible Content (default)

Un gruppo flexible "Contenuti" renderizza ogni layout dinamicamente:

```twig
{% for content in post.meta('content') %}
    {% include "components/block-" ~ content.acf_fc_layout ~ ".twig" %}
{% endfor %}
```

Niente `ignore missing`: un layout senza twig deve fallire rumorosamente invece di sparire
dalla pagina in silenzio. Vedi `templates/partial/page-content.twig` e `templates/components/`.

Due moduli universali sono attivi (**free** e **media**). Gli altri quattordici — columns,
panel, cards, icons, numbers, accordion, faq, cta, form, contacts, separator, shortcode,
code, posts — stanno inerti in `library/modules/` e si **installano copiandoli dentro** quando
servono. Da un progetto non si toglie mai niente: si aggiunge. Procedura in
[`library/README.md`](library/README.md).

### Blocchi Gutenberg ACF (API v3)

Editing visuale con blocchi ACF renderizzati da Timber. Ogni blocco è una cartella autonoma
in `/blocks/` (block.json + twig + fields.json + css), scoperta e registrata da
`functions/blocks.php`. Si abilita in `functions/config.php`:

```php
define('GUTENBERG_ENABLED', true);
define('GUTENBERG_CUSTOM_BLOCKS_ENABLED', true);
```

Documentazione completa in [`blocks/README.md`](blocks/README.md): salvataggio ACF JSON
per blocco, preview nell'inserter, InnerBlocks, editor styles.

## 🖼️ Immagini (TimberAVIF)

`functions/avif.php` converte in AVIF/WebP on demand, con un budget per richiesta e una coda
in background per l'eccedenza. Filtri Twig:

```twig
{{ image|toavif }}              {# AVIF o originale #}
{{ image|avif_src(800, 600) }}  {# resize + AVIF #}
{{ image|best_src(800, 600) }}  {# AVIF > WebP > originale #}
```

La macro `image()` in `templates/partial/macros.twig` costruisce `<picture>` responsive
(AVIF con fallback WebP, srcset, densità 2x, lazy loading, opzione `atf` per l'above-the-fold).
Le macro `mp4()` ed `embed()` producono il markup video per vLitejs.

## 🌐 Oggetto request

In ogni template Twig è disponibile una request sanitizzata:

```twig
{{ request.get.page }}    {# $_GET, unslashed + sanitized #}
{{ request.post.email }}  {# $_POST, unslashed + sanitized #}
```

## 🔌 Plugin consigliati

| Plugin | Scopo | Necessario |
|---|---|---|
| **[ACF Pro](https://www.advancedcustomfields.com/)** | Campi custom e flexible content | Fortemente consigliato |
| **[Yoast SEO](https://yoast.com/)** | SEO e breadcrumb | Consigliato |
| **[Contact Form 7](https://contactform7.com/)** | Form | Opzionale |
| **[WPML](https://wpml.org/)** | Multilingua | Opzionale |
| **[WP Rocket](https://wp-rocket.me/)** | Cache e ottimizzazione | Opzionale |

## 🛠️ Build

- **CSS**: Tailwind 4 via PostCSS. Niente `postcss-import` né autoprefixer — Tailwind risolve
  gli import e gestisce prefissi e nesting da sé con Lightning CSS
- **JS**: bundling ES6+ con esbuild
- **Font e immagini** non vengono processati: si referenziano relativi ad `/assets/`

Per convenzione ogni `.js` e ogni `.css` top-level in `dev/` diventa un bundle in
`assets/{js,css}/<nome>.min.*`. Un file in più = un bundle in più.

**Caratteristiche:**

- **Rebuild incrementali** — il context esbuild è riusato tra le build e si ricrea solo quando
  cambia l'elenco degli entrypoint
- **CSS iniettato senza reload** — modificare un foglio di stile non fa perdere lo stato della
  pagina: menu aperti, modali, posizione di scroll. Twig, PHP e JS fanno reload pieno
- **Sourcemap solo in sviluppo** — in produzione esporrebbero i sorgenti
- **Versioning automatico** in `style.css` e cache busting via enqueue WordPress

## 🎨 Convenzioni CSS

Tailwind 4 con `@import`, `@theme` e `@utility` — niente `tailwind.config.js`.

**Token funzionali, mai nomi colore.** La palette prende il nome dal ruolo, così lo stesso
foglio di stile funziona su ogni progetto cambiando sette valori:

```css
/* dev/css/styles.css */
@theme {
  --color-accent: #609422;  /* brand */
  --color-light:  #f2f1f0;  /* bande chiare (data-bg="light") */
  --color-dark:   #22262a;  /* bande scure, testi forti */
  --color-darker: #121417;  /* footer, overlay */
  --color-body:   #64748b;  /* testo corrente */
  --color-error:  #dc2626;  /* validazione form */
  --color-focus:  #1d4ed8;  /* anello di focus, visibile su ogni sfondo */
}
```

Nessun `slate-*` / `neutral-*` / `sky-*` nel codice.

**Niente `@apply`.** Nei file CSS si scrive CSS, usando i token come custom property:

```css
.btn {
  padding-inline: calc(var(--spacing) * 4);
  border-radius: var(--radius-md);
  background: var(--color-light);
  font-size: var(--text-sm);
}
```

Le utility stanno nei template, non dentro il CSS.

**Cascata a layer espliciti**, dichiarati sugli import:
`base` → `components` (CSS del tema e dei plugin) → `utilities` (Tailwind) → `overrides`.

Conseguenza pratica: **una utility in un template batte sempre il CSS di componente**.
In `overrides` va solo ciò che deve vincere per forza — oggi il solo `layout/section-bg.css`,
la cui inversione testi su `data-bg="dark"` deve battere un `text-accent` scritto nel markup.

Corollario: lo stile base di un componente e i suoi stati vanno nello *stesso* file. Se lo stato
sta nel CSS (`.nav-item.active`) mentre la base sta nelle utility del template
(`after:scale-y-0`), lo stato perde in silenzio.

Incluso un config Biome che gestisce la sintassi Tailwind 4.

## 📝 Changelog

### v7.5 — Build e igiene CSS (corrente)

Affila le due cose che ogni progetto tocca ogni giorno: il ciclo di build e i fogli di stile.

**Build**
- ⚡ Rebuild su `esbuild.context()` riusato — CSS 249→60 ms, JS 44→15 ms, build di produzione
  completa 0.75→0.35 s
- 💧 I cambi CSS sono **iniettati senza ricaricare**: menu aperti, modali e posizione di scroll
  sopravvivono a una modifica
- 📄 Config di build per progetto spostata in `devkit.config.json`; `esbuild.js` è ora identico
  tra devkit e progetti e si aggiorna senza conflitti
- 🧹 Via autoprefixer (Tailwind 4 prefissa con Lightning CSS) — 5 KB di prefissi morti in meno
- 🔒 Sourcemap solo in sviluppo e non più committati; lockfile npm/composer ora sì

**CSS**
- 🎨 Tre token funzionali nuovi: `--color-body`, `--color-error`, `--color-focus`
- ✂️ **Rimossi tutti i 93 `@apply`** — i fogli di stile sono CSS puro coi token come custom property
- 🧯 **Rimosse tutte le 37 classi della palette di default** (`slate-*`, `neutral-*`, `sky-*`…)
- 🧱 Layer di cascata espliciti (`base` → `components` → `utilities` → `overrides`): le utility nei
  template battono in modo affidabile il CSS di componente, e l'inversione `data-bg` vince dove deve
- ♿ L'anello di focus è un vero `outline` con offset invece di un ring box-shadow
- 📉 CSS compilato 109,3 → 100,4 KB (18,1 KB gzip)

### v7.0 — Moduli flexible e seam di progetto

Trasforma il boilerplate in una base che avvia un progetto invece di una che va riscritta.

**Novità**
- 🧩 **Sistema di moduli flexible content**: `free` e `media` attivi, altri quattordici in
  `library/modules/`, installati copiandoli dentro
- 📐 **Contratto modulo**: opzioni standard `bg` / `section_id`, campi intro condivisi, ritmo
  verticale governato da `.main > *` così i twig dei moduli non portano spaziatura verticale
- 🌗 Sfondi di sezione `data-bg` con inversione automatica dei testi sulle bande scure, più
  l'opt-out `.surface` per le card che mantengono la propria superficie chiara
- 🎛️ **Seam per progetto**: `functions/config.php` centralizza namespace del tema e flag
  Gutenberg, con supporto ai setup misti (blocchi su alcune pagine, flexible sulle altre)
- 🏗️ Scaffolder `make:module` accanto a `make:block`

**Migliorie**
- Token colore funzionali e scala tipografica fluida in `@theme`
- Reveal per singolo elemento, con skip dei blocchi above-the-fold (niente flash al reload)
- Bundle caricato una volta sola, solo da `functions/enqueue.php`

### v6.0 — Blocchi

- 🧱 Boilerplate blocchi Gutenberg ACF (API v3): auto-discovery da `/blocks/`, render callback
  Timber generico, ACF JSON per blocco, preview nell'inserter, InnerBlocks, `make:block`
- 🖼️ TimberAVIF con coda in background, strumenti admin e supporto WP-CLI
- 🖼️ Macro `image()` responsive più le macro video `mp4()` / `embed()`
- ➕ VenoBox, CountUp.js, `@alpinejs/focus`

**Breaking** — rimosso `laminas-diactoros` (`{{ request }}` è ora un oggetto sanitizzato
semplice), Vidstack sostituito da vLitejs, filtro `iframesrc` rinominato in `video_src`.

### v5.0 — Architettura moderna

- Rimosso Bootstrap e le dipendenze situazionali
- Script custom rifatti come moduli ES6
- Config esbuild con gestione errori e debouncing
- Enqueue WordPress per versioning e cache

**Breaking** — rimozione di Bootstrap.

### v1.x — Prime versioni

Prima release pubblica, poi passaggio da Bootstrap ad Alpine.js e menu Alpine-powered.

## 📄 Licenza

Fornito così com'è per lo sviluppo di temi. Le singole dipendenze mantengono la propria licenza.

## 🙏 Crediti

Realizzato da [@zenotds](https://github.com/zenotds).

Grazie ai team di [Timber](https://timber.github.io/) e [Tailwind CSS](https://tailwindcss.com/),
e a tutti i contributori open source.
