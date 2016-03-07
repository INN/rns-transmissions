<?php
/**
 * Be very careful of line breaks and white space
 *
 * The end of PHP blocks serves as a natural line break
 */
$data = $_POST;
$id = $data['post_ID'];
$transmission = get_post( $id );
$meta = get_post_meta( $id );
$extension = $data['rns_fix_for_ap'];
$content_type = $extension == 'docx' ? 'application/msword' : 'text/plain';
$filename = get_post_meta( $id, '_rns_transmission_slug', true ) . '.' . date( 'mdy', strtotime($transmission->post_date));
$filename = strtolower(str_replace(array( ' ', '-'), '', $filename));
header("Content-Type: " . $content_type);
header('Content-Disposition: attachment; filename="' . $filename . '.' . $extension . '"');
header("Content-Type: application/octet-stream");
?>
<?php
  $options = get_option( 'rns_transmissions_options' );
  if ( 'report' == $meta['_rns_transmission_type'][0] ) {
  	$default_ap_headers = implode( "\n", rns_get_daily_report_address() );
  	$default_ap_headers .= "\n\n" . rns_get_daily_report_email();
      $editors = rns_get_daily_report_editors();
      if( $editors && is_array( $editors )){
        foreach( $editors as $editor ){
          $default_ap_headers .= "\n\n" . $editor;
        }
      }
  	$default_ap_headers .= "\n\n" . rns_get_daily_report_copyright( $transmission );
  } else {
  	$default_ap_headers = $options['default_ap_headers'];
  }
  echo apply_filters( 'rns_ap_headers', $default_ap_headers );
?>

<?php
if ( 'report' != $meta['_rns_transmission_type'][0] ) {
	if ( isset( $meta['_rns_transmission_slug'] ) )
	  echo 'slug: ^BC-' . $meta['_rns_transmission_slug'][0] . '<';

	if ( isset( $meta['_rns_transmission_overline'] ) )
	  echo "\n" . '^' . $meta['_rns_transmission_overline'][0] . '<';

	if ( 'story' === $meta['_rns_transmission_type'][0] )
	  echo "\n" . '^' . apply_filters( 'rns_ap_title', $transmission->post_title ) . '<';

	if ( isset( $meta['_rns_transmission_wordcount'] ) )
	  echo "\n" . '^' . $meta['_rns_transmission_wordcount'][0] . '<';

	if ( isset( $meta['_rns_transmission_eds_note'] ) )
	  echo "\n" . '^' . $meta['_rns_transmission_eds_note'][0] . '<';

	if ( isset( $meta['_rns_transmission_categories'] ) )
	  echo "\n" . '^Categories: ' . $meta['_rns_transmission_categories'][0] . '<';

	if ( isset( $meta['_rns_transmission_byline'] ) )
	  echo "\n" . '^' . $meta['_rns_transmission_byline'][0] . '<';

	if ( isset( $meta['_rns_transmission_copyright'] ) )
	  echo "\n" . '^' . $meta['_rns_transmission_copyright'][0] . '<';
}
?>

<?php
$transmission->post_content = apply_filters( 'rns_ap_content', $transmission->post_content );
if ( 'budget' === $meta['_rns_transmission_type'][0] ) :
  $budget_frontmatter = '............................................................' . "\n";
  $budget_frontmatter .= 'RELIGION NEWS SERVICE' . "\n";
  $budget_frontmatter .= $transmission->post_title . "\n";
  $budget_frontmatter .= '............................................................' . "\n\n";
  $budget_frontmatter .= 'Copyright ' . date( 'Y' ) . ' Religion News Service. All rights reserved.' . "\n\n";
  $budget_frontmatter .= 'If you have questions about today\'s stories and photos or need a retransmission of a story, call the RNS News Desk at 202-463-8777.' . "\n";
  echo apply_filters( 'rns_ap_budget_frontmatter', $budget_frontmatter );
  echo "\n";
  echo apply_filters( 'rns_ap_budget_content', $transmission->post_content );
elseif ( 'story' === $meta['_rns_transmission_type'][0] ) :
  echo apply_filters( 'rns_ap_story_content', $transmission->post_content );
elseif ( 'report' === $meta['_rns_transmission_type'][0] ) :
  echo apply_filters( 'rns_ap_report_content', $transmission->post_content );
  echo implode( "\n", rns_get_daily_report_index_header( $transmission ) );
  $connected = rns_get_daily_report_connections( $transmission );
  echo "\n\n" . rns_get_daily_report_being_transmitted( $transmission, $connected );
  while ( $connected->have_posts() ) : $connected->the_post();
  	echo "\n\n";
  	$item = rns_get_daily_report_toc_item();
  	$item = implode( "\n", $item );
  	echo apply_filters( 'rns_ap_report_toc_item', $item );
  endwhile;
  echo "\n\n" . rns_transmissions_thirty();
  wp_reset_query();
  while ( $connected->have_posts() ) : $connected->the_post();
  $eds_note = rns_get_story_eds_note();
  if ( $eds_note ) {
  	echo "\n\n" . $eds_note;
  }
  $title_block = rns_get_daily_report_story_title_block();
  $title_block = implode( "\n", $title_block );
  echo "\n\n" . apply_filters( 'rns_report_story_title_block', $title_block );
  echo "\n\n" . apply_filters( 'rns_ap_report_content', get_the_content() );
  // if ( rns_is_not_last_post_in_report( $connected ) ) {
  	echo "\n\n" . rns_transmissions_thirty();
  // }
  endwhile;
  echo "\n\n" . rns_get_daily_report_copyright( $transmission );
endif;
?>

<?php
if ( isset( $meta['_rns_transmission_end_line'] ) )
  echo "\n" . $meta['_rns_transmission_end_line'][0];
?>
