<?php

// Registra posizione del menu
// NB: aggiungere i nuovi menu al contesto in setup.php
function register_menu_locations() {
	$menu_config = devkit_config_get('menus', []);

	$menus = array(
		'top_menu' => 'Top Menu',
		'main_menu' => 'Main Menu',
		'mobile_menu' => 'Mobile Menu',
		'footer_menu' => 'Footer Menu',
		'credits_menu' => 'Credits Menu',
	);

	$menus = array_filter(
		$menus,
		function ($label, $location) use ($menu_config) {
			return $menu_config[$location] ?? true;
		},
		ARRAY_FILTER_USE_BOTH
	);

	if (empty($menus)) {
		return;
	}

	register_nav_menus($menus);
}
add_action( 'init', 'register_menu_locations' );
