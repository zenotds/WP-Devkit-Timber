# START — Checklist avvio nuovo progetto

> Checklist operativa per trasformare il devkit in un tema di progetto.
> Eseguibile da uno sviluppatore o da un'AI: al primo prompt di un nuovo progetto,
> dai in pasto questo file a Claude insieme ai materiali del design.
> A setup completato: compila `.claude/PROJECT.md` ed **elimina questo file**.

## 1. Identità del tema

- [ ] `style.css` → `Theme Name`, `Description`, `Version: 1.0d0`
- [ ] Rinomina la cartella del tema (slug progetto)
- [ ] `humans.txt` → dati progetto
- [ ] `functions/config.php` → `THEME_NAMESPACE` (default `theme`) col prefisso del progetto

## 2. Build

- [ ] `npm install && composer install` (il postinstall crea il symlink `wp-config.php` se il tema è dentro un'installazione WP)
- [ ] `devkit.config.json` → `proxy` con l'URL locale, `browser` a piacere (`esbuild.js` non si tocca)
- [ ] Prova: `npm run watch` deve compilare e aprire il proxy

## 3. Design system

- [ ] `dev/css/styles.css` → blocco `@theme`: palette (`--color-accent/light/dark/darker/body/error/focus`) e scala tipografica dai materiali design (mantieni i NOMI dei token)
- [ ] Font: woff2 in `assets/webfonts/`, `@font-face` in `dev/css/base/fonts.css`, `--font-base` nel `@theme`, preload in `functions/enqueue.php`
- [ ] FontAwesome Pro (se serve): CSS in `dev/css/fontawesome/`, woff2 in `assets/webfonts/`, scommenta gli import in `styles.css`
- [ ] `editor_color_palette()` in `functions/acf.php` (vedi CLAUDE.md → Ricette) allineata ai token

## 4. WordPress e ACF

- [ ] Plugin: ACF Pro, CF7 (+ Yoast, WPML se previsti)
- [ ] Chiavi API in `wp-config.php` (es. `define('GMAPS_API_KEY', '…')`) — mai nel tema
- [ ] Admin → ACF → **Sincronizza** tutti i gruppi (moduli v7 + options + menu)
- [ ] Options: compila Anagrafica e Opzioni Tema (logo `header_logo`, social, footer)
- [ ] Menu: crea le posizioni usate (`functions/menus.php`) e assegnale

## 5. Sistema contenuti

- [ ] Default: flexible content (gruppo "Contenuti" su page, moduli attivi: free + media). Gutenberg? → `functions/config.php` (`GUTENBERG_ENABLED` + eventuali `ALLOWED_SLUGS/IDS`); per i blocchi custom: `npm run make:block -- <slug> "<Titolo>"`
- [ ] Installa dalla dispensa i moduli che il progetto richiede (`library/README.md`); i moduli nuovi si scaffoldano con `npm run make:module -- <slug> "<Titolo>"`
- [ ] Crea la pagina **"Libreria moduli"** e inserisci un esempio di ogni modulo attivo (QA visivo + reference per l'editor)
- [ ] CPT/tassonomie di progetto via ACF (JSON versionati in `acf-json/`)
- [ ] Logica di dominio in `functions/logic.php` (prefisso progetto, es. `acme_*`)

## 6. Documentazione

- [ ] Compila `.claude/PROJECT.md` (cliente, URL, palette, decisioni)
- [ ] Elimina `START.md`

## Ordine di lavoro consigliato

1. Design system (`@theme` + font) → header/footer/menu
2. CPT e tassonomie → archivi → single
3. Pagine con moduli flexible (parti dalla libreria, crea i moduli custom che mancano)
4. Blog, form, SEO, fino in fondo
5. `npm run build` prima di ogni deploy

Convenzioni complete: `.claude/CLAUDE.md`. Stato del progetto: `.claude/PROJECT.md`.
Entrambi sono file di lavoro **locali e non versionati**: portali a mano nel progetto.
