<?php
// Kill language error
add_filter('doing_it_wrong_trigger_error', function () {
	return false;
}, 10, 0);

// Le sole size di WordPress che restano sono thumbnail (griglia dell'admin) e medium: il resto lo coprono le larghezze canoniche che Timber AVIF registra come size proprie (tavif-*), quindi le size intermedie sarebbero solo file in più a ogni upload.
// Anche 1536x1536 e 2048x2048 si tolgono di qui: un remove_image_size() su init sarebbe un secondo posto che dice la stessa cosa. Le tavif-* non vanno tolte: sono le varianti del srcset.
// La qualità JPEG la governa Timber AVIF (impostazione jpeg_quality, default 82): un filtro qui non avrebbe effetto, perché Timber AVIF aggancia jpeg_quality a priorità 20, dopo il tema.
add_filter('intermediate_image_sizes', function ($sizes) {
	return array_diff($sizes, ['medium_large', 'large', '1536x1536', '2048x2048']);
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

// Breadcrumb Yoast come lista vera: <ol class="breadcrumb"> con una <li> per voce invece degli <span> di default.
// Il wrapper esterno si SOSTITUISCE, non si elimina: Yoast ignora un wrapper vuoto e ricade su `span`.
add_filter('wpseo_breadcrumb_single_link_wrapper', fn() => 'li');
add_filter('wpseo_breadcrumb_output_wrapper', fn() => 'ol');
add_filter('wpseo_breadcrumb_output_class', fn() => 'breadcrumb');

// Separatore vuoto: un `/` nudo fra due <li> non è markup valido dentro un <ol>.
// Lo disegna il CSS con `li + li::before` (dev/css/partial/breadcrumb.css)
add_filter('wpseo_breadcrumb_separator', fn() => '');

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


/** Testo dei moduli del flexible, per l'analisi di Yoast. Salta chiavi tecniche, valori non testuali e URL. */
function theme_modules_text($value, array $skip): string
{
	if (is_string($value)) {
		$value = trim($value);

		return $value !== '' && !filter_var($value, FILTER_VALIDATE_URL) && preg_match('/\p{L}/u', $value) ? $value : '';
	}

	if (!is_array($value)) {
		return '';
	}

	$out = [];

	foreach ($value as $key => $item) {
		if (is_string($key) && in_array($key, $skip, true)) {
			continue;
		}

		if ($text = theme_modules_text($item, $skip)) {
			$out[] = $text;
		}
	}

	return implode("\n\n", $out);
}

/** Testo dei moduli di un contenuto, per l'analisi di Yoast. Vuoto se il flexible non c'è. */
function theme_post_modules_text(int $id): string
{
	if (!function_exists('get_field')) {
		return '';
	}

	$modules = get_field('content', $id);

	// Chiavi che non portano testo leggibile: opzioni di resa, agganci e riferimenti. I moduli di progetto aggiungono qui le proprie.
	$skip = [
		'acf_fc_layout', 'unique_id', 'bg', 'section_id', 'tag', 'align', 'view', 'aspect', 'size',
		'surface', 'boxed', 'columns', 'icon', 'icon_style', 'image', 'images', 'bg_image', 'cover',
		'media', 'gallery', 'video', 'embed', 'poster', 'file', 'logo', 'form', 'typ', 'posts',
		'items_source', 'color', 'style',
	];

	return is_array($modules) ? theme_modules_text($modules, $skip) : '';
}

/**
 * Yoast analizza il contenuto dell'editor, che su una pagina costruita a moduli è vuoto: senza questo ponte l'analisi gira sul nulla e il semaforo resta rosso su ogni pagina.
 *
 * L'analisi di Yoast 28 vive nel browser, quindi il testo dei moduli va passato al suo motore JS: il filtro PHP `wpseo_pre_analysis_post_content` non serve, è chiamato in un punto solo e per estrarre le immagini dell'anteprima social.
 *
 * ⚠️ Il testo è quello SALVATO: l'analisi si aggiorna al salvataggio, non mentre si scrive nei campi.
 */
function theme_yoast_modules_bridge(): void
{
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;

	$id = get_the_ID() ?: (int) ($_GET['post'] ?? 0);

	if (!$screen || $screen->base !== 'post' || !$id) {
		return;
	}

	$text = theme_post_modules_text($id);

	if (!$text) {
		return;
	}
	?>
	<script>
		(function () {
			var extra = <?php echo wp_json_encode($text); ?>;

			function register() {
				if (typeof YoastSEO === "undefined" || !YoastSEO.app) {
					return;
				}
				YoastSEO.app.registerPlugin("themeModules", { status: "ready" });
				// La separazione va scritta "\n\n": qui siamo fuori dal PHP, quindi newline letterali finirebbero dentro la stringa JS e sarebbero un SyntaxError
				YoastSEO.app.registerModification("content", function (data) {
					return data + "\n\n" + extra;
				}, "themeModules", 5);
			}

			if (typeof YoastSEO !== "undefined" && YoastSEO.app) {
				register();
			} else {
				jQuery(window).on("YoastSEO:ready", register);
			}
		})();
	</script>
	<?php
}
add_action('admin_footer', 'theme_yoast_modules_bridge');
