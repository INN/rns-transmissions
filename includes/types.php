<?php

/**
 * Create the Transmissions post type.
 */
function register_cpt_rns_transmission() {

	$labels = array(
		'name' => _x( 'Transmissions', 'rns_transmission' ),
		'singular_name' => _x( 'Transmission', 'rns_transmission' ),
		'add_new' => _x( 'Add New', 'rns_transmission' ),
		'add_new_item' => _x( 'Add New Transmission', 'rns_transmission' ),
		'edit_item' => _x( 'Edit Transmission', 'rns_transmission' ),
		'new_item' => _x( 'New Transmission', 'rns_transmission' ),
		'view_item' => _x( 'View Transmission', 'rns_transmission' ),
		'search_items' => _x( 'Search Transmissions', 'rns_transmission' ),
		'not_found' => _x( 'No transmissions found', 'rns_transmission' ),
		'not_found_in_trash' => _x( 'No transmissions found in Trash', 'rns_transmission' ),
		'parent_item_colon' => _x( 'Parent Transmission:', 'rns_transmission' ),
		'menu_name' => _x( 'Transmissions', 'rns_transmission' ),
	);

	$args = array(
		'labels' => $labels,
		'hierarchical' => false,

		'supports' => array( 'title', 'editor' ),

		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 100,

		'show_in_nav_menus' => true,
		'publicly_queryable' => true,
		'exclude_from_search' => true,
		'has_archive' => false,
		'query_var' => true,
		'can_export' => true,
		'rewrite' => array(
			'slug' => 'transmission',
			'with_front' => true,
			'feeds' => true,
			'pages' => true
		),
		'capability_type' => 'post'
	);

	register_post_type( 'rns_transmission', $args );

	$mc_tools_args = array(
		'preview' => false,
		'editor' => false,
		'settings' => false
	);
	mailchimp_tools_register_for_post_type( 'rns_transmission', $mc_tools_args );
}
