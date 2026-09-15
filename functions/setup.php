<?php

use Timber\Site;

/**
 * Class StarterSite
 */
class StarterSite extends Site
{
	public function __construct()
	{
		add_action('after_setup_theme', array($this, 'theme_supports'));
		add_action('init', array($this, 'register_post_types'));
		add_action('init', array($this, 'register_taxonomies'));

		add_filter('timber/context', array($this, 'add_to_context'));
		add_filter('timber/twig', array($this, 'add_to_twig'));
		add_filter('timber/twig/environment/options', [$this, 'update_twig_environment_options']);

		parent::__construct();
	}

	/**
	 * This is where you can register custom post types.
	 */
	public function register_post_types() {}

	/**
	 * This is where you can register custom taxonomies.
	 */
	public function register_taxonomies() {}

	/**
	 * This is where you add some context
	 *
	 * @param string $context context['this'] Being the Twig's {{ this }}.
	 */
	public function add_to_context($context)
	{
		// Globals
		$context['site']  = $this;
		$context['menu']  = Timber::get_menu();
		$context['server'] = (object) $_SERVER;
		$context['upload_dir'] = wp_get_upload_dir();
		$context['logout_url'] = wp_logout_url(home_url());
		$context['is_blog_page'] = is_home();

		// Menu
		$context['top_menu'] = Timber::get_menu('top_menu');
		$context['main_menu'] = Timber::get_menu('main_menu');
		$context['mobile_menu'] = Timber::get_menu('mobile_menu');
		$context['footer_menu'] = Timber::get_menu('footer_menu');
		$context['credits_menu'] = Timber::get_menu('credits_menu');

		// Archives
		$context['posts_page'] = get_option('page_for_posts');
		// $context['cpt_page'] = get_post_type_archive_link('cpt');

		// Lingue
		if (function_exists('icl_get_languages')) {
			$context["languages"] = icl_get_languages('skip_missing=0');
			$context["current_language"] = ICL_LANGUAGE_CODE;
		}

		// YOAST Breadcrumbs
		if (function_exists('yoast_breadcrumb') && !is_front_page()) {
			// La classe .breadcrumb la mette il filtro wpseo_breadcrumb_output_class sull'<ol> (functions/custom.php): qui resta il solo <nav> con l'etichetta
			$context['breadcrumbs'] = yoast_breadcrumb('<nav aria-label="breadcrumbs">', '</nav>', false);
		}

		// Get options
		if (function_exists('get_fields')) {
			$context["settings"] = $this->get_settings();
		};

		return $context;
	}

	/**
	 * Opzioni ACF con fallback sulla lingua di default.
	 *
	 * Con WPML attivo ACF legge le opzioni da `options_<lingua>` quando la lingua corrente non è quella di default (acf_get_valid_post_id): finché le opzioni della seconda lingua non sono compilate, header e footer restano senza logo, menu e testi — non tradotti, proprio assenti. Le opzioni della lingua di default riempiono le SOLE chiavi vuote, così una traduzione parziale è sempre meglio di un chrome rotto.
	 */
	private function get_settings()
	{
		$settings = get_fields('options') ?: [];

		if (!function_exists('acf_get_setting')) {
			return $settings;
		}

		$current = acf_get_setting('current_language');
		$default = acf_get_setting('default_language');

		if (!$current || !$default || $current === $default) {
			return $settings;
		}

		acf_update_setting('current_language', $default);
		$fallback = get_fields('options') ?: [];
		acf_update_setting('current_language', $current);

		foreach ($fallback as $key => $value) {
			// `empty` no: azzererebbe uno 0 o un false scelti in traduzione
			if (!isset($settings[$key]) || $settings[$key] === '' || $settings[$key] === []) {
				$settings[$key] = $value;
			}
		}

		return $settings;
	}

	public function theme_supports()
	{
		// Add default posts and comments RSS feed links to head.
		add_theme_support('automatic-feed-links');

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support('title-tag');

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support('post-thumbnails');

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				// 'comment-form',
				// 'comment-list',
				'gallery',
				'caption',
			)
		);

		/*
		 * Enable support for Post Formats.
		 *
		 * See: https://codex.wordpress.org/Post_Formats
		 */
		// add_theme_support(
		// 	'post-formats',
		// 	array(
		// 		'aside',
		// 		'image',
		// 		'video',
		// 		'quote',
		// 		'link',
		// 		'gallery',
		// 		'audio',
		// 	)
		// );

		add_theme_support('menus');

		/*
		 * Inject theme CSS inside the block editor iframe (apiVersion 3).
		 * WordPress prefixes selectors with .editor-styles-wrapper automatically.
		 * Solo con Gutenberg attivo: con l'editor classico gli editor-styles iniettano Tailwind nell'admin, TinyMCE compreso, e sporcano le WYSIWYG.
		 */
		if (defined('GUTENBERG_ENABLED') && GUTENBERG_ENABLED) {
			add_theme_support('editor-styles');
			add_editor_style('assets/css/styles.min.css');
		}
	}

	/**
	 * This is where you can add your own functions to twig.
	 *
	 * @param Twig\Environment $twig get extension.
	 */
	public function add_to_twig($twig)
	{
		/**
		 * Required when you want to use Twig’s template_from_string.
		 * @link https://twig.symfony.com/doc/3.x/functions/template_from_string.html
		 */
		// $twig->addExtension( new Twig\Extension\StringLoaderExtension() );

		return $twig;
	}

	/**
	 * Updates Twig environment options.
	 *
	 * @link https://twig.symfony.com/doc/2.x/api.html#environment-options
	 *
	 * \@param array $options An array of environment options.
	 *
	 * @return array
	 */
	function update_twig_environment_options($options)
	{
		// $options['autoescape'] = true;

		return $options;
	}
}
