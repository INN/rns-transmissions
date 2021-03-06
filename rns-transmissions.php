<?php
/*
Plugin Name: RNS Transmissions
Plugin URI:
Description: Send emails to subscribers in WordPress
Version: 1.3
Author: INN Nerds, David Herrera
Author URI: http://nerds.inn.org
License: GPLv2 or later
License URI:
*/

/**
 * MailChimp API and Modal Functions
 */
if ( ! class_exists( 'MailChimp' ) && file_exists( __DIR__ . '/wordpress-mailchimp-tools/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/wordpress-mailchimp-tools/vendor/autoload.php';
}

// Dashboard functions such as the settings page
function rns_transmission_init() {
	define( 'RNS_TRANSMISSIONS_DIR', __DIR__ );

	if ( is_admin() ){
	  require_once( __DIR__ . '/includes/admin.php' );
	}

	require_once( __DIR__ . '/includes/metaboxes.php' );
	require_once( __DIR__ . '/includes/actions.php' );
	require_once( __DIR__ . '/includes/functions.php' );

	// Custom post type
	require_once( __DIR__ . '/includes/types.php' );
	register_cpt_rns_transmission();

	add_filter( 'single_template', 'rns_override_transmission_template' );

	if ( class_exists( 'WP_CLI_Command' ) ) {
		require __DIR__ . '/includes/cli.php';
		WP_CLI::add_command( 'transmissions', 'Transmissions_WP_CLI_Command' );
	}
}
add_action( 'init', 'rns_transmission_init' );
