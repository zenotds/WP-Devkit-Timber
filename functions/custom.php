<?php
// Kill language error
add_filter('doing_it_wrong_trigger_error', function () {
	return false;
}, 10, 0);

// Funzioni per ottimizzare le immagini immagini
add_filter('jpeg_quality', function ($arg) {
	return 100;
});

add_filter('intermediate_image_sizes', function ($sizes) {
	return array_diff($sizes, ['medium_large']);  // Medium Large (768 x 0)
});

add_action('init', function () {
	remove_image_size('1536x1536'); // Medium Large (1536 x 1536)
	remove_image_size('2048x2048'); // Large (2048 x 2048)
});

// Init traduzione stringhe
function theme_load_theme_textdomain()
{
	load_theme_textdomain('theme', get_template_directory() . '/lang');
}
add_action('after_setup_theme', 'theme_load_theme_textdomain');

// Disable emoji
function disable_emojis()
{
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('admin_print_scripts', 'print_emoji_detection_script');
	remove_action('wp_print_styles', 'print_emoji_styles');
	remove_action('admin_print_styles', 'print_emoji_styles');
	remove_filter('the_content_feed', 'wp_staticize_emoji');
	remove_filter('comment_text_rss', 'wp_staticize_emoji');
	remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
	add_filter('tiny_mce_plugins', 'disable_emojis_tinymce');
	add_filter('option_use_smilies', function () {
		return false;
	});
}
add_action('init', 'disable_emojis');

function disable_emojis_tinymce($plugins)
{
	if (is_array($plugins)) {
		return array_diff($plugins, array('wpemoji'));
	} else {
		return array();
	}
}

// Fix per quell'abominio di Yoast Breadcrumbs
// Toglie gli stupidi span dall'output
function filter_yoast_breadcrumb_output($output)
{
	$from = '<span>';
	$to = '</span>';
	$output = str_replace($from, $to, $output);
	return $output;
}
add_filter('wpseo_breadcrumb_output', 'filter_yoast_breadcrumb_output');

// Modifiche all'elemento BC
add_filter('wpseo_breadcrumb_single_link', 'custom_yoast_breadcrumb_single_link', 10, 2);
function custom_yoast_breadcrumb_single_link($link_output, $link)
{
	// Check if the URL and text are set to avoid any notices or errors.
	if (isset($link['url']) && isset($link['text'])) {
		// Rebuild the breadcrumb item.
		$link_output = '<li class="breadcrumb-item">';
		$link_output .= '<a href="' . esc_url($link['url']) . '" title="' . esc_attr($link['text']) . '">' . esc_html($link['text']) . '</a>';
		$link_output .= '</li>';
	}

	return $link_output;
}

// WPRocket for editors
function custom_wp_rocket()
{
	// gets the author role object
	$role = get_role('editor');

	// add a new capability
	$role->add_cap('rocket_regenerate_critical_css', true);
	$role->add_cap('rocket_purge_cache', true);
	$role->add_cap('rocket_purge_cloudflare_cache', true);
	$role->add_cap('rocket_purge_sucuri_cache', true);
	$role->add_cap('rocket_preload_cache', true);
	$role->add_cap('rocket_remove_unused_css', true);
	$role->add_cap('rocket_purge_posts', true);
}
add_action('init', 'custom_wp_rocket', 12);
