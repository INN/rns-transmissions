<?php
global $post;

// Boilerplate and frontmatter

$address = rns_get_daily_report_address();
echo wpautop( implode( "\n", $address ) );

printf( '<p>%s</p>', rns_get_daily_report_email() );

$editors = rns_get_daily_report_editors();
if($editors && is_array($editors)){
	foreach ( $editors as $editor ){
		printf('<p>%s</p>', $editor);
	}
}

printf( '<p>%s</p>', rns_get_daily_report_copyright( $post ) );

// Post content
the_content();

// Query for connections
$connected = rns_get_daily_report_connections( get_queried_object() );
if ( $connected && $connected->have_posts() ) :

	// Index
	$index_header = rns_get_daily_report_index_header( $post );
	echo wpautop( implode( "\n", $index_header ) );

	printf( '<p>%s</p>', rns_get_daily_report_being_transmitted( $post, $connected ) );

	$toc = '';

	while ( $connected->have_posts() ) : $connected->the_post();
		$item = rns_get_daily_report_toc_item();
		$toc .= wpautop( implode( "\n", $item ) );
	endwhile;

	$toc .= sprintf( '<p>%s</p>', rns_transmissions_thirty() );

	echo $toc;

	wp_reset_query();

	// Loop of connected posts
	while( $connected->have_posts() ) : $connected->the_post();

	$eds_note = rns_get_story_eds_note();
	if ( $eds_note ) {
		printf( '<p>%s</p>', $eds_note );
	}

	$title_block = rns_get_daily_report_story_title_block();
	$title_block = implode( "\n", $title_block );
	echo wpautop( $title_block );

	the_content();

	if ( rns_is_not_last_post_in_report( $connected ) ) {
		printf( '<p>%s</p>', rns_transmissions_thirty() );
	}

	endwhile;

	wp_reset_postdata();

endif;
?>
