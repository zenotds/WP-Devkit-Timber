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
		$menu_config = devkit_config_get('menus', []);
		$context_config = devkit_config_get('context', []);

		$context['foo']   = 'bar';
		$context['menu']  = Timber::get_menu();
		$context['site']  = $this;

		// Globals
		$context['site']  = $this;
		$context['server'] = (object) $_SERVER;
		$context['upload_dir'] = wp_get_upload_dir();
		$context['logout_url'] = wp_logout_url(home_url());
		$context['is_blog_page'] = is_home();

		// Menu
		$context['top_menu'] = ($menu_config['top_menu'] ?? true) ? Timber::get_menu('top_menu') : null;
		$context['main_menu'] = ($menu_config['main_menu'] ?? true) ? Timber::get_menu('main_menu') : null;
		$context['mobile_menu'] = ($menu_config['mobile_menu'] ?? true) ? Timber::get_menu('mobile_menu') : null;
		$context['footer_menu'] = ($menu_config['footer_menu'] ?? true) ? Timber::get_menu('footer_menu') : null;
		$context['credits_menu'] = ($menu_config['credits_menu'] ?? true) ? Timber::get_menu('credits_menu') : null;

		// Collections
		$context['posts'] = ($context_config['preloadPosts'] ?? true)
			? Timber::get_posts([
				'post_type' => 'post',
				'orderby' => 'date',
				'order' => 'DESC',
				'posts_per_page' => -1,
			])->to_array()
			: [];

		// Taxonomies
		$context['categories'] = ($context_config['preloadCategories'] ?? true)
			? Timber::get_terms([
				'taxonomy' => 'category',
				'hide_empty' => true,
			])
			: [];

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
			$context['breadcrumbs'] = yoast_breadcrumb('<nav class="breadcrumb" aria-label="breadcrumbs">', '</nav>', false);
		}

		// Get options
		if (function_exists('get_fields')) {
			$context["settings"] = get_fields("options");
		};

		return $context;
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
