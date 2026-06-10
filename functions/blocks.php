<?php
// ============================================
// CONFIGURAZIONE GUTENBERG
// ============================================

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

// Namespace dei blocchi custom del tema (deve corrispondere a "name" nei block.json)
define('GUTENBERG_BLOCKS_NAMESPACE', 'bizen');

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

// ============================================
// DISPONIBILITÀ EDITOR
// ============================================

if (!GUTENBERG_ENABLED) {
    // Se la costante globale è false, disabilita Gutenberg ovunque.
    add_filter('use_block_editor_for_post_type', '__return_false', 10);
} else {
    /**
     * Abilita Gutenberg solo per post, pagine o CPT specifici.
     * Con entrambi gli array vuoti, l'editor è attivo ovunque.
     */
    function conditionally_enable_gutenberg($use_block_editor, $post)
    {
        if (!$post) {
            return $use_block_editor;
        }

        if (empty(GUTENBERG_ALLOWED_IDS) && empty(GUTENBERG_ALLOWED_SLUGS)) {
            return true;
        }

        if (in_array($post->ID, GUTENBERG_ALLOWED_IDS)) {
            return true;
        }

        if (in_array($post->post_name, GUTENBERG_ALLOWED_SLUGS)) {
            return true;
        }

        return false;
    }
    add_filter('use_block_editor_for_post', 'conditionally_enable_gutenberg', 10, 2);
}

/**
 * Rimuove gli stili dei blocchi core di Gutenberg dal frontend
 */
if (GUTENBERG_ENABLED && !GUTENBERG_CORE_BLOCKS_ENABLED) {
    function remove_block_css()
    {
        wp_dequeue_style('wp-block-library'); // Core di WordPress
        wp_dequeue_style('wp-block-library-theme'); // Tema Core di WordPress
        wp_dequeue_style('wc-block-style'); // WooCommerce
        wp_dequeue_style('storefront-gutenberg-blocks'); // Tema Storefront
    }
    add_action('wp_enqueue_scripts', 'remove_block_css', 100);
}

// ============================================
// BLOCCHI CUSTOM ACF + TIMBER
// ============================================

/**
 * Raccoglie i blocchi custom dalla cartella /blocks/ (slug => path)
 */
function get_custom_blocks(): array
{
    static $blocks = null;

    if ($blocks === null) {
        $blocks = [];
        foreach (glob(get_template_directory() . '/blocks/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (file_exists($dir . '/block.json')) {
                $blocks[basename($dir)] = $dir;
            }
        }
    }

    return $blocks;
}

/**
 * ACF JSON per-blocco: ogni blocco è un'unità autonoma (block.json + twig + fields.json).
 * Questi filtri restano attivi anche con i blocchi disabilitati, così il sync ACF
 * non segnala field group mancanti.
 */
add_filter('acf/settings/load_json', function ($paths) {
    return array_merge($paths, array_values(get_custom_blocks()));
});

// Salva il field group nella cartella del blocco (location rule: block == bizen/<slug>)
add_filter('acf/json/save_paths', function ($paths, $post) {
    foreach ($post['location'] ?? [] as $group) {
        foreach ($group as $rule) {
            if ($rule['param'] === 'block' && str_starts_with($rule['value'], GUTENBERG_BLOCKS_NAMESPACE . '/')) {
                $dir = get_template_directory() . '/blocks/' . substr($rule['value'], strlen(GUTENBERG_BLOCKS_NAMESPACE) + 1);
                if (is_dir($dir)) {
                    return [$dir];
                }
            }
        }
    }
    return $paths;
}, 10, 2);

// Nome file leggibile per i field group dei blocchi
add_filter('acf/json/save_file_name', function ($filename, $post) {
    foreach ($post['location'] ?? [] as $group) {
        foreach ($group as $rule) {
            if ($rule['param'] === 'block') {
                return 'fields.json';
            }
        }
    }
    return $filename;
}, 10, 2);

/**
 * Render callback generico: ogni blocco renderizza blocks/<slug>/<slug>.twig
 * via Timber. Firma ACF: il primo parametro è l'array $block.
 */
function bizen_block_render($block, $content = '', $is_preview = false, $post_id = 0, $wp_block = null, $context = false)
{
    $slug = str_replace(GUTENBERG_BLOCKS_NAMESPACE . '/', '', $block['name']);

    // Screenshot di anteprima nell'inserter (vedi "example" in block.json)
    if ($is_preview && !empty($block['data']['is_example'])) {
        $preview = get_template_directory() . '/blocks/' . $slug . '/preview.png';
        if (file_exists($preview)) {
            echo '<img src="' . esc_url(get_template_directory_uri() . '/blocks/' . $slug . '/preview.png') . '" style="width:100%;height:auto;display:block;">';
            return;
        }
    }

    $ctx = Timber\Timber::context();
    $ctx['block'] = $block;
    $ctx['fields'] = function_exists('get_fields') ? (get_fields() ?: []) : [];
    $ctx['is_preview'] = $is_preview;
    $ctx['post_id'] = $post_id;
    $ctx['content'] = $content;

    // Attributi comodi pre-calcolati
    $ctx['block_id'] = !empty($block['anchor']) ? $block['anchor'] : 'block-' . $block['id'];
    $ctx['classes'] = implode(' ', array_filter([
        'block-' . $slug,
        $block['className'] ?? '',
        !empty($block['align']) ? 'align' . $block['align'] : '',
    ]));

    Timber\Timber::render('@blocks/' . $slug . '/' . $slug . '.twig', $ctx);
}

if (GUTENBERG_ENABLED && GUTENBERG_CUSTOM_BLOCKS_ENABLED) {

    // Registrazione dei blocchi via block.json
    add_action('init', function () {
        foreach (get_custom_blocks() as $path) {
            register_block_type($path);
        }
    });

    // Categoria custom del tema nell'inserter
    add_filter('block_categories_all', function ($categories) {
        array_unshift($categories, [
            'slug' => GUTENBERG_BLOCKS_NAMESPACE,
            'title' => __('Blocchi tema', 'theme'),
            'icon' => 'layout',
        ]);
        return $categories;
    });

    // Namespace Twig @blocks per i template dei blocchi
    add_filter('timber/locations', function ($paths) {
        $paths['blocks'] = [get_template_directory() . '/blocks'];
        return $paths;
    });

    // Con i core blocks disabilitati, limita l'inserter ai blocchi custom
    // più i core necessari agli InnerBlocks
    if (!GUTENBERG_CORE_BLOCKS_ENABLED) {
        add_filter('allowed_block_types_all', function ($allowed_blocks, $context) {
            $custom_blocks = array_map(
                fn($slug) => GUTENBERG_BLOCKS_NAMESPACE . '/' . $slug,
                array_keys(get_custom_blocks())
            );
            return array_merge($custom_blocks, GUTENBERG_INNER_CORE_BLOCKS);
        }, 10, 2);
    }
}
