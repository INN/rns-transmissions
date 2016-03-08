<?php
do_action( 'rns_transmissions_before_content' );

while ( have_posts() ) : the_post();

switch ( get_post_meta( get_the_ID(), '_rns_transmission_type', true ) ) {
	case 'story':
		include RNS_TRANSMISSIONS_DIR . '/templates/email-story-header.php';
		the_content();
		break;

	case 'budget':
		include RNS_TRANSMISSIONS_DIR . '/templates/email-budget-header.php';
		the_content();
		break;

	case 'report':
		include RNS_TRANSMISSIONS_DIR . '/templates/email-report-header.php';
		break;
}
endwhile;

get_template_part( 'templates/email-story-footer' );

do_action( 'rns_transmissions_after_content' );
