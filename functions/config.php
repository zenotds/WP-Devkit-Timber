<?php

// ============================================
// CONFIGURAZIONE TEMA
// ============================================
// Unico punto di modifica per le scelte di sviluppo per-progetto.
// Le scelte si fanno qui, a inizio progetto: nessun toggle runtime.

// --------------------------------------------
// Namespace del tema
// --------------------------------------------
// Prefisso dei blocchi custom ("name" nei block.json: <namespace>/<slug>).
// dev/make-block.mjs legge questa costante: si cambia solo qui.
define('THEME_NAMESPACE', 'theme');

// --------------------------------------------
// Gutenberg
// --------------------------------------------
// Abilita o disabilita completamente Gutenberg
define('GUTENBERG_ENABLED', false);

// Se Gutenberg è abilitato, limita a pagine/post specifici tramite slug o ID.
// Lascia entrambi gli array vuoti per abilitarlo su tutti i tipi di post.
// Utile anche per setup misti: blocchi su alcune pagine, flexible content sulle altre.
define('GUTENBERG_ALLOWED_SLUGS', []); // Esempio: ['contacts', 'homepage']
define('GUTENBERG_ALLOWED_IDS', []); // Esempio: [12, 45, 67]

// Abilita o disabilita i blocchi core/nativi di Gutenberg
define('GUTENBERG_CORE_BLOCKS_ENABLED', true);

// Abilita o disabilita i blocchi personalizzati ACF/Timber (cartella /blocks/)
define('GUTENBERG_CUSTOM_BLOCKS_ENABLED', false);

// Blocchi core consentiti negli InnerBlocks quando i core blocks sono disabilitati
define('GUTENBERG_INNER_CORE_BLOCKS', [
    'core/paragraph',
    'core/heading',
    'core/list',
    'core/list-item',
    'core/buttons',
    'core/button',
    'core/image',
]);

// --------------------------------------------
// Chiavi API
// --------------------------------------------
// Le chiavi NON si committano nel tema: definisci GMAPS_API_KEY in wp-config.php.
// Qui c'è solo il fallback vuoto per evitare notice quando manca.
if (!defined('GMAPS_API_KEY')) {
    define('GMAPS_API_KEY', '');
}
