<?php
$options = get_option( 'rns_transmissions_options' );
$sep = $options['ewire_separator'];
$boilerplate = $options['ewire_heading_boilerplate'];
$meta = get_post_meta( get_the_ID() );

/* Add 'words' and 'By' if they're missing */
$wordcount = (isset($meta['_rns_transmission_wordcount'][0])) ? $meta['_rns_transmission_wordcount'][0] : false;
if( $wordcount ) {
  if ( false === strpos( $wordcount, 'words' ) ) {
    $wordcount = trim( $wordcount ) . ' words';
  }
}

$byline = (isset($meta['_rns_transmission_byline'][0])) ? $byline = $meta['_rns_transmission_byline'][0] : false;
if ( $byline ) {
  if ( false === stripos( $byline, 'By' ) ) {
    $byline = 'By ' . trim( $byline );
  }
}

$header = '<p>';
$header .= $sep;
$header .= '<br>' . get_the_date( 'l, F d, Y' );
$header .= '<br>' . $sep;
$header .= '<br>' . $boilerplate;
if ( $slug = get_post_meta( get_the_ID(), '_rns_transmission_slug', true ) ) {
  $header .= '<br>' . $sep;
  $header .= '<br>' . $slug;
  $header .= '<br>' . $sep;
}
$header .= '</p>';

$header .= '<p>';
$header .= (isset($meta['_rns_transmission_overline'][0])) ? $meta['_rns_transmission_overline'][0] . '<br>' : '';
$header .= get_the_title();
$header .= $wordcount ? '<br>' . $wordcount : '';
$header .= (isset($meta['_rns_transmission_eds_note'][0])) ? '<br>' . $meta['_rns_transmission_eds_note'][0] : '';
$header .= $byline ? '<br>' . $byline : '';
$header .= (isset($meta['_rns_transmission_copyright'][0])) ?  '<br>' . $meta['_rns_transmission_copyright'][0] : '';
$header .= '</p>';

echo $header;
