// Scaffolda un nuovo modulo flexible content (twig dal contratto standard).
// Usage: npm run make:module -- <slug> "<Titolo>"

import fs from "node:fs";
import path from "node:path";

const [slug, title] = process.argv.slice(2);

if (!slug || !/^[a-z][a-z0-9_]*$/.test(slug)) {
	console.error('Usage: npm run make:module -- <slug> "<Titolo>"');
	console.error("Lo slug deve essere snake_case minuscolo (es. hero, team, timeline).");
	process.exit(1);
}

const moduleTitle = title || slug.charAt(0).toUpperCase() + slug.slice(1);
const target = path.resolve(`templates/components/block-${slug}.twig`);

if (fs.existsSync(target)) {
	console.error(`Il modulo esiste già: ${target}`);
	process.exit(1);
}

const twig = `{# Modulo ${moduleTitle} — TODO: descrizione in una riga; dipendenze (Alpine, Swiper, FA...) #}
{% import '/partial/macros.twig' as macros %}
{% set tag = content.tag|default('h2') %}
{% set bg = content.bg|default('none') %}

<section class="block-${slug}" id="{{ content.section_id|default('section-' ~ loop.index) }}" data-loop="{{ loop.index }}"{% if bg != 'none' %} data-bg="{{ bg }}"{% endif %}>
    <div class="container mx-auto">

        {# Intro standard: title 70 / tag 30 in admin, subtitle text-lead #}
        {% if content.title or content.subtitle %}
            <div class="intro mb-8 lg:mb-12">
                {% if content.title %}
                    <{{ tag }} class="title typo-h text-{{ tag }} has-[+*]:mb-4">{{ content.title }}</{{ tag }}>
                {% endif %}
                {% if content.subtitle %}
                    <p class="subtitle text-lead">{{ content.subtitle }}</p>
                {% endif %}
            </div>
        {% endif %}

        {# TODO: contenuto del modulo #}

    </div>
</section>
`;

fs.writeFileSync(target, twig);

console.log(`✅ Modulo creato: templates/components/block-${slug}.twig`);
console.log("Prossimi passi:");
console.log(`  1. Crea il gruppo ACF "Modulo: ${moduleTitle}" in admin (active: false, location dummy su post)`);
console.log(`     con i campi standard bg/section_id + intro (title/tag/subtitle) e i campi del modulo`);
console.log(`  2. Aggiungi al flexible "Contenuti" il layout "${slug}" con clone seamless del gruppo`);
console.log(`  3. Sync ACF in admin, poi seed di un esempio sulla pagina libreria`);
console.log(`  4. CSS di modulo (se serve): dev/css/components/${slug}.css + import in styles.css`);
