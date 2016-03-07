<?php

/**
 * @since 1.1.0
 */
function rns_transmission_tag_init() {
	register_taxonomy( 'rns_transmission_tag', array( 'rns_transmission' ), array(
		'hierarchical'      => false,
		'public'            => true,
		'show_in_nav_menus' => false,
		'show_ui'           => true,
		'show_admin_column' => false,
		'query_var'         => true,
		'rewrite'           => true,
		'capabilities'      => array(
			'manage_terms'  => 'edit_posts',
			'edit_terms'    => 'edit_posts',
			'delete_terms'  => 'edit_posts',
			'assign_terms'  => 'edit_posts'
		),
		'labels'            => array(
			'name'                       => __( 'Transmission Tags', 'rns-transmissions' ),
			'singular_name'              => _x( 'Transmission Tag', 'taxonomy general name', 'rns-transmissions' ),
			'search_items'               => __( 'Search Transmission Tags', 'rns-transmissions' ),
			'popular_items'              => __( 'Popular Transmission Tags', 'rns-transmissions' ),
			'all_items'                  => __( 'All Transmission Tags', 'rns-transmissions' ),
			'parent_item'                => __( 'Parent Transmission Tags', 'rns-transmissions' ),
			'parent_item_colon'          => __( 'Parent Transmission Tags:', 'rns-transmissions' ),
			'edit_item'                  => __( 'Edit Transmission Tag', 'rns-transmissions' ),
			'update_item'                => __( 'Update Transmission Tag', 'rns-transmissions' ),
			'add_new_item'               => __( 'New Transmission Tag', 'rns-transmissions' ),
			'new_item_name'              => __( 'New Transmission Tag', 'rns-transmissions' ),
			'separate_items_with_commas' => __( 'Transmission Tags separated by comma', 'rns-transmissions' ),
			'add_or_remove_items'        => __( 'Add or remove Transmission Tags', 'rns-transmissions' ),
			'choose_from_most_used'      => __( 'Choose from the most used Transmission Tags', 'rns-transmissions' ),
			'menu_name'                  => __( 'Transmission Tags', 'rns-transmissions' ),
		),
	) );

}
add_action( 'init', 'rns_transmission_tag_init' );
