<?php

add_action( 'p2p_init', 'rns_transmissions_connection_types' );
function rns_transmissions_connection_types() {
  p2p_register_connection_type( array(
    'name' => 'rns_transmissions_daily_report',
    'from' => 'rns_transmission',
    'from_labels' => array( 'create' => 'Select articles to include' ),
    'to' => 'rns_transmission',
    'sortable' => true,
    'title' => array( 'from' => 'Included in reports', 'to' => 'Add Articles To A Daily Report' ),
    'admin_box' => array(
      'show' => 'to',
      'context' => 'normal',
      'can_create_post' => false
    ),
  ));
}
