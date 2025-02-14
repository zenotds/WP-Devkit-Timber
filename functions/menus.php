<?php

// Registra posizione del menu
// NB: aggiungere i nuovi menu al contesto in setup.php
function register_menu_locations() {
	register_nav_menus(
		array(
			'top_menu' => 'Top Menu',
			'main_menu' => 'Main Menu',
			'mobile_menu' => 'Mobile Menu',
			'footer_menu' => 'Footer Menu',
			'credits_menu' => 'Credits Menu',
		)
	);
}
add_action( 'init', 'register_menu_locations' );