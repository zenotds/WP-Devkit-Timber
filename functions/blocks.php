<?php
// =============================================================================
// BLOCK EDITOR CONFIGURATION
// =============================================================================

// Configuration switches - set only ONE to true
define('DISABLE_BLOCKS_COMPLETELY', false);    // Disables Gutenberg entirely, uses classic editor
define('ENABLE_ALL_BLOCKS', true);             // Default WordPress + custom blocks
define('ENABLE_ONLY_CUSTOM_BLOCKS', false);    // Only custom blocks, no WordPress defaults

// =============================================================================
// IMPLEMENTATION - Don't modify below unless you know what you're doing
// =============================================================================

// 1. DISABLE BLOCKS COMPLETELY
if (DISABLE_BLOCKS_COMPLETELY) {
    // Disable Gutenberg entirely
    add_filter('use_block_editor_for_post_type', '__return_false', 10);
    
    // Remove all block CSS
    function remove_all_block_css() {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('wc-blocks-style');
        wp_dequeue_style('classic-theme-styles');
    }
    add_action('wp_enqueue_scripts', 'remove_all_block_css', 100);
    add_action('admin_enqueue_scripts', 'remove_all_block_css', 100);
}

// 2. ENABLE ALL BLOCKS (WordPress defaults + custom)
if (ENABLE_ALL_BLOCKS) {
    // Register custom blocks alongside WordPress defaults
    function register_all_blocks() {
        register_custom_acf_blocks(); // Your custom blocks
        // WordPress default blocks remain available
    }
    add_action('init', 'register_all_blocks');
}

// 3. ENABLE ONLY CUSTOM BLOCKS
if (ENABLE_ONLY_CUSTOM_BLOCKS) {
    // Keep Gutenberg but restrict to custom blocks only
    function allowed_custom_blocks_only($allowed_blocks, $block_editor_context) {
        $custom_blocks = [];
        
        // Get blocks from block.json files
        foreach (get_custom_blocks() as $block_path) {
            $block_json_file = $block_path . '/block.json';
            if (file_exists($block_json_file)) {
                $block_json = json_decode(file_get_contents($block_json_file), true);
                if (isset($block_json['name'])) {
                    $custom_blocks[] = $block_json['name'];
                }
            }
        }
        
        // Get ACF registered blocks
        $acf_blocks = get_acf_registered_blocks();
        $custom_blocks = array_merge($custom_blocks, $acf_blocks);
        
        return array_unique($custom_blocks);
    }
    add_filter('allowed_block_types_all', 'allowed_custom_blocks_only', 10, 2);
    
    // Remove default block CSS but keep structure for custom blocks
    function remove_default_block_css() {
        wp_dequeue_style('wp-block-library-theme'); // Theme styles only
        // Keep wp-block-library for structure
    }
    add_action('wp_enqueue_scripts', 'remove_default_block_css', 100);
}

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

// Get custom blocks from /blocks directory with block.json
function get_custom_blocks() {
    $blocks = [];
    $blocks_dir = get_template_directory() . '/blocks';
    
    if (is_dir($blocks_dir)) {
        $block_folders = glob($blocks_dir . '/*/block.json');
        foreach ($block_folders as $block_json) {
            $blocks[] = dirname($block_json);
        }
    }
    return $blocks;
}

// Register blocks from block.json files
function register_json_blocks() {
    $custom_blocks = get_custom_blocks();
    foreach ($custom_blocks as $block_path) {
        register_block_type($block_path);
    }
}
add_action('init', 'register_json_blocks');

// Get list of ACF registered block names
function get_acf_registered_blocks() {
    $acf_blocks = [];
    if (function_exists('acf_get_block_types')) {
        $block_types = acf_get_block_types();
        foreach ($block_types as $block_type) {
            $acf_blocks[] = $block_type['name'];
        }
    }
    return $acf_blocks;
}

// =============================================================================
// BLOCK RENDERING
// =============================================================================

// Timber render callback for blocks
function block_render_callback($attributes, $content = '', $block = null) {
    // Extract slug from block name
    $block_name = isset($block['blockName']) ? $block['blockName'] : $attributes['name'];
    $slug = str_replace(['acf/', 'bizen/'], '', $block_name);
    
    $context = Timber::context();
    $context['attributes'] = $attributes;
    $context['fields'] = get_fields();
    $context['is_preview'] = is_admin() && isset($_GET['preview']);
    $context['block_id'] = 'block-' . uniqid();
    
    // Look for template file
    $template_paths = [
        'blocks/' . $slug . '/' . $slug . '.twig',
        'blocks/' . $slug . '.twig',
    ];
    
    foreach ($template_paths as $template) {
        if (Timber::compile($template, $context, false)) {
            Timber::render($template, $context);
            return;
        }
    }
    
    // Fallback if no template found
    echo '<div class="block-error">Template not found for block: ' . esc_html($slug) . '</div>';
}

// =============================================================================
// CUSTOM BLOCK REGISTRATION EXAMPLES
// =============================================================================

// Register your custom ACF blocks here
function register_custom_acf_blocks() {
    if (!function_exists('acf_register_block_type')) {
        return;
    }
    
    // Example: Hero Section Block
    acf_register_block_type([
        'name' => 'hero-section',
        'title' => __('Hero Section'),
        'description' => __('Custom hero section block'),
        'render_callback' => 'block_render_callback',
        'category' => 'layout',
        'icon' => 'cover-image',
        'keywords' => ['hero', 'banner', 'header'],
        'supports' => [
            'align' => ['full', 'wide'],
            'anchor' => true,
            'customClassName' => true,
        ],
    ]);
    
    // Example: Content Cards Block
    acf_register_block_type([
        'name' => 'content-cards',
        'title' => __('Content Cards'),
        'description' => __('Flexible content cards layout'),
        'render_callback' => 'block_render_callback',
        'category' => 'layout',
        'icon' => 'grid-view',
        'keywords' => ['cards', 'content', 'grid'],
        'supports' => [
            'align' => ['full', 'wide'],
            'anchor' => true,
        ],
    ]);
    
    // Add more custom blocks here...
}
add_action('acf/init', 'register_custom_acf_blocks');

// =============================================================================
// DEVELOPMENT HELPERS
// =============================================================================

// Debug: Show available blocks in admin (only for development)
if (defined('WP_DEBUG') && WP_DEBUG && is_admin()) {
    function debug_available_blocks() {
        if (current_user_can('manage_options')) {
            $screen = get_current_screen();
            if ($screen && $screen->is_block_editor()) {
                echo '<script>console.log("Available blocks:", wp.blocks.getBlockTypes().map(b => b.name));</script>';
            }
        }
    }
    add_action('admin_footer', 'debug_available_blocks');
}