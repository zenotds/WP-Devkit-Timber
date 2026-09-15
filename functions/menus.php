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

// Le voci che puntano a contenuti non pubblicati non escono in front end.
// WP di suo scarta solo il cestino: una bozza resta a menu con un permalink `?page_id=`.
// Vale per tutti i menu e per tutti i template, così i twig non devono ripetere il controllo.
function theme_menu_hide_unpublished( $items, $menu, $args ) {
	if ( is_admin() || empty( $items ) ) {
		return $items;
	}

	$dropped = array();

	foreach ( $items as $item ) {
		if ( 'post_type' === $item->type && ! is_post_publicly_viewable( $item->object_id ) ) {
			$dropped[ $item->ID ] = true;
		}
	}

	if ( ! $dropped ) {
		return $items;
	}

	// Il figlio di una voce rimossa va rimosso anche lui, o si orfanizza e risale di livello.
	// Si ripete finché la lista è stabile: i livelli possono essere più di due.
	do {
		$again = false;
		foreach ( $items as $item ) {
			if ( ! isset( $dropped[ $item->ID ] ) && isset( $dropped[ $item->menu_item_parent ] ) ) {
				$dropped[ $item->ID ] = true;
				$again = true;
			}
		}
	} while ( $again );

	return array_values( array_filter( $items, fn( $item ) => ! isset( $dropped[ $item->ID ] ) ) );
}
add_filter( 'wp_get_nav_menu_items', 'theme_menu_hide_unpublished', 10, 3 );
