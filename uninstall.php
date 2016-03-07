<?php

// Exist if uninstall is not called from WordPress
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
  exit();

// Delete options from the options table
delete_option( 'rns_transmissions_options' );
delete_option( 'rns_transmissions_lists' );
