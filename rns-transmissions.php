<?php
/*
Plugin Name: RNS Transmissions
Plugin URI:
Description: Send emails to subscribers in WordPress
Version: 1.2.2
Author: INN Nerds, David Herrera
Author URI: http://nerds.inn.org
License: GPLv2 or later
License URI:
*/

// Dashboard functions such as the settings page
function rns_transmission_init() {
	if ( is_admin() ){
	  require_once( dirname(__FILE__) . '/includes/admin.php' );
	}

	/**
	 * Mailchimp API and Modal Functions
	 */
	if ( ! function_exists( 'mailchimp_tools_register_for_post_type' ) && file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	}

	require_once( dirname(__FILE__) . '/includes/metaboxes.php' );
	require_once( dirname(__FILE__) . '/includes/actions.php' );
	//require_once( dirname(__FILE__) . '/includes/p2p.php' );
	require_once( dirname(__FILE__) . '/includes/functions.php' );

	// Custom post type
	require_once( dirname(__FILE__) . '/includes/types.php' );
	register_cpt_rns_transmission();
}
add_action( 'init', 'rns_transmission_init' );
