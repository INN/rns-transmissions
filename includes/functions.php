<?php

function rns_transmissions_init() {
	if ( isset( $_POST['rns_create_transmission'] ) || isset( $_POST['rns_recreate_transmission'] ) ) {
		// if ( RNS_TRANSMISSIONS_DEBUG === true ) wp_die(var_dump($_POST)); // debugging
		if ( isset( $_POST['rns_recreate_transmission'] ) ) {
			$post = $_POST['post_ID'];
			$existing_transmission = get_post_meta( $post, '_rns_transmission_id', true );
			if ( ! $existing_transmission ) {
				wp_die( 'Error: No existing Transmission to delete', 'Error' );
			} else {
				wp_trash_post( $existing_transmission );
			}
		}
		add_filter( 'rns_create_transmission_content', 'rns_alessandro_speciale_honorary_line_spacer' );
		add_filter( 'rns_create_transmission_content', 'rns_add_space_around_headings' );
		add_filter( 'rns_create_transmission_content', 'rns_add_bullets_for_list_items' );
		add_filter( 'rns_create_transmission_content', 'rns_strip_two_spaces' );
		add_filter( 'rns_create_transmission_content', 'rns_strip_tags' );
		add_filter( 'rns_create_transmission_content', 'strip_shortcodes' );
		add_filter( 'rns_create_transmission_content', 'trim' );
		add_filter( 'rns_create_transmission_defaults', 'rns_no_transmission_defaults_for_quotes' );
		rns_create_transmission( $_POST );
	}

	if ( isset( $_POST['rns_fix_for_ap'] ) ) {
		add_filter( 'rns_ap_headers', 'rns_convert_line_break_characters' );

		add_filter( 'rns_ap_title', 'wptexturize' );
		add_filter( 'rns_ap_title', 'trim' );
		add_filter( 'rns_ap_title', 'rns_character_replacement' );

		add_filter( 'rns_ap_budget_frontmatter', 'rns_indent_all_grafs' );

		add_filter( 'rns_ap_content', 'strip_tags' );
		add_filter( 'rns_ap_content', 'rns_convert_line_break_characters' );
		add_filter( 'rns_ap_content', 'wptexturize' );
		add_filter( 'rns_ap_content', 'rns_character_replacement' );
		add_filter( 'rns_ap_content', 'rns_strip_remaining_shortcodes' );

		add_filter( 'rns_ap_budget_content', 'rns_indent_all_grafs' );

		// indenting grafs must happen after converting single to double-
		// spaced grafs, so add the function to the budget and story
		// filters, not the global filter
		add_filter( 'rns_ap_story_content', 'rns_convert_double_to_single_spaced' );
		add_filter( 'rns_ap_story_content', 'rns_indent_all_grafs' );

		add_filter( 'rns_ap_report_toc_item', 'rns_character_replacement' );
		add_filter( 'rns_report_story_title_block', 'rns_character_replacement' );
		add_filter( 'rns_ap_content', 'strip_tags' );
		add_filter( 'rns_ap_report_content', 'rns_convert_line_break_characters' );
		add_filter( 'rns_ap_report_content', 'strip_tags' );
		add_filter( 'rns_ap_report_content', 'rns_convert_quadruple_to_double_spaced' );

		include( 'fixforap.php' );
		die;
	}
}
add_action( 'admin_init', 'rns_transmissions_init' );

function rns_create_transmission( $data ) {
	remove_action( 'save_post', 'rcp_save_meta_data' );
	$args = array(
		'post_type' => 'rns_transmission',
		'post_title' => $data['post_title'],
		'post_content' => apply_filters( 'rns_create_transmission_content', $data['content'] ),
	);
	// if ( RNS_TRANSMISSIONS_DEBUG === true ) wp_die(var_dump($args)); // debugging
	$transmission = wp_insert_post( $args );
	update_post_meta( $data['post_ID'], '_rns_transmission_id', $transmission );
	update_post_meta( $data['post_ID'], '_rns_transmission_show_notice', '1' );

	$defaults = apply_filters( 'rns_create_transmission_defaults', array(
		'type' => 'story',
		'slug' => 'RNS-SLUG-SLUG',
		'byline' => rns_get_byline_from_coauthors( $data['coauthors'] ),
		'overline' => 'NEWS STORY',
		'wordcount' => rns_get_word_count_estimate( $args['post_content'] ),
		'eds_note' => 'Eds: There IS/ARE XXXX PHOTO/PHOTOS available for WEB-ONLY/WEB AND PRINT at the time of publication. Please refer to this story on religionnews.com for those and any additional photos.',
		'categories' => 'c, l',
		'copyright' => 'c. ' . date( 'Y' ) . ' Religion News Service',
		'end_line' => 'XXX/XXX END XXXXXX',
	));

	if ( $defaults ) {
		foreach ( $defaults as $key => $value ) {
			update_post_meta( $transmission, '_rns_transmission_' . $key, $value );
		}
	}

	if ( ! $data['rns_create_transmission_skip_alert'] || '1' != $data['rns_create_transmission_skip_alert'] )
		rns_alert_distributors( $transmission );
}

/**
 * Shows the 'Transmission created' notice
 *
 * Shows the notice only if a meta field exists, then deletes that
 * meta field to prevent showing the notice multiple times
 */
function rns_transmission_successfully_created() {
	global $post;
	if ( get_post_meta( $post->ID, '_rns_transmission_show_notice', true ) ) {
		$url = get_edit_post_link( get_post_meta( $post->ID, '_rns_transmission_id', true ) );
		echo "<div class='updated'><p>Transmission created. <a href='{$url}'>Edit Transmission</a></p></div>";
		delete_post_meta( $post->ID, '_rns_transmission_show_notice', true );
	}
}
add_action( 'admin_notices', 'rns_transmission_successfully_created' );

/**
 * Creates QOTD Transmissions without custom field content
 *
 * @return false|array False if the Post is a Quote, the original $args if it isn't
 */
function rns_no_transmission_defaults_for_quotes( $args ) {
	$post = get_post( $_POST['post_ID'] );
	if ( has_post_format( 'quote', $post ) )
		return false;

	return $args;
}

function rns_get_byline_from_coauthors( $coauthors ) {
	foreach ($coauthors as $login) {
		$userdata = WP_User::get_data_by( 'login', $login );
		// Assume that byline names must be uppercase
		$names[] = strtoupper( $userdata->display_name );
	}
	// http://stackoverflow.com/a/8586179
	$byline = 'By ' . join(' and ', array_filter(array_merge(array(join(', ', array_slice($names, 0, -1))), array_slice($names, -1))));
	return $byline;
}

function rns_get_word_count_estimate( $content ) {
	$sizeof_content = sizeof(explode(' ', $content));
	if ( $sizeof_content % 25 === 0 ) {
		return $sizeof_content . ' words';
	} else {
		$remainder = $sizeof_content % 25;
		$adjustment = $remainder < 13 ? -$remainder : 25 - $remainder;
		$sizeof_content += $adjustment;
		return $sizeof_content . ' words';
	}
}

function rns_convert_line_break_characters( $content ) {
	$content = str_replace( "\r\n", "\n", $content );
	$content = str_replace( "\r", "\n", $content );
	return $content;
}

function rns_convert_quadruple_to_double_spaced( $content ) {
	$content = str_replace( "\n\n\n\n", "\n\n", $content );
	$content = str_replace( "\n\n\n", "\n\n", $content );
	return $content;
}

function rns_convert_double_to_single_spaced( $content ) {
	// $content = str_replace(array("\n", "\r"), array('\n', '\r'), $content);
	$content = str_replace( "\n\n\n\n", "\n", $content );
	$content = str_replace( "\n\n", "\n", $content );
	return $content;
}

function rns_indent_all_grafs( $content ) {
	$exploded = explode( "\n", $content );
	foreach ($exploded as $key => $value) {
		$exploded[$key] = '    ' . $value;
	}
	$content = implode( "\n", $exploded );
	return $content;
}

function rns_indent_all_but_first_graf( $content ) {
	$exploded = explode( "\n", $content );
	foreach ($exploded as $key => $value) {
		if ( 0 !== $key ) $exploded[$key] = '    ' . $value;
	}
	$content = implode( "\n", $exploded );
	return $content;
}

/**
 * @link  http://stackoverflow.com/a/7923444 Fix for non-breaking spaces
 */
function rns_character_replacement( $content ) {
	$find = array( '&#8220;', '&#8221;', '&#8216;', '&#8217;', '&#8212;', '&amp;', '–', '“', '”', "’", '&#8211;', '—', "‘", '…', '&#8230;', "\xc2\xa0" );
	$replace = array( '``', "''", '`', "'", '_', '&', '_', '``', "''", "'", '_', '_', "'", '...', '.', ' ' );
	$content = str_replace( $find, $replace, $content );
	return $content;
}

function rns_alessandro_speciale_honorary_line_spacer( $content ) {
	$content = str_replace( '<p lang="en-US" style="text-align: left;" align="JUSTIFY">', "\n<p>", $content );
	$content = str_replace( '<p lang="en-US" align="JUSTIFY">', "\n<p>", $content );
	$content = str_replace( '<p style="text-align: left;" align="JUSTIFY">', "\n<p>", $content );
	$content = str_replace( '<p lang="en-US">', "\n<p>", $content );
	$content = str_replace( '<p style="text-align: left;">', "\n<p>", $content );
	$content = str_replace( '<p style="text-align: justify;">', "\n<p>", $content );
	return $content;
}

function rns_add_space_around_headings( $content ) {
	$headers = array( '<h1>', '<h2>', '<h3>', '<h4>', '<h5>', '<h6>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>' );
	$content = str_replace( $headers, "\n", $content );
	return $content;
}

function rns_add_bullets_for_list_items( $content ) {
	$content = str_replace( '<li>', '* ', $content );
	return $content;
}

function rns_strip_two_spaces( $content ) {
	$content = str_replace( '  ', ' ', $content );
	return $content;
}

function rns_strip_tags( $content ) {
	$content = strip_tags( $content, '<strong><em>' );
	return $content;
}

/**
 * Return the street address that goes on top of daily reports
 *
 * Formatted as an array so it can be exploded into paragraph or line break-ready forms
 *
 * @return array
 */
function rns_get_daily_report_address() {
	return array(
		'RELIGION NEWS SERVICE',
		'529 14th Street NW, Suite 1009',
		'Washington, DC  20045',
		'(202) 463-8777 - Voice'
	);
}

/**
 * Return the general email address that goes on top of daily reports
 *
 * @return string
 */
function rns_get_daily_report_email() {
	return 'E-mail: info@religionnews.com';
}

/**
 * Return the name of the editors that are displayed on top of daily reports
 *
 * @return array
 */
function rns_get_daily_report_editors() {
	return array(
		'Jerome Socolovsky, Editor-in-Chief',
	);
}

/**
 * Return the copyright string that goes on top of daily reports
 *
 * If a post object is passed, include a year with the copyright. Otherwise,
 * just say "Copyright Religion News Service"
 *
 * @param  WP_Post $post Post whose date should be used for the copyright year
 * @return string
 */
function rns_get_daily_report_copyright( $post = null ) {
	$copyright = "Copyright ";
	if ( $post ) {
		$copyright .= mysql2date( 'Y', $post->post_date ) . " ";
	}
	$copyright .= "Religion News Service. All rights reserved. No part of this transmission may be reproduced without written permission.";
	return $copyright;
}

/**
 * Get the "Index of" header that goes above the daily report list of contents
 *
 * Formatted as an array so it can be exploded into paragraph or line
 * break-ready forms. If a post object is passed, include the date in the array.
 *
 * @param  WP_Post $post Post object to use for a date
 * @return array
 */
function rns_get_daily_report_index_header( $post = null ) {
	$header = array(
		'Index of Daily Report'
	);

	if ( $post ) {
		$header[] = mysql2date( 'l, F j, Y', $post->post_date );
	}

	return $header;
}

/**
 * Query for posts connected to a daily report
 *
 * @param  WP_Post $post The post object of the daily report
 * @return WP_Query|bool
 */
function rns_get_daily_report_connections( $post ) {
	$connected = new WP_Query( array(
		'connected_type' => 'rns_transmissions_daily_report',
		'connected_items' => $post,
		'connected_direction' => 'to',
		'connected_query' => array( 'post_status' => 'any' ),
		'nopaging' => true,
	) );
	return $connected;
}

/**
 * Print the number of articles being included in a daily report
 *
 * Subtracts the advisory, calendar, and quote of the day, which are included
 * in the connected posts, from the number of stories
 *
 * @uses convert_number_to_words() To turn number of stories from an integer to a word
 *
 * @param  WP_Object $post      The post object of the daily report
 * @param  WP_Query $connected  The object returned by the query for posts connected to the daily report
 * @return string
 */
function rns_get_daily_report_being_transmitted( $post, $connected ) {
	$daily_report_includes = get_post_meta( $post->ID, '_rns_transmission_daily_report_includes', false );
	$has_advisory = in_array( 'advisory', $daily_report_includes );
	$has_calendar = in_array( 'calendar', $daily_report_includes );
	if ( $has_advisory && $has_calendar ) {
		$and_advisory_or_calendar = ', the RNS Coverage Advisory and the updated Religion Calendar';
	} elseif ( $has_advisory ) {
		$and_advisory_or_calendar = ' and the RNS Coverage Advisory';
	} elseif ( $has_calendar ) {
		$and_advisory_or_calendar = ' and the updated Religion Calendar';
	} else {
		$and_advisory_or_calendar = '';
	}

	/**
	 * For each non-article included in a Daily Report, subtract 1
	 * from the number of articles 'being transmitted'
	 */
	$number_of_articles = $connected->post_count;
	foreach ($daily_report_includes as $key) {
		$number_of_articles--;
	}

	$article_or_articles = $number_of_articles === 1 ? ' article' : ' articles';

	$number_of_articles = ucfirst( convert_number_to_words( $number_of_articles ) );

	if ( 'Zero' !== $number_of_articles ) {
		$being_transmitted = $number_of_articles . $article_or_articles . $and_advisory_or_calendar . ' are being transmitted today:';
		$being_transmitted = str_replace( 'article are', 'article is', $being_transmitted );
		return $being_transmitted;
	}
}

/**
 * Get the name and byline of a story for use in the daily report index
 *
 * Must be used in the loop. Returns an array for imploding with necessary
 * formatting, such as with wpautop() or "\n"
 *
 * @return array
 */
function rns_get_daily_report_toc_item() {
	$item = array(
		get_the_title()
	);

	if ( $byline = get_post_meta( get_the_ID(), '_rns_transmission_byline', true ) ) {
		$item[] = $byline;
	}

	return $item;
}

/**
 * Return a '== 30 ==' line
 *
 * @return string
 */
function rns_transmissions_thirty() {
	return '== 30 ==';
}

/**
 * Return the "'Eds:' note" meta field
 *
 * Must be used in the loop
 *
 * @return string
 */
function rns_get_story_eds_note() {
	return get_post_meta( get_the_ID(), '_rns_transmission_eds_note', true );
}

/**
 * Return the title and metadata that go above a story in a daily report
 *
 * Must be used in the loop
 *
 * @return array
 */
function rns_get_daily_report_story_title_block() {
	$meta = get_post_meta( get_the_ID() );
	$title = get_the_title();
	$words = $meta['_rns_transmission_wordcount'];
	$byline = $meta['_rns_transmission_byline'];
	$copyright = $meta['_rns_transmission_copyright'];

	$header = array(
		get_the_title()
	);

	if ( $words[0] ) {
		$header[] = $words[0];
	}

	if ( $byline[0] ) {
		$header[] = $byline[0];
	}

	if ( $copyright[0] ) {
		$header[] = $copyright[0];
	}

	return $header;
}

/**
 * Check whether the current post is not the last in the loop
 *
 * @param  WP_Query $wp_query Query being looped
 * @return bool
 */
function rns_is_not_last_post_in_report( $wp_query ) {
	$current_post = ( intval( $wp_query->current_post ) + 1 );
	$post_count = intval( $wp_query->post_count );
	return $current_post !== $post_count;
}

/**
 * Return the string with the <unsubscribe> tag required by Campaign Monitor
 *
 * @return string
 */
function rns_transmissions_email_footer() {
	return "To change the email addresses in your newsroom that receive transmissions from RNS, please email " . antispambot( 'ron.ribiat@religionnews.com' ) . ". To stop receiving this type of transmission at this email address without adding a replacement, <unsubscribe>click here</unsubscribe>.";
}

/**
 * Remove opening and closing shortcodes while retaining their content
 *
 * @link  http://wordpress.org/support/topic/stripping-shortcodes-keeping-the-content#post-2977834
 * @param string $content
 * @return string
 */
function rns_strip_remaining_shortcodes( $content ) {
	return preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', $content);
}

/**
 * Get email addresses to alert to a new Transmissions
 *
 * Gets the distributors from Edit Flow by default, but is filterable
 *
 * @param object $post WP_Post object of the new Transmission
 * @return array|bool Email addresses or false
 */
function rns_get_transmission_alert_recipients( $post ) {
	$addresses = array();

	$distributors = rns_transmissions_get_distributor_emails();
	$addresses = apply_filters( 'rns_transmission_alert_recipients', $distributors );
	// HACK: Hardcode the emails until we find correct hook
	$addresses[] = 'ron.ribiat@religionnews.com';
	$addresses[] = 'russell.fair@religionnews.com';
	$addresses[] = 'rfair404+testrnstransmission@gmail.com';
	return $addresses;

	if ( ! $addresses )
		return false;

	return $addresses;
}

/**
 * Get the email addresses of users in the Distributors usergroup
 *
 * @return array|bool Email addresses or false
 */
function rns_transmissions_get_distributor_emails() {
	global $edit_flow;

	if ( ! $edit_flow )
		return false;

	$usergroup = $edit_flow->user_groups->get_usergroup_by( 'slug', 'distributors' );
	$distributors = $usergroup->user_ids;

	if ( ! $distributors )
		return false;

	foreach ( $distributors as $distributor ) {
		$user = get_user_by( 'id', $distributor );
		$email = $user->user_email;
		$emails[] = $email;
	}

	return $emails;
}

/**
 * Create the subject line of the Transmission alert
 *
 * @param obj $post WP_Post object of the new Transmission
 * @return string The subject line
 */
function rns_get_transmission_alert_subject( $post ) {
	if ( ! is_object( $post ) )
		return false;

	$blogname = get_option( 'blogname' );
	$post_type = 'Transmission';
	$post_title = $post->post_title;
	$subject = sprintf( '[%1$s] New %2$s Created: "%3$s"', $blogname, $post_type, $post_title );

	return apply_filters( 'rns_transmission_alert_subject', $subject );
}

/**
 * Create the body of the Transmission alert
 *
 * @see  EF_Notifications::notification_status_change()
 *
 * @param obj $post WP_Post object of the new Transmission
 * @return string|bool Alert message or false
 */
function rns_get_transmission_alert_message( $post ) {
	if ( ! is_object( $post ) )
		return false;

	$post_id = $post->ID;
	$post_type = 'Transmission';
	$post_title = $post->post_title;

	$body = sprintf( 'A new %1$s (#%2$s "%3$s") was created by %4$s %5$s %6$s', $post_type, $post_id, $post_title, date_i18n( get_option( 'date_format' ) ), date_i18n( get_option( 'time_format' ) ), get_option( 'timezone_string' ) ) . "\r\n";

	$body .= "--------------------\r\n\r\n";

	$body .= sprintf( '== %s Details ==', $post_type ) . "\r\n";
	$body .= sprintf( 'Title: %s', $post_title ) . "\r\n";

	$edit_link = get_option( 'siteurl' ) . "/wp-admin/post.php?post={$post_id}&action=edit";
	$body .= "\r\n";
	$body .= '== Actions ==' . "\r\n";

	$body .= sprintf( 'Edit: %s', $edit_link ) . "\r\n";

	return $body;
}

/**
 * Send an alert that a Transmission was Created
 *
 * @param int $transmission The ID of the new Transmission
 * @return bool Success or failure
 */
function rns_alert_distributors( $transmission ) {
	$post = get_post( $transmission );
	if ( ! $post )
		return false;

	$ignored_statuses = array( 'inherit', 'auto-draft' );
	if ( in_array( $post->post_status, $ignored_statuses ) )
		return false;

	$to = rns_get_transmission_alert_recipients( $post );
	$subject = rns_get_transmission_alert_subject( $post );
	$message = rns_get_transmission_alert_message( $post );
	if ( ! $to || ! $subject || ! $message )
		return false;

	// Comply with PHP mail()
	$to = implode( ', ', $to );

	mail( $to, $subject, $message );
}

/**
 * Convert a number to its textual form
 *
 * @param  int $number The number to convert
 * @return string The number as a string
 * @see http://www.karlrixon.co.uk/writing/convert-numbers-to-words-with-php/
 */
function convert_number_to_words($number) {

	$hyphen      = '-';
	$conjunction = ' and ';
	$separator   = ', ';
	$negative    = 'negative ';
	$decimal     = ' point ';
	$dictionary  = array(
		0                   => 'zero',
		1                   => 'one',
		2                   => 'two',
		3                   => 'three',
		4                   => 'four',
		5                   => 'five',
		6                   => 'six',
		7                   => 'seven',
		8                   => 'eight',
		9                   => 'nine',
		10                  => 'ten',
		11                  => 'eleven',
		12                  => 'twelve',
		13                  => 'thirteen',
		14                  => 'fourteen',
		15                  => 'fifteen',
		16                  => 'sixteen',
		17                  => 'seventeen',
		18                  => 'eighteen',
		19                  => 'nineteen',
		20                  => 'twenty',
		30                  => 'thirty',
		40                  => 'fourty',
		50                  => 'fifty',
		60                  => 'sixty',
		70                  => 'seventy',
		80                  => 'eighty',
		90                  => 'ninety',
		100                 => 'hundred',
		1000                => 'thousand',
		1000000             => 'million',
		1000000000          => 'billion',
		1000000000000       => 'trillion',
		1000000000000000    => 'quadrillion',
		1000000000000000000 => 'quintillion'
	);

	if (!is_numeric($number)) {
		return false;
	}

	if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
		// overflow
		trigger_error(
			'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
			E_USER_WARNING
		);
		return false;
	}

	if ($number < 0) {
		return $negative . convert_number_to_words(abs($number));
	}

	$string = $fraction = null;

	if (strpos($number, '.') !== false) {
		list($number, $fraction) = explode('.', $number);
	}

	switch (true) {
	case $number < 21:
		$string = $dictionary[$number];
		break;
	case $number < 100:
		$tens   = ((int) ($number / 10)) * 10;
		$units  = $number % 10;
		$string = $dictionary[$tens];
		if ($units) {
			$string .= $hyphen . $dictionary[$units];
		}
		break;
	case $number < 1000:
		$hundreds  = $number / 100;
		$remainder = $number % 100;
		$string = $dictionary[$hundreds] . ' ' . $dictionary[100];
		if ($remainder) {
			$string .= $conjunction . convert_number_to_words($remainder);
		}
		break;
	default:
		$baseUnit = pow(1000, floor(log($number, 1000)));
		$numBaseUnits = (int) ($number / $baseUnit);
		$remainder = $number % $baseUnit;
		$string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
		if ($remainder) {
			$string .= $remainder < 100 ? $conjunction : $separator;
			$string .= convert_number_to_words($remainder);
		}
		break;
	}

	if (null !== $fraction && is_numeric($fraction)) {
		$string .= $decimal;
		$words = array();
		foreach (str_split((string) $fraction) as $number) {
			$words[] = $dictionary[$number];
		}
		$string .= implode(' ', $words);
	}

	return $string;
}
