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
 * @param  int $post_ID The post to return to 'Draft'
 */
function rns_return_post_to_draft( $post_ID ) {
  $elements = array();
  $elements['ID'] = $post_ID;
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
function rns_send_transmission( $post_ID, $post ) {
	$options = get_option( 'rns_transmissions_options' );

	/* Bail if this thing isn't on */
	if ( ! $options['is_enabled'] ) return;

	/* Bail if this is not a transmission */
	if ( $post->post_type != 'rns_transmission' ) return;

	/* Bail if this transmission has already been sent */
	if ( get_post_meta( $post_ID, '_rns_transmission_sent', true ) ) return;

	/* Save the recipients post meta just to be sure */
	rns_transmissions_save_meta( $post_ID, $post );
	$type = get_post_meta( $post_ID, '_rns_transmission_type', true );

	/* Bail if no distribution list is selected */
	if ( ! get_post_meta( $post_ID, '_rns_transmission_recipients', false ) ) return;

	/* Final safety checks */
	if ( 'publish' != $post->post_status ) rns_generic_transmission_error();
	// Assume a type is required if a distribution list is selected
	if ( empty( $type ) ) rns_no_transmission_type_selected_error();
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) rns_generic_transmission_error();
	if ( defined('DOING_AJAX') && DOING_AJAX ) rns_generic_transmission_error();
	if ( defined('DOING_CRON') && DOING_CRON ) rns_generic_transmission_error();
	if ( ! isset( $options['lists_enabled'] ) ) rns_generic_transmission_error();

	$mc_api = mailchimp_tools_get_api_handle();

	if ( ! empty( $mc_api ) ) {
		/* List IDs that we'll send to */
		$recipients = get_post_meta( $post_ID, '_rns_transmission_recipients', false );
		$list_id = $options['lists_enabled'][0];
		$group_id = $options['list_groups'][$list_id]['default_group'];

		// Grab the list from MC to use its default values for to/from address
		$list_results = $mc_api->lists->getList( array(
			'list_id' => $list_id
		) );
		$list = $list_results['data'][0];

		// Compose campaign options using what's left in $data
		$campaign_options = wp_parse_args($data, array(
			'from_email' => $list['default_from_email'],
			'from_name' => $list['default_from_name'],
			'title' => $post->post_title . ' (' . $list['name'] . ')',
			'subject' => rns_maybe_include_slug_in_subject( $post ),
			'list_id' => $list_id
		));

		$html = apply_filters( 'the_content', $post->post_content );

		$campaign_content = array(
			'text' => file_get_contents( get_permalink( $post->ID ) )
		);

		$segment_opts = array(
			'match' => 'any',
			'conditions' => array(
				array(
					'field' => 'interests-' . $group_id,
					'op' => 'one',
					'value' => $recipients
				)
			)
		);

		$response = $mc_api->campaigns->create(
			'plaintext',
			$campaign_options,
			$campaign_content,
			$segment_opts,
			null
		);

		if ( isset( $response['status'] ) && $response['status'] == 'error' ) {
			rns_return_post_to_draft( $post_ID );
			wp_die( 'Error: ' . $response['error'], 'Error', 'back_link=true' );
		} else {
			$sent = $mc_api->campaigns->send( $response['id'] );
			if ( isset( $sent['complete'] ) && $sent['complete'] == true ) {
				add_post_meta( $post_ID, '_rns_transmission_sent', 1 );
			} else if ( isset( $sent['status'] ) && $sent['status'] == 'error' ) {
				rns_return_post_to_draft( $post_ID );
				wp_die( 'Error: ' . $sent['error'], 'Error', 'back_link=true' );
			}
		}
	}
}
add_action( 'publish_rns_transmission', 'rns_send_transmission', 30, 2 );

/**
 * Schedule a new transmission when a post is scheduled.
 */
function rns_schedule_transmission( $post ) {
	/* TODO:
	 * What is this function used for? It was commented out.
	 * Maybe replace Campaign Monitor with MailChimp
	 */

	// Sleep for a wink, just to see whether that helps us ensure the post is live
	sleep( 5 );

	// Bail if this is not a transmission
	if ( 'rns_transmission' != $post->post_type ) {
		return;
	}

	$options = get_option( 'rns_transmissions_options' );

	require_once plugin_dir_path( __FILE__ ) . 'campaignmonitor/csrest_campaigns.php';

	$draft_wrap = new CS_REST_Campaigns(NULL, $options['api_key']);

	$draft_result = $draft_wrap->create( $options['client_id'], array(
		'Subject' => rns_maybe_include_slug_in_subject( $post ),
		'Name' => $post->post_title,
		'FromName' => $options['from_name'],
		'FromEmail' => $options['from_email'],
		'ReplyTo' =>  $options['from_email'],
		'HtmlUrl' => get_permalink( $post->ID ),
		'ListIDs' => array( $options['list_id'] ),
	));

	/**
	 * Schedule the transmission.
	 */
	$send_wrap = new CS_REST_Campaigns($draft_result->response, $options['api_key']);

	$send_result = $send_wrap->send(array(
		'ConfirmationEmail' => $options['from_email'],
		'SendDate' => 'immediately',
	));

}
//add_action( 'future_to_publish', 'rns_schedule_transmission', 10, 1 );

// add_action('admin_head-post.php', 'rns_transmissions_hide_publishing_actions');
// add_action('admin_head-post-new.php', 'rns_transmissions_hide_publishing_actions');
/**
 * Hide publishing actions on the Edit transmission screen based on the post status.
 *
 * To ensure that a post is saved before the user sends it to transmission Monitor,
 * hide the Publish button unless the post is saved with the Pending status.
 * Then, disallow any publishing actions or additional editing on Pending posts
 * except for Publish and Move to Trash.
 *
 * @source http://wordpress.stackexchange.com/questions/36118/
 */
function rns_transmissions_hide_publishing_actions() {
	/* TODO:
	 * What is this used for? It was/is commented out.
	 */
	global $post;

	if( $post->post_type == 'rns_transmission' ) {
		if ( $post->post_status != 'pending') {
			echo '
				<style type="text/css">
				#major-publishing-actions {
				display:none;
		}
		</style>
			';
		}
		if ( $post->post_status == 'pending' ) {
			echo '
				<!--
				possibly hide these:
				#wp-content-editor-tools,
				.mceToolbar,
				-->
				<style type="text/css">
				#minor-publishing-actions,
				.misc-pub-section,
				.p2p-create-connections,
				.p2p-col-delete {
					display: none;
		}
		.curtime {
			display: block;
		}
		.edit-timestamp {
			display: none;
		}
		</style>
			<script type="text/javascript" charset="utf-8">
window.onload = function() {
	document.getElementById("title").disabled = true;
		}
		</script>
	  ';
	  add_action( 'admin_notices', 'rns_transmissions_pending_transmission_admin_notice' );
	}
  }
}
