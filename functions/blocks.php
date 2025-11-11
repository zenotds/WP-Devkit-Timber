<?php
// ============================================
// CONFIGURAZIONE GUTENBERG
// ============================================

// Abilita o disabilita completamente Gutenberg
define('GUTENBERG_ENABLED', false);

// Se Gutenberg è abilitato, limita a pagine/post specifici tramite slug o ID.
// Lascia entrambi gli array vuoti per abilitarlo su tutti i tipi di post.
define('GUTENBERG_ALLOWED_SLUGS', []); // Esempio: ['contacts', 'homepage']
define('GUTENBERG_ALLOWED_IDS', []); // Esempio: [12, 45, 67]

// Abilita o disabilita i blocchi core/nativi di Gutenberg
define('GUTENBERG_CORE_BLOCKS_ENABLED', true);

// Abilita o disabilita i blocchi personalizzati Timber/ACF
define('GUTENBERG_CUSTOM_BLOCKS_ENABLED', false);

// ============================================
// IMPLEMENTAZIONE
// ============================================

/**
 * Controlla la disponibilità dell'editor Gutenberg
 */
if (!GUTENBERG_ENABLED) {
    // Se la costante globale è false, disabilita Gutenberg ovunque.
    add_filter('use_block_editor_for_post_type', '__return_false', 10);
} else {
    /**
     * Abilita Gutenberg solo per post, pagine o CPT specifici.
     * Questa funzione viene eseguita per tutti i tipi di post.
     * La logica è: disabilita di default, abilita solo se le condizioni sono soddisfatte.
     */
    function conditionally_enable_gutenberg($use_block_editor, $post) {
        // Se non c'è un oggetto $post (es. su alcune schermate di amministrazione), non fare nulla.
        if (!$post) {
            return $use_block_editor;
        }

        // Se entrambi gli array di ID e slug sono vuoti, significa che vogliamo abilitare Gutenberg ovunque.
        if (empty(GUTENBERG_ALLOWED_IDS) && empty(GUTENBERG_ALLOWED_SLUGS)) {
            return true;
        }

        // Controlla se l'ID del post è nell'array degli ID consentiti.
        if (in_array($post->ID, GUTENBERG_ALLOWED_IDS)) {
            return true; // Abilita Gutenberg
        }

        // Controlla se lo slug del post (post_name) è nell'array degli slug consentiti.
        if (in_array($post->post_name, GUTENBERG_ALLOWED_SLUGS)) {
            return true; // Abilita Gutenberg
        }

        // Se nessuna delle condizioni sopra è soddisfatta, disabilita Gutenberg.
        // Questo si applica a tutti i post, pagine e CPT che non corrispondono,
        // incluse le schermate "Aggiungi Nuovo".
        return false;
    }
    // Usiamo 'use_block_editor_for_post' che passa direttamente l'oggetto $post.
    add_filter('use_block_editor_for_post', 'conditionally_enable_gutenberg', 10, 2);
}

/**
 * Rimuove gli stili dei blocchi core di Gutenberg dal frontend
 */
if (GUTENBERG_ENABLED && !GUTENBERG_CORE_BLOCKS_ENABLED) {
    function remove_block_css() {
        wp_dequeue_style('wp-block-library'); // Core di WordPress
        wp_dequeue_style('wp-block-library-theme'); // Tema Core di WordPress
        wp_dequeue_style('wc-block-style'); // WooCommerce
        wp_dequeue_style('storefront-gutenberg-blocks'); // Tema Storefront
    }
    add_action('wp_enqueue_scripts', 'remove_block_css', 100);
}

/**
 * Logica per i blocchi personalizzati Timber/ACF
 */
if (GUTENBERG_ENABLED && GUTENBERG_CUSTOM_BLOCKS_ENABLED) {
    
    // Funzione per raccogliere i blocchi personalizzati
    function get_custom_blocks() {
        $blocks = array();
        $blocks_dir = get_template_directory() . '/blocks';
        
        if (is_dir($blocks_dir)) {
            foreach (new DirectoryIterator($blocks_dir) as $item) {
                if ($item->isDir() && !$item->isDot() && file_exists($item->getPathname() . '/block.json')) {
                    $blocks[] = $item->getPathname();
                }
            }
        }
        
        return $blocks;
    }

    // Registrazione dei blocchi ACF personalizzati
    function register_acf_blocks() {
        $custom_blocks = get_custom_blocks();
        
        foreach ($custom_blocks as $block) {
            register_block_type($block);
        }
    }
    add_action('init', 'register_acf_blocks');

    // Limitazione ai soli blocchi personalizzati se i blocchi core sono disabilitati
    if (!GUTENBERG_CORE_BLOCKS_ENABLED) {
        function allowed_block_types($allowed_blocks, $post) {
            $custom_blocks = array();
            
            foreach (get_custom_blocks() as $block) {
                // Estrai il nome del blocco dal percorso
                $block_name = basename($block);
                $custom_blocks[] = 'bizen/' . $block_name;
            }
            
            return $custom_blocks;
        }
        add_filter('allowed_block_types_all', 'allowed_block_types', 10, 2);
    }

    // Callback di rendering per i blocchi Timber/ACF
    function block_render_callback($attributes, $content = '', $is_preview = false, $post_id = 0, $wp_block = null) {
        // Crea lo slug del blocco usando la proprietà 'name' in block.json
        $slug = str_replace('bizen/', '', $attributes['name']);
        $context = Timber::context();

        // Memorizza gli attributi del blocco
        $context['attributes'] = $attributes;

        // Memorizza i valori dei campi ACF
        $context['fields'] = get_fields();

        // Memorizza se il blocco è in modalità anteprima o frontend
        $context['is_preview'] = $is_preview;

        // Renderizza il blocco
        Timber::render(
            'blocks/' . $slug . '/' . $slug . '.twig',
            $context
        );
    }
}