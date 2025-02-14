<?php

// Attivazione Pagine Opzioni
if (function_exists('acf_add_options_page')) {

	acf_add_options_page(array(
		'page_title' 	=> 'Opzioni',
		'menu_title'	=> 'Opzioni',
		'menu_slug' 	=> 'site-settings',
		'capability'	=> 'edit_posts',
		'redirect'		=> true,
		'position'		=> 2,
		'icon_url'		=> 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI1LjIuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxpdmVsbG9fMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiCgkgdmlld0JveD0iMCAwIDI0NCAyNDQiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDI0NCAyNDQ7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4KPHN0eWxlIHR5cGU9InRleHQvY3NzIj4KCS5zdDB7ZmlsbDojOUVBM0E4O30KPC9zdHlsZT4KPGcgaWQ9InBpdHRvZ3JhbW1hIj4KCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0xNDIuOCwxOTMuMmgtNDFjLTExLjMsMC0yMC40LTkuMS0yMC40LTIwLjRsMCwwYzAtMTEuMyw5LjEtMjAuNCwyMC40LTIwLjRoNDFjMTEuMywwLDIwLjQsOS4xLDIwLjQsMjAuNGwwLDAKCQlDMTYzLjIsMTg0LjEsMTU0LjEsMTkzLjIsMTQyLjgsMTkzLjJ6Ii8+Cgk8ZWxsaXBzZSBjbGFzcz0ic3QwIiBjeD0iMTIyLjMiIGN5PSIyMjMuNyIgcng9IjIwLjQiIHJ5PSIyMC4zIi8+Cgk8ZWxsaXBzZSBjbGFzcz0ic3QwIiBjeD0iMTIyLjMiIGN5PSIyMC4zIiByeD0iMjAuNCIgcnk9IjIwLjMiLz4KCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik05MS40LDE0Mi4yaC00MWMtMTEuMywwLTIwLjQtOS4xLTIwLjQtMjAuNGwwLDBjMC0xMS4zLDkuMS0yMC40LDIwLjQtMjAuNGg0MWMxMS4zLDAsMjAuNCw5LjEsMjAuNCwyMC40bDAsMAoJCUMxMTEuOSwxMzMuMSwxMDIuNywxNDIuMiw5MS40LDE0Mi4yeiIvPgoJPGVsbGlwc2UgY2xhc3M9InN0MCIgY3g9IjE0Mi41IiBjeT0iMTIxLjgiIHJ4PSIyMC40IiByeT0iMjAuMyIvPgoJPGVsbGlwc2UgY2xhc3M9InN0MCIgY3g9IjE5My42IiBjeT0iMTIxLjgiIHJ4PSIyMC40IiByeT0iMjAuMyIvPgoJPGVsbGlwc2UgY2xhc3M9InN0MCIgY3g9IjgxLjciIGN5PSI3MSIgcng9IjIwLjQiIHJ5PSIyMC4zIi8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTczLjEsOTEuNGgtNDFjLTExLjMsMC0yMC40LTkuMS0yMC40LTIwLjRsMCwwYzAtMTEuMyw5LjEtMjAuNCwyMC40LTIwLjRoNDFjMTEuMywwLDIwLjQsOS4xLDIwLjQsMjAuNGwwLDAKCQlDMTkzLjUsODIuMywxODQuMyw5MS40LDE3My4xLDkxLjR6Ii8+CjwvZz4KPC9zdmc+Cg=='
	));

	acf_add_options_sub_page(array(
		'page_title'    => 'Anagrafica cliente',
		'menu_title'    => 'Anagrafica cliente',
		'parent_slug'   => 'site-settings',
	));

	acf_add_options_sub_page(array(
		'page_title'    => 'Opzioni Blog',
		'menu_title'    => 'Opzioni Blog',
		'parent_slug'   => 'site-settings',
	));

	acf_add_options_sub_page(array(
		'page_title'    => 'Opzioni Storie',
		'menu_title'    => 'Opzioni Storie',
		'parent_slug'   => 'site-settings',
	));

	acf_add_options_sub_page(array(
		'page_title'    => 'Opzioni Header',
		'menu_title'    => 'Opzioni Header',
		'parent_slug'   => 'site-settings',
	));

	acf_add_options_sub_page(array(
		'page_title'    => 'Opzioni Footer',
		'menu_title'    => 'Opzioni Footer',
		'parent_slug'   => 'site-settings',
	));

	acf_add_options_sub_page(array(
		'page_title'    => 'Opzioni Social',
		'menu_title'    => 'Opzioni Social',
		'parent_slug'   => 'site-settings',
	));

	acf_add_options_sub_page(array(
		'page_title'    => 'Opzioni Avanzate',
		'menu_title'    => 'Opzioni Avanzate',
		'parent_slug'   => 'site-settings',
	));
}

// Sucuri Clear Cache
if (get_field('clear_cache', 'option')) {
	function add_custom_menu_link()	{
		$clear_cache = get_field('clear_cache', 'option');
		add_menu_page('cache_clear_link', 'Svuota Cache', 'read', $clear_cache, '', 'dashicons-superhero', 3);
	}
	add_action('admin_menu', 'add_custom_menu_link');
}