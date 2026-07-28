# Library — dispensa moduli

Moduli flexible content **pronti ma non attivi**: niente qui viene caricato dal tema.
Sono la distillazione di temi di produzione: si copia dentro solo ciò che serve al
progetto, il boilerplate resta magro.

Nel tema sono attivi solo `free` e `media` (i due universali): usali come riferimento
del contratto modulo (vedi `.claude/CLAUDE.md` → Moduli).

## Come si installa un modulo

1. **Twig**: copia `library/modules/<nome>/block-<nome>.twig` in `templates/components/`.
2. **Campi**: copia `fields.json` in `acf-json/` rinominandolo `group_modulo-<nome>.json`
   (nome file parlante; le chiavi dentro restano hash), poi in admin **ACF → Sincronizza**.
3. **Layout**: aggiungi al flexible "Contenuti" un layout `<nome>` con **clone seamless**
   del gruppo appena sincronizzato (label + name identici al twig).
4. **Extra** (solo se presenti nella cartella):
   - `<nome>.css` → `dev/css/components/` + import nella sezione Components di `styles.css`
   - `functions.php` → segui le istruzioni nel file (es. `posts` registra `module_posts()`)
   - altri twig di supporto (es. `tease-card.twig` di posts) → `templates/`
5. Gli init JS e il CSS Swiper generici (`.media-slider`, `.posts-slider`, `[data-countup]`,
   `data-typ`, popup trigger) sono GIÀ nel devkit: nessun passo aggiuntivo.

## Moduli disponibili

| Modulo | Cosa fa | Extra |
|---|---|---|
| columns | testo + media affiancati (media flexible annidato) | — |
| panel | split full-bleed immagine+testo (escluso dal ritmo verticale) | — |
| cards | card con immagine (repeater) | — |
| icons | card con icona FA, vista grid/carousel | icons.css |
| numbers | contatori `[data-countup]` | — |
| accordion | fisarmonica Alpine collapse | — |
| faq | Q&A con JSON-LD FAQPage | faq.css |
| cta | banda accent con immagine di sfondo opzionale | — |
| form | CF7 + `data-typ` redirect thank-you | — |
| contacts | mappa embed + testo | — |
| separator | `<hr>` | — |
| shortcode | shortcode WP | — |
| code | HTML/embed grezzo | — |
| posts | estrazione articoli in carosello `.posts-slider` | functions.php, tease-card.twig |

Le chiavi ACF dei `fields.json` sono hash random già univoci: nessun conflitto
se ne installi più d'uno o con i gruppi esistenti del progetto.
