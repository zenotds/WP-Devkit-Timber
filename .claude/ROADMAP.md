# ROADMAP — backlog del devkit

> Backlog del **devkit**, non di un progetto (quello sta in `.claude/PROJECT.md`).
> Ogni voce ha il contesto e i numeri che l'hanno motivata, così si riparte senza rianalizzare.

Le misure qui sotto sono aggiornate alla v8.0.

## 1. Code splitting del bundle JS

**Perché.** Il bundle è **367 KB min / 122 KB gzip** e viene caricato su ogni pagina, anche
su una di solo testo. Il codice del tema è 6.6 KB: tutto il resto è libreria.

| | KB min |
|---|---|
| gsap + ScrollTrigger | 111.6 |
| swiper | 93.4 |
| alpinejs | 45.5 |
| vlitejs | 29.7 |
| @alpinejs/focus | 24.9 |
| lenis | 17.9 |
| venobox | 16.7 |
| countup.js | 6.6 |
| **codice del tema** | **6.6** |

**Come.** `splitting: true` + `format: "esm"` in `createBuildOptions()`, `import()` dinamico dietro
guardia di selettore in `dev/js/scripts.js`, enqueue con `type="module"` (o `wp_enqueue_script_module()`,
WP 6.5+). Stima: ~40 KB gzip iniziali.

**Trappole già trovate provandolo:**
- `import("swiper/modules")` sul barrel **disattiva il tree-shaking**: 83 KB invece di ~20.
  Vanno importati i singoli moduli (`swiper/modules/navigation`, …).
- VenoBox deve girare **dopo** Swiper (ripulisce le `.venobox` dalle slide clonate dal loop).
- vLite va caricata **una volta sola** e memoizzata: la doppia `registerProvider` fa re-throw
  e lascia i player morti.
- `[data-loop]` sta sul wrapper di ogni modulo flexible, quindi GSAP si caricherebbe quasi sempre.
  Il guadagno vero su GSAP arriva solo sostituendo i reveal con scroll-driven animations CSS
  (`animation-timeline: view()`), che è un cambio di resa da valutare a parte.
- Serve `chunkNames` + pulizia dei chunk orfani a ogni build.

**Stato.** Provato e funzionante sulla 7.5 (356 → 97.7 KB iniziali), poi rimosso: si tiene un bundle unico
finché non si decide anche la questione GSAP.

## 2. `functions/avif.php` → pacchetto composer — CHIUSA in v8.0

Risolta diversamente: la libreria vive nella sua repo (`zenotds/timber-avif`), che resta la
sorgente per i temi esistenti, e nel devkit è già integrata e distribuita. Un fix nasce lì e
scende nel devkit a ogni rilascio, senza il peso di un pacchetto composer e di un mu-plugin.
Resta valido l'effetto collaterale che la voce indicava: se servisse un `bizen/theme-core` con
twig filters, forms e acf helpers, è da lì che si parte.

## 3. Modulo dual-consumer (flexible + Gutenberg)

**Perché.** Oggi un modulo esiste solo come layout flexible. Passare un progetto a Gutenberg
vuol dire riscrivere i moduli; mantenere entrambi vuol dire due dispense.

**Come.** Un modulo, due consumer: il twig legge i campi da una variabile normalizzata

```twig
{% set f = fields|default(content) %}
```

`page-content.twig` passa `content`, il renderer di blocco passa i campi ACF del blocco.
Stesso `fields.json`, stesso twig, due modi di inserirlo in pagina.

**Perché conviene.** Il 90% c'è già: `functions/blocks.php` fa auto-discovery e render generico,
`library/modules/*/` ha già `fields.json` + `block-*.twig`, e `make:block` / `make:module`
scaffoldano. Serve che il generatore produca sia il layout flexible sia il `block.json`.

**Cosa NON fare.** Block theme / FSE: template HTML e `theme.json` come autorità sono un
downgrade per design custom governato al pixel. Gutenberg-come-editor e block-theme sono
due decisioni separate — la seconda resta un no.
