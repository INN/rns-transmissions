<?php

/**
* Prepends the Transmission slug to the title
*
* @param object $post A post object
* @return string Text to be used as the subject line
*/
function rns_maybe_include_slug_in_subject( $post ) {
	if ( 'story' === get_post_meta( $post->ID, '_rns_transmission_type', true ) ) {
		$slug = get_post_meta( $post->ID, '_rns_transmission_slug', true );
		$subject = '[' . $slug . '] ' . $post->post_title;
	} else {
		$subject = $post->post_title;
	}
	return $subject;
}

/**
* Takes a post ID and returns that post to the 'Draft' status
*
* @param  int $post_id The post to return to 'Draft'
*/
function rns_return_post_to_draft( $post_id ) {
	$elements = array();
	$elements['ID'] = $post_id;
	$elements['post_status'] = 'draft';
	wp_update_post( $elements );
}

function rns_generic_transmission_error() {
	wp_die( 'Sorry, there was an error. Please go back, save your changes, and try sending again.' );
}

function rns_no_transmission_type_selected_error() {
	wp_die( 'Error: If you select a distribution list, you must also select a Transmission Type.', 'Error', 'back_link=true' );
}

/**
* Disable oEmbeds
*
* Attached to an action hook that must be manually inserted into templates
*
* @link  http://wpengineer.com/2487/disable-oembed-wordpress/
*/
function rns_disable_oembed() {
	remove_filter( 'the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
}
add_action( 'rns_transmissions_before_content', 'rns_disable_oembed' );

/**
* Immediately send a new transmission when the post is published.
*
* Uses the post title for the email subject line and transmission name.
* Uses options defined above for the FromName and FromEmail.
*/
function rns_send_transmission( $post_id, $post ) {
	$options = get_option( 'rns_transmissions_options' );

	/* Bail if this thing isn't on */
	if ( ! $options['is_enabled'] ) {
		return;
	}

	/* Bail if this is not a transmission */
	if ( 'rns_transmission' !== $post->post_type ) {
		return;
	}

	/* Bail if this transmission has already been sent */
	if ( get_post_meta( $post_id, '_rns_transmission_sent', true ) ) {
		return;
	}

	/* Save the recipients post meta just to be sure */
	rns_transmissions_save_meta( $post_id, $post );
	$type = get_post_meta( $post_id, '_rns_transmission_type', true );

	/* Bail if no distribution list is selected */
	if ( ! get_post_meta( $post_id, '_rns_transmission_recipients', false ) ) {
		return;
	}

	/* Final safety checks */
	if ( 'publish' !== $post->post_status ) {
		rns_generic_transmission_error();
	}
	// Assume a type is required if a distribution list is selected
	if ( empty( $type ) ) {
		rns_no_transmission_type_selected_error();
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		rns_generic_transmission_error();
	}
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		rns_generic_transmission_error();
	}
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		rns_generic_transmission_error();
	}
	if ( ! isset( $options['lists_enabled'] ) ) {
		rns_generic_transmission_error();
	}

	$mc_api = mailchimp_tools_get_api_handle();

	if ( ! empty( $mc_api ) ) {
		/* List IDs that we'll send to */
		$recipients = get_post_meta( $post_id, '_rns_transmission_recipients', false );
		$list_id = $options['lists_enabled'][0];
		$group_id = $options['list_groups'][ $list_id ]['default_group'];

		// Grab the list from MC to use its default values for to/from address
		$list = $mc_api->get( 'lists/' . $list_id );

		// Compose campaign options using what's left in $data
		$campaign_recipients = wp_parse_args( $data, array(
			'list_id' => $list_id,
			'generate_text' => true,
		));

		$html = apply_filters( 'the_content', $post->post_content );

		$campaign_content = array(
			'subject_line' => rns_maybe_include_slug_in_subject( $post ),
			'title' => $post->post_title . ' (' . $list['name'] . ')',
			'from_name' => $list['campaign_defaults']['from_name'],
			'reply_to' => $list['campaign_defaults']['from_email'],
		);

		$segment_opts = array(
			'match' => 'any',
			'conditions' => array(
				array(
					'condition_type' => 'Interests',
					'field' => 'interests-' . $group_id,
					'op' => 'interestcontains',
					'value' => $recipients,
				),
			),
		);

		$response = $mc_api->post( 'campaigns', [
			'type' => 'regular',
			'recipients' => [
				'list_id' => $list_id,
				'segment_opts' => $segment_opts,
			],
			'settings' => $campaign_content,
			$segment_opts,
			null
		]);

		if ( isset( $response['status'] ) && 'error' === $response['status'] ) {
			rns_return_post_to_draft( $post_id );
			wp_die( 'Error: ' . wp_kses( $response['error'] ), 'Error', 'back_link=true' );
		} else {
			// Add content to the campaign
			$content_response = $mc_api->put( 'campaigns/' . $response['id'] . '/content', [
				'html' => file_get_contents( get_permalink( $post->ID ) ),
			]);

			$sent = $mc_api->post( 'campaigns/' . $response['id'] . '/actions/send' );
			if ( isset( $sent['status'] ) && 'error' === $sent['status'] ) {
				rns_return_post_to_draft( $post_id );
				wp_die( 'Error: ' . wp_kses( $sent['error'] ), 'Error', 'back_link=true' );
			} else {
				add_post_meta( $post_id, '_rns_transmission_sent', 1 );
			}
		} // End if().
	} // End if().
}
add_action( 'publish_rns_transmission', 'rns_send_transmission', 30, 2 );
