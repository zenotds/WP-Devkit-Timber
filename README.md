# Zeno's WP DevKit | Timber Edition

A modern, opinionated WordPress theme development kit built on Timber, featuring Tailwind CSS 4.x, esbuild, and a curated set of tools for efficient theme development.

## ✨ Features

- 🌲 **Timber 2.x** - Component-based theme development with Twig templating
- 🎨 **Tailwind CSS 4.x** - Modern utility-first CSS with the latest features
- ⚡ **esbuild** - Lightning-fast build system with hot reload
- 🧱 **ACF Gutenberg Blocks** - Block boilerplate (API v3) rendered with Timber
- 🖼️ **TimberAVIF** - On-demand AVIF/WebP conversion with background queue
- 📦 **Modular Scripts** - Tree-shakeable ES6 modules for common UI patterns
- 🔧 **WordPress Best Practices** - Proper enqueue system and cache management

## 🚀 Quick Start

### Prerequisites

- Node.js 18+ and npm
- PHP 8.0+
- Composer
- WordPress 6.0+

### Installation

1. **Install Dependencies**
   ```bash
   npm install
   composer install
   ```

2. **Configure Development URL**
   
   Edit `esbuild.js` at line 245 and set your local development domain:
   ```javascript
   const PROXY_URL = 'https://your-site.test';
   ```

3. **Start Development**
   ```bash
   npm run dev
   ```

4. **Build for Production**
   ```bash
   npm run build
   ```

### Available Commands

| Command | Alias | Description |
|---------|-------|-------------|
| `npm run dev` | `watch` | Watch for changes and rebuild assets with hot reload |
| `npm run build` | `prod` | Build optimized assets for production |

## 📁 Project Structure

### Theme Organization

```
your-theme/
├── assets/               # Compiled files (auto-generated)
│   ├── css/              # Compiled stylesheets
│   └── js/               # Compiled scripts
├── blocks/               # ACF Gutenberg blocks (one folder per block)
│   └── section/          # Demo block: block.json + twig + fields.json
├── dev/                  # Source files
│   ├── css/              # Source stylesheets (Tailwind)
│   ├── js/               # Source scripts (ES6 modules)
│   └── make-block.mjs    # Block scaffolding script
├── functions/            # Theme logic (renamed from "src")
│   ├── acf.php           # Advanced Custom Fields setup
│   ├── avif.php          # TimberAVIF: AVIF/WebP conversion
│   ├── blocks.php        # Gutenberg / ACF blocks setup
│   ├── custom.php        # Other stuff
│   ├── enqueue.php       # Script and style enqueueing
│   ├── forms.php         # Form handling utilities
│   ├── menus.php         # Navigation menu configuration
│   ├── setup.php         # Timber Starter and settings
│   └── twig.php          # Twig extensions and filters
└── templates/            # Twig templates (renamed from "views")
```

### Key Changes from Timber Starter Theme

- `views/` → `templates/` - More intuitive naming for Twig files
- `src/` → `functions/` - Clearer separation of PHP logic
- Modular function partials for better code organization
- All partials are optional - use what you need
- Some templates have been removed and base.twig has a more modular structure

## 🎯 Technology Stack

### Core Technologies

- **[Timber](https://timber.github.io/docs/)** ^2.0 - MVC-style theme development
- **[Tailwind CSS](https://tailwindcss.com/)** ^4.x - Utility-first CSS framework
- **[PostCSS](https://postcss.org/)** - CSS transformation with plugins
- **[esbuild](https://esbuild.github.io/)** - Ultra-fast JavaScript bundler

### Included Libraries

Check `package.json` for the complete list. Notable inclusions:

- **[Alpine.js](https://alpinejs.dev/)** - Lightweight JavaScript framework
- **[GSAP](https://greensock.com/gsap/)** - Professional-grade animation
- **[Swiper](https://swiperjs.com/)** - Modern touch slider
- **[vLitejs](https://vlite.js.org/)** - Lightweight video player (MP4, YouTube, Vimeo)
- **[VenoBox](https://veno.es/venobox/)** - Lightbox for images and videos
- **[CountUp.js](https://inorganik.github.io/countUp.js/)** - Animated counters
- **[Lenis](https://lenis.darkroom.engineering/)** - Smooth scroll engine
- **Custom Utilities** - Autohide, HoverIntent, SmoothScroll, Sticky

### Custom JavaScript Modules

Import only what you need:

```javascript
import { Autohide, HoverIntent, SmoothScroll, Sticky } from './custom/custom.js';

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  Autohide('.header', 80);
  SmoothScroll();
  Sticky('.sidebar');
  
  new HoverIntent(document.querySelectorAll('.dropdown'), {
    onEnter: (el) => el.classList.add('open'),
    onExit: (el) => el.classList.remove('open')
  });
});
```

## 📝 Content Management

Two interchangeable content systems ship as boilerplate — pick one per project
(or mix them, enabling Gutenberg only on specific posts):

### ACF Flexible Content (default)

A "Contenuti" flexible content group renders each layout dynamically:

```twig
{% for content in post.meta('content') %}
    {% include "components/block-" ~ content.acf_fc_layout ~ ".twig" ignore missing %}
{% endfor %}
```

See `templates/partial/page-content.twig` and `templates/components/`.

### ACF Gutenberg Blocks (API v3)

Visual editing with ACF blocks rendered by Timber. Each block is a
self-contained folder in `/blocks/` (block.json + twig + fields.json + css),
auto-discovered and registered by `functions/blocks.php`.

```bash
# Scaffold a new block from the demo
npm run make:block -- hero "Hero"
```

Enable in `functions/blocks.php`:

```php
define('GUTENBERG_ENABLED', true);
define('GUTENBERG_CUSTOM_BLOCKS_ENABLED', true);
```

Full docs in [`blocks/README.md`](blocks/README.md): per-block ACF JSON
save/load, inserter previews, InnerBlocks, iframe editor styles.

## 🖼️ Image Optimization (TimberAVIF)

`functions/avif.php` converts images to AVIF/WebP on demand with a per-request
budget and a background queue for the overflow. Twig filters:

```twig
{{ image|toavif }}              {# AVIF or original #}
{{ image|avif_src(800, 600) }}  {# resize + AVIF #}
{{ image|best_src(800, 600) }}  {# AVIF > WebP > original #}
```

The `image()` macro in `templates/partial/macros.twig` builds responsive
`<picture>` elements (AVIF > WebP fallback, srcset breakpoints, 2x density,
lazy loading, `atf` option for above-the-fold priority). Companion macros
`mp4()` and `embed()` output vLitejs-ready video markup.

## 🌐 Request Object

A sanitized request object is available in every Twig template:

```twig
{{ request.get.page }}    {# $_GET, unslashed + sanitized #}
{{ request.post.email }}  {# $_POST, unslashed + sanitized #}
```

## 🔌 Recommended WordPress Plugins

These plugins integrate seamlessly with the theme's custom functions:

| Plugin | Purpose | Required |
|--------|---------|----------|
| **[ACF Pro](https://www.advancedcustomfields.com/)** | Custom fields and flexible content | Highly Recommended |
| **[Yoast SEO](https://yoast.com/)** | Search engine optimization | Recommended |
| **[WPML](https://wpml.org/)** | Multilingual site support | Optional |
| **[WP Rocket](https://wp-rocket.me/)** | Advanced caching and optimization | Optional |
| **[Contact Form 7](https://contactform7.com/)** | Form builder and management | Optional |

## 🛠️ Build System

### Asset Processing

- **CSS**: Tailwind CSS compilation with PostCSS
- **JavaScript**: ES6+ transpilation and bundling with esbuild
- **Static Assets**: Fonts and images are not processed - reference them relative to `/assets/`

### Build Features

- **Hot Module Replacement** - Instant updates during development
- **Error Handling** - Robust error catching and reporting
- **Debouncing** - Prevents unnecessary rebuilds
- **Version Tagging** - Automatic versioning in output files
- **Cache Busting** - WordPress enqueue system with version hashes

### Important Notes

- ⚠️ **Avoid images in CSS** - Use `<img>` tags or background images via HTML/Twig for better performance
- 📁 **Static fonts** - Place fonts in `/assets/webfonts/` and reference them directly in CSS
- 🎯 **ES Modules** - All JavaScript uses modern module syntax with tree-shaking
- 🎯 **Tailwind utilities** - Theme compiles without custom class errors out of the box

## 🎨 Styling Guidelines

This theme uses Tailwind CSS 4.x with the new `@import`, `@theme`, and `@utility` syntax:

```css
/* dev/css/main.css */
@import "tailwindcss";

@theme {
  --color-primary: #3b82f6;
  --font-sans: 'Inter', system-ui, sans-serif;
}

@utility custom-shadow {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
```

Includes a Biome config to handle Tailwind 4.0 syntax.

## 📝 Changelog

### v6.0 - Blocks & Lessons Learned (Current)

Brings the lessons learned from production themes back into the devkit.

**Breaking Changes**
- Removed `laminas-diactoros`: `{{ request }}` is now a plain sanitized object
  (`request.get.*` / `request.post.*`)
- Removed Vidstack in favor of **vLitejs** (MP4 + YouTube/Vimeo providers,
  volume/mobile plugins)
- Twig filters: `iframesrc` renamed to `video_src`; removed `fvb` and `sbt`

**New Features**
- 🧱 **ACF Gutenberg blocks boilerplate** (API v3): auto-discovery from
  `/blocks/`, generic Timber render callback, per-block ACF JSON, inserter
  previews, InnerBlocks support, `make:block` scaffolding script, demo block
- 🖼️ **TimberAVIF**: on-demand AVIF/WebP conversion with background queue,
  admin tools and WP-CLI support
- 🖼️ Responsive `image()` macro (picture, AVIF > WebP, srcset, 2x) plus
  `mp4()` / `embed()` video macros
- ➕ New libraries: VenoBox (lightbox), CountUp.js (animated counters),
  `@alpinejs/focus`
- 🎨 Block editor iframe gets the theme CSS via `add_editor_style`

**Improvements**
- All Composer and npm dependencies updated (Timber 2.5, Tailwind 4.3,
  esbuild 0.28, chokidar 5)
- Optimized `video_*` Twig filters: shared URL extraction (iframe or raw URL),
  wider YouTube/Vimeo pattern support (shorts, youtu.be, player.vimeo)
- `svg` filter: local URL → path resolution and per-request cache
- Leaner comments across JS/CSS sources

### v5.0 - Modern Architecture

**Breaking Changes**
- Removed Bootstrap completely (so long, and thanks for all the fish! 🐬)
- Removed situational dependencies: smooth-scroll, tippy, vanillasharing
- Aligned version numbering with non-timber devkit

**New Features**
- ✨ Custom scripts refactored as ES6 modules with comprehensive documentation
- ⚡ Enhanced esbuild configuration with error handling and debouncing
- 🎯 WordPress enqueue system for better versioning and cache management
- 🧹 Biome integration with Tailwind v4 compatibility
- 📊 Build outputs version and date on separate lines for better caching

**Improvements**
- Streamlined installation experience
- Removed unused devDependencies
- Build outputs version and date on separate lines
- Various under-the-hood optimizations

### v1.1 - Alpine Era

- Fixed missing index.php
- Replaced Bootstrap with Alpine.js
- Upgraded to Vidstack player
- Replaced modals with Fancybox
- Better starter theme with Alpine-powered menus
- Auto-generates wp-config.php shortcut

### v1.0 - Initial Release

First public version

## 🤝 Contributing

This is a personal development kit, but suggestions and improvements are welcome! Feel free to fork and adapt to your needs.

## 📄 License

This project is provided as-is for theme development. Individual dependencies maintain their own licenses.

## 🙏 Credits

Built with ❤️ by [@zenotds](https://github.com/zenotds)

Special thanks to:
- [Timber](https://timber.github.io/) team
- [Tailwind CSS](https://tailwindcss.com/) team
- All the amazing open-source contributors

---

**Happy theming!** 🎉