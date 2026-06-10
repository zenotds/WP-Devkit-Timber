<?php

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'theme_enqueue_assets');

function theme_enqueue_assets()
{
    $theme_version = wp_get_theme()->get('Version');

    // Enqueue main stylesheet
    wp_enqueue_style(
        'theme-styles',
        get_template_directory_uri() . '/assets/css/styles.min.css',
        array(),
        $theme_version
    );

    // Enqueue main JavaScript
    wp_enqueue_script(
        'theme-scripts',
        get_template_directory_uri() . '/assets/js/scripts.min.js',
        array(),
        $theme_version,
        true // Load in footer
    );

    // Add async and defer attributes to the script
    add_filter('script_loader_tag', function ($tag, $handle) {
        if ('theme-scripts' === $handle) {
            return str_replace(' src', ' async defer src', $tag);
        }
        return $tag;
    }, 10, 2);

    // Brand color from ACF options as CSS variable (pair with
    // `--color-accent: var(--brand-color)` in the Tailwind @theme block)
    // if (function_exists('get_field') && $brand_color = get_field('color', 'options')) {
    //     wp_add_inline_style('theme-styles', ":root { --brand-color: {$brand_color}; }");
    // }
}

// Preload critical assets
add_action('wp_head', 'theme_preload_assets', 1);

function theme_preload_assets()
{
    $theme_version = wp_get_theme()->get('Version');
?>
    <link rel="preload" href="<?php echo get_template_directory_uri(); ?>/assets/css/styles.min.css?ver=<?php echo $theme_version; ?>" as="style">
    <?php // Font preloads: uncomment once the woff2 files are in /assets/webfonts/ (e.g. FontAwesome Pro, added per project) ?>
    <?php /*
    <link rel="preload" href="<?php echo get_template_directory_uri(); ?>/assets/webfonts/fa-brands-400.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?php echo get_template_directory_uri(); ?>/assets/webfonts/fa-regular-400.woff2" as="font" type="font/woff2" crossorigin>
    */ ?>
<?php
}
