# Zeno's WP DevKit | Timber Edition

A modern, opinionated WordPress theme development kit built on Timber, featuring Tailwind CSS 4.x, esbuild, and a curated set of tools for efficient theme development.

## ✨ Features

- 🌲 **Timber 2.0** - Component-based theme development with Twig templating
- 🎨 **Tailwind CSS 4.x** - Modern utility-first CSS with the latest features
- ⚡ **esbuild** - Lightning-fast build system with hot reload
- 📦 **Modular Scripts** - Tree-shakeable ES6 modules for common UI patterns
- 🔧 **WordPress Best Practices** - Proper enqueue system and cache management

## 🚀 Quick Start

### Prerequisites

- Node.js 18+ and npm
- PHP 8.0+
- Composer
- WordPress 6.0+

### Installation

1. **(Optional) Run the Installer**
   ```bash
   npm run setup
   ```
   Answer the prompts to set your theme name, BrowserSync proxy, Gutenberg preferences, Tailwind usage, and other defaults. Pressing enter keeps the detected defaults, so you can safely skip values or the entire step. You can re-run this at any time or edit `devkit.config.json` manually.

2. **Install Dependencies**
   ```bash
   npm install
   composer install
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
├── dev/                  # Source files
│   ├── css/              # Source stylesheets (Tailwind)
│   └── js/               # Source scripts (ES6 modules)
├── functions/            # Theme logic (renamed from "src")
│   ├── acf.php           # Advanced Custom Fields setup
│   ├── blocks.php        # Gutenberg / Block editor setup
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
- **[Vidstack](https://www.vidstack.io/)** - Advanced video player
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

### v5.0 - Modern Architecture (Current)

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
