# ROADMAP — backlog del devkit

> Backlog del **devkit**, non di un progetto (quello sta in `.claude/PROJECT.md`).
> Ogni voce ha il contesto e i numeri che l'hanno motivata, così si riparte senza rianalizzare.

---

# v8.0 — piano di rilascio

Distillazione del tema **Think Water** (luglio-settembre 2026, ~115 decisioni tracciate): due mesi e
mezzo di uso continuo della v7.5 hanno prodotto un elenco di difetti del framework — la sezione
**N11** del suo `PROJECT.md` — più un gruppo di soluzioni che si sono rivelate convenzioni e non
scelte di progetto. Questo piano le porta a monte.

**Perché major e non 7.6.** Timber AVIF 6.0 cambia la firma di `macros.image()`: da `sizes` come
mappa di pixel per breakpoint a `sizes` come stringa CSS. Sono 13 call site nel devkit e *tutti*
quelli di un tema esistente. Più i file rimossi e la conversione a tab dell'intero codebase: un tema
su v7.5 non si aggiorna, si migra.

## Decisioni prese prima di iniziare

- **Aggiornamento agnostico**: nessun pezzo di Think Water entra come tale. Entrano le correzioni e
  le convenzioni, riscritte senza il progetto attorno.
- **`library/` resta una dispensa.** Su Think Water sono stati installati tutti e 16 i moduli il
  primo giorno e la potatura non si è mai fatta, ma il boilerplate resta magro: chi vuole tutto
  copia tutto, chi non vuole niente non si porta dietro moduli da cancellare.
- **Timber AVIF è integrato e distribuito col devkit.** La repo `zenotds/timber-avif` resta la
  **sorgente** — per i temi esistenti, dove la macro va integrata a mano comunque — mentre nel
  devkit `avif.php` e la macro `image()` sono già dentro, nei loro posti. `image()` resta in
  `macros.twig` insieme a `mp4()` e `embed()`: un file solo di macro.
- **`.claude/` versionata integralmente** (solo `settings.local.json` fuori). Non sono note di
  lavoro: `CLAUDE.md` è la documentazione delle convenzioni, `PROJECT.md` è il template che ogni
  progetto compila, questo file è il backlog. Viaggiano col devkit come `README.md` e `START.md`.
- **`custom.js` resta un file solo.** Spostare `HoverIntent` e `Autohide` in una dispensa
  trasformerebbe un file in tre per spostare codice che **non entra nel bundle**: verificato, il
  tree-shaking di esbuild li lascia a zero occorrenze in `assets/js/scripts.min.js`, perché
  `scripts.js` importa solo ciò che usa.
- **Nessuna installazione WordPress in gioco.** Tutto il piano si scrive e si verifica standalone
  tranne il Lotto 1, che si chiude al primo progetto: vedi "Cosa resta aperto".

---

## Lotto 0 — Indentazione a tab

**Perché.** 71 file a spazi, 40 a tab, 1 misto (`dev/make-module.mjs`). Non è estetica: un editor che
formatta al salvataggio converte ciò che tocca, quindi ogni commit porta righe fantasma, e un edit
che cerca una stringa esatta non la trova più.

**Come.** Conversione della sola indentazione iniziale, come commit isolato. Tab e non spazi perché
Biome li impone già di default su js/css/json, gli standard WordPress li usano per il php, e il
markup annida fino a 16 livelli — coi tab la larghezza è una preferenza di chi legge.

**Verifica.** `git diff -w` deve essere **vuoto** (nient'altro è cambiato) e `php -l` pulito su tutti
i php.

**Trappole.**
- **Escludere `acf-json/*.json`**: li riscrive ACF a 4 spazi, convertirli a mano significa riaverli
  indietro al primo salvataggio da admin.
- **Escludere `./wp-config.php`**: il `postinstall` crea il symlink alla root WP, che è in
  `.gitignore` — quindi git non lo vede e non lo protegge. Uno script che itera sui file del tema ci
  finisce dentro e riformatta il wp-config del sito. È successo.

**Coda.** `biome.json` non dichiara l'indentazione (ed è esso stesso a spazi): aggiungere
`"formatter": { "indentStyle": "tab" }`.

---

## Lotto 1 — Timber AVIF 6.0

**Perché.** La macro v7.5 emette un `<source media>` per breakpoint con densità fisse 1x/2x e chiede
al chiamante larghezze assolute in pixel. Misurato su un tema reale: 20 ricette `sizes` distinte per
21 chiamate, 98 dimensioni generate, 185 varianti della sola immagine hero, 45 MB di derivati su 50.
Nessun file riusato fra moduli, perché ogni ricetta inventava le sue misure.

Il difetto non è estetico: **la macro causava l'avviso PageSpeed che doveva togliere**. Un dispositivo
a DPR 1,75 con due soli gradini è costretto a prendere quello sopra e scarica ~2× i byte che gli
servono. Con `srcset`/`w` il browser sceglie il gradino giusto: −59% sul caso misurato, e l'overshoot
scende sotto la soglia dell'audit.

**Come.**

| | Azione |
|---|---|
| `functions/avif.php` | sostituito dal 6.0 (1608 → 2016 righe) |
| `templates/partial/macros.twig` | `image()` da 144 a 26 righe; `mp4()` e `embed()` restano |
| `languages/` | nuova cartella in root col `.mo` italiano; senza, l'admin resta in inglese |
| 13 call site | `block-media` (4), `block-columns` (2), `block-cards` (3), `panel`, `cta`, `tease-card` |
| `single.twig`, `header.twig`, `mobile-menu.twig` | `resize(1200,300)` e i due `header_logo|towebp|resize(200)`, che su un logo SVG non producono nulla |

**`functions/custom.php`**, nello stesso giro:
- `jpeg_quality` forzato a 100 → **via**: la qualità la governa Timber AVIF. Da lì derivati JPEG più
  pesanti dell'originale.
- `intermediate_image_sizes` esteso a `large`, `1536x1536`, `2048x2048`.
- **Rimosso** l'hook `init` con `remove_image_size()`: sulle size core non fa nulla, quelle stanno
  nelle option e si tolgono solo col filtro. Il commento diceva il contrario.

**Nel README** va la nota sulla qualità, che è controintuitiva: **AVIF 75 ≠ JPEG 75**. Il floor «mai
sotto 90», giusto per JPEG, su AVIF triplica il peso per una differenza che su foto non si vede.

---

## Lotto 2 — I difetti del framework (N11)

Venti difetti trovati usando la v7.5. Stato verificato sul repo:

| # | Difetto | Dove |
|---|---|---|
| 1 | `{{ options.schemaorg }}` — chiave inesistente (`settings`) e campo mai esistito: stampa vuoto da sempre | `templates/html/footer.twig` |
| 2 | `.typo-r h1` senza `font-size`: il reset Tailwind azzera gli heading e resta a 16px | `dev/css/base/typography.css` |
| 3 | Due collection globali (20 post + categorie) costruite su **ogni** richiesta, usate da nessun twig | `functions/setup.php` |
| 4 | `unique_id` applicato a **tutti** gli array ACF invece che a repeater/flexible: **fatal** `offset on string` al primo repeater dentro un group | `functions/acf.php` |
| 5 | Opzioni ACF senza fallback sulla lingua di default: con WPML `/en/` è **senza logo, senza footer, senza menu** — non "in inglese", assente | `functions/setup.php` |
| 6 | `editor-styles` non gated su `GUTENBERG_ENABLED`: con editor classico Tailwind entra nell'admin e nelle WYSIWYG | `functions/setup.php` |
| 7 | Breadcrumb Yoast con `str_replace('<span>','</span>')`: lascia un `</span>` spurio e `<li>` fuori da qualunque lista | `functions/custom.php` |
| 8 | `icons.css` scritto con `@apply`, che il framework vieta — e disegna un badge che nessun mockup ha mai chiesto | `library/modules/icons/` |
| 9 | Nessun `min-w-0` / `minmax(0,*)` intorno agli slider: dentro grid o flex crescono all'infinito | twig dei moduli |
| 10 | Nessun `data-lenis-prevent` sui contenitori con scroll proprio: la rotella scorre la pagina | twig dei moduli |
| 11 | `forms.css` dà solo la griglia, e un blocco azzera l'outline dei campi annullando `*:focus-visible` di `base.css` | `dev/css/partial/forms.css` |
| 12 | Id di checkbox/radio non prefissati dallo unit-tag CF7: due form nella stessa pagina collidono su `privacy` e la label spunta la casella dell'altro. Il regex matcha per caso (`value="(.*?)" \/>` cattura gli attributi dentro il value) | `functions/forms.php` |
| — | `--font-icons` FA6 → FA7 | ✅ già fatto in `3e37d50` |

I numeri 4, 5 e 11 sono quelli che costano di più: un fatal, un sito in seconda lingua senza chrome,
e il focus invisibile su tutti i form.

---

## Lotto 3 — Aggiunte

Quello che su Think Water si è rivelato framework e non progetto.

**Bridge Yoast per i moduli** (`functions/custom.php`) — è la singola aggiunta di più valore. Yoast
analizza `post_content`: su una pagina costruita a flexible content l'analisi gira sul vuoto. Misurato
là: **14 contenuti su 36**. Si ripresenta in ogni tema a moduli.
Due cose da sapere, entrambe pagate:
- Il filtro `wpseo_pre_analysis_post_content` **non serve**: in Yoast 28 è chiamato in un punto solo,
  per estrarre le immagini dell'anteprima social. L'analisi vive nel browser, quindi il testo si passa
  al motore JS con `YoastSEO.app.registerModification('content', …)`, agganciato in `admin_footer`
  con `jQuery(window).on('YoastSEO:ready')` come rete.
- Lo script è inline dentro il PHP: **la stringa di separazione va scritta `"\n\n"`**, non con newline
  letterali, o è un `SyntaxError` e non gira niente. È il bug trovato rileggendo il codice sorgente.
- ⚠️ Il testo è quello **salvato**: l'analisi si aggiorna al salvataggio, non mentre si scrive.

**`theme_menu_hide_unpublished()`** (`functions/menus.php`) — WP di suo scarta solo il cestino: una
bozza resta a menu con permalink `?page_id=`. Toglie anche i discendenti, o il figlio di una voce
nascosta si orfanizza e risale di livello. Sostituisce quattro controlli `status == 'publish'`
ripetuti nei twig.

**Breadcrumb Yoast** rimarkuppato coi filtri del plugin (`single_link_wrapper` → `li`,
`output_wrapper` → `ol`, separatore vuoto) + `dev/css/partial/breadcrumb.css`. Il `/` lo disegna il
CSS con `li + li::before`: fra due `<li>` un testo nudo non è markup valido. Yoast **ignora un
wrapper vuoto** e ricade su `span`, quindi il wrapper si sostituisce, non si elimina.

**`functions/acf.php`**
- `editor_color_palette()` come funzione vera, non come ricetta in CLAUDE.md: fonte unica per la
  palette dei campi ACF **e** per l'editor classico via `tiny_mce_before_init`.
- Nota: mettendo `display: grid` su un campo ACF va spento il clearfix di `.acf-hl`
  (`content: none` su `::before`/`::after`), o il primo pseudo-elemento diventa una cella e si vede
  come un buco davanti alle scelte.

**`functions/forms.php`** — oltre al fix del #12, le tre trappole CF7 da documentare:
- **`novalidate` sul wrapper `.form`**: CF7 valida a ogni `change` e il suo `validate()` scorre tutti
  i wrap fino al target, quindi cliccando un consenso in fondo segna errati tutti i campi vuoti sopra.
- **`wpcf7-submit` sul `<button>` custom**: è la classe che il suo JS cerca per disabilitare l'invio
  finché un `[acceptance]` obbligatorio non è spuntato.
- **Il CSS di CF7 è caricato senza `@layer`**, quindi batte qualunque layer dell'autore a prescindere
  dalla specificità: per raggiungere il suo `margin-left` sui `.wpcf7-list-item` serve `!important`.

**CSS**
- `partial/forms.css`: aspetto dei campi (bordo, raggio, fondo, chevron del select) **per elemento e
  non per classe**, così un form scritto senza `class:form-input` sui tag resta vestito.
- `base/custom.css`: `.icon-svg` (un SVG inline si dimensiona sul `font-size` del wrapper, così
  `text-5xl` vale per il glifo FA e per l'SVG allo stesso modo) e `.scroll-thin`, con le property
  standard dentro `@supports not selector(::-webkit-scrollbar)` — **solo Firefox**: in Chrome
  `scrollbar-color` ha la precedenza sui pseudo-elementi e ne annulla raggio e margini.
- `base/typography.css`: bullet delle liste allineato **per costruzione** (`top: 0` +
  `line-height: inherit`) invece che con un valore a occhio che sbaglia a ogni `font-size`.
- `layout/section-bg.css`: le esclusioni full-bleed **allineate a `common.css`**. Oggi le due liste si
  contraddicono — stando in `overrides`, `section-bg` ridà il padding a ogni `[data-bg]` e batte il
  `padding-block: 0` delle bande, quindi su un panel con sfondo l'immagine non arriva a filo.
  Regola generale: una regola in `overrides` che ridichiara una property del ritmo va scritta con le
  **stesse esclusioni** di quella base.
- `layout/common.css`: `.main:has(+ .cta-band)` per saldare la banda CTA all'ultima sezione.

**`dev/js/custom/custom.js`** — `SmoothScroll` va promosso nel commento: non è una utility
situazionale, è il **fallback di accessibilità** chiamato da `initAnchors()` quando Lenis è spento per
`prefers-reduced-motion`. Senza, gli anchor perdono l'offset dell'header sticky proprio per chi ha
scelto il moto ridotto.

---

## Lotto 4 — Potatura

| Da rimuovere | Perché |
|---|---|
| `author.php` + `templates/author.twig` | nessun design ha pagine autore; Yoast `disable-author` a true toglie superficie indicizzabile |
| `sidebar.php` + `templates/sidebar.twig` | — |
| `templates/comment.twig`, `comment-form.twig` | commenti disattivati di serie |
| `templates/tease.twig`, `archive-category.twig` | duplicati di `tease-post.twig` / `archive.twig` |
| `custom-page.php` + `templates/page-custom.twig` | un solo modo di fare una pagina interna: `page.twig` è il renderer dei moduli |
| `dev/css/partial/main-menu.css` | su un tema reale si è svuotato da sé: era trascrizione di utility |
| macro `intro()` | zero chiamate anche nel devkit — fossile |
| `autoprefixer` in devDependencies | **non è importato da nessuna parte**: `esbuild.js` dice esplicitamente di non usarlo (Tailwind 4 fa prefixing e nesting con Lightning CSS) |

---

## Lotto 5 — Convenzioni (`.claude/CLAUDE.md`)

Regole che hanno retto due mesi e mezzo e che il devkit oggi non scrive.

- **Commenti**: un paragrafo non si spezza su più righe — nei docblock PHP e nei blocchi `//` sta su
  UNA riga per quanto lunga, e i paragrafi si separano con una riga vuota. La lunghezza non è un
  problema, il paragrafo a fette lo è. Mai newline dentro `{# #}` / `/* */`. Il *perché* e i tentativi
  scartati vanno in `PROJECT.md`, non nel codice.
- **ACF**: repeater in vista **blocco**, con l'unica eccezione del repeater a un solo campo breve (in
  blocco diventerebbe una colonna di blocchi alti). Larghezze: campo lungo a piena larghezza, campi
  piccoli appaiati al 50%, 70/30 per la coppia titolo + tag. `collapsed` impostato sul campo che
  identifica la riga.
- **Gotcha ACF nuovi**:
  - riscrivere un flexible da codice lascia **meta fantasma**, e i valori vecchi ricompaiono sotto il
    modulo sbagliato: `update_field()` non cancella i sub-field assenti e `delete_field()` cancella
    solo quelli del layout corrente;
  - cancellare il JSON di un gruppo **non lo toglie dal DB**: serve `acf_delete_field_group()`;
  - `acf_import_field_group()` su certe installazioni **duplica** invece di aggiornare, e rigenerando i
    JSON dal DB ne perde dei campi;
  - i sub-field annidati hanno come parent il **campo**, non il gruppo: una query «orfani» scritta male
    cancella mezzo tema.
- **Struttura template**: chi estende `base.twig` sta in root, le sottocartelle solo per i pezzi che
  vengono inclusi. I tease stanno in root coi template che li usano.
- **Griglie di card con `div`**, non `ul/li`: tutti i moduli usano `div.grid` e la coerenza interna
  vale più del purismo. Corollario che è anche un bug: un `<a>` o `<article>` come flex item senza
  `w-full` si dimensiona sul contenuto, quindi le celle risultano di larghezze diverse.
- **Stato nelle utility, non nella cascata** (già in CLAUDE.md), col corollario costato un'ora: in un
  ternario Alpine **anche il non-stato** va nel ramo falso. Una classe statica e una classe del
  `:class` che si contendono la stessa property non hanno un vincitore prevedibile — vince quella
  emessa dopo da Tailwind.
- **Niente `<br>` nei contenuti**: le interruzioni di riga dei mockup dipendono dalla larghezza della
  colonna, non dal testo. E `text-balance` non è il rimedio: su testi corti in colonna stretta
  distribuisce peggio del wrap naturale.
- **Verifica mobile**: Chrome headless non scende sotto ~485px. Uno screenshot a 375 è **ritagliato**,
  non riscalato, e sembra overflow orizzontale. L'unica prova valida senza CDP è renderizzare a 485 e
  confrontare `scrollWidth` con `clientWidth`.
- **I mockup si normalizzano**: dove due tavole si contraddicono si sceglie, si annota in `PROJECT.md`
  e non si riproduce l'incoerenza.
- **Dipendenza esterna**: la sanificazione degli SVG all'upload sta nel modulo `svg-flatten` di
  **Bizen Toolkit**, non nel tema. Senza il plugin, due SVG di Illustrator inlineati nella stessa
  pagina si rubano i colori — le classi generate (`.cls-1`) sono identiche in tutti i file e l'ultimo
  `<style>` vince. Il sintomo è cattivo perché sembra una scelta di design sbagliata, non un bug.

---

## Lotto 6 — Meta, dipendenze, documentazione

- **`.gitignore`**: `.claude/` versionata, fuori solo `settings.local.json`. ✅ fatto
- **`.claude/settings.json`** con `plansDirectory: ".claude/plans"`: i piani nascono dentro il repo.
  La retention di Claude a 30 giorni passa su `~/.claude/`, non sulla cartella del progetto — su Think
  Water ha cancellato il piano per fasi e tutti i transcript, e a salvarsi è stato solo ciò che stava
  nel tema.
- **npm**: chalk 5→6, alpine 3.15→3.17, swiper 14.0→14.2, tailwind 4.3.2→4.3.3, lenis, postcss;
  via autoprefixer. **composer**: timber `^2.3` resta.
- **Versioni**: `package.json` e `composer.json` a `8.0`; `style.css` resta `1.0d0` (è il tema, non il
  devkit).
- **README** con la sezione migrazione v7.5 → v8.0 (firma della macro, file rimossi, tab).
- **START.md**: `languages/` fra i seam, `editor_color_palette()`, la voce «verifica le `sizes` dei
  moduli installati», e il promemoria che `body`/`.typo-r` a `font-weight: 400` è un default da
  ricontrollare contro le tavole a ogni progetto (su Think Water era Light su tutto).
- **`library/README.md`**: una colonna con la `sizes` attesa per ogni modulo che usa immagini.
- **Rebuild** di `assets/` (fermi al 31 luglio) e smoke test del build standalone.

---

## Cosa NON entra

`liquid.js` (riempimento liquido dei bottoni), store locator Google Maps, mega slider, timeline,
moduli custom di progetto, `layout/narrow.css`. Sono firme di quel progetto: `.btn` nel devkit resta
una base da rivestire.

## Cosa resta aperto

**Le `sizes` dei 13 call site.** Sono stringhe CSS che devono dire il vero, o il browser sceglie il
gradino sbagliato — in silenzio, senza errori. Si derivano staticamente leggendo la griglia dentro
ogni twig, e si scrivono col ragionamento annotato accanto, ma la conferma arriva guardando una
pagina. Presidi perché non si perda: la voce in `START.md` e la colonna in `library/README.md`.

---
---

# Backlog (non pianificato per la v8.0)

## 1. Code splitting del bundle JS

**Perché.** Il bundle è **356 KB min / 121 KB gzip** e viene caricato su ogni pagina, anche
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

**Stato.** Provato e funzionante (356 → 97.7 KB iniziali), poi rimosso: si tiene un bundle unico
finché non si decide anche la questione GSAP.

## 2. `functions/avif.php` → pacchetto composer

**Stato: chiusa in v8.0, con una soluzione diversa.** La libreria vive nella sua repo
(`zenotds/timber-avif`), che resta la sorgente ed è quello che si integra a mano nei temi esistenti;
nel devkit è **già integrata e distribuita**. Un fix nasce nella repo e scende nel devkit a ogni
rilascio, senza il peso di un pacchetto composer e di un mu-plugin per una dipendenza che il tema
vuole comunque dentro di sé.

Resta valido l'effetto collaterale che la voce indicava: se un domani servisse un `bizen/theme-core`
con twig filters, forms e acf helpers, è da lì che si parte.

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
