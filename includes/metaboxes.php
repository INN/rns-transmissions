<?php
/**
 * This code for displaying and saving the recipients box
 * owes a massive debt to Justin Tadlock's Members plugin.
 */

include_once __DIR__ . '/connections.php';

add_action( 'add_meta_boxes', 'rns_transmissions_metabox_create' );
add_action( 'save_post', 'rns_transmissions_save_meta', 10, 2 );

/**
 * Register metaboxes
 */
function rns_transmissions_metabox_create() {
	add_meta_box(
		'rns-transmissions-lists',
		'Select recipients',
		'rns_transmissions_recipients_metabox',
		'rns_transmission',
		'side',
		'high'
	);
	add_meta_box(
		'rns-transmissions-create-from-post',
		'Transmissions',
		'rns_transmissions_post_page_metabox',
		'post',
		'side',
		'default'
	);
	add_meta_box(
		'rns-transmissions-create-ap-file',
		'Download For Distribution',
		'rns_transmissions_fix_for_ap_metabox',
		'rns_transmission',
		'side',
		'default'
	);
	add_meta_box(
		'rns-connections',
		'Add Articles To A Daily Report',
		'rns_add_connections_meta_box_callback',
		'rns_transmission'
	);
}

/**
 * Create the metabox for choosing Transmission recipients
 */
function rns_transmissions_recipients_metabox( $post ) {
	$available_groups = get_option( 'rns_transmissions_lists_groups' );

	$options = get_option( 'rns_transmissions_options' );

	if ( ! isset( $options['lists_enabled'] ) ) {
		echo '<p><a href="' . admin_url('edit.php?post_type=rns_transmission&page=rns_transmissions') . '">No recipients available.</a></p>';
		echo '<p><a href="' . admin_url('edit.php?post_type=rns_transmission&page=rns_transmissions') . '">Configure Trasnmissions list and group.</a></p>';
		return;
	}

	$list_id = $options['lists_enabled'][0];
	$group_id = $options['list_groups'][$list_id]['default_group'];

	$groups = array();
	foreach ( $available_groups[$list_id] as $available_group ) {
		if ( $available_group['id'] == $group_id )
			$groups = $available_group['groups'];
	}

	/* Get the recipients saved for the post */
	$recipients = get_post_meta( $post->ID, '_rns_transmission_recipients', false ); ?>
	<input type="hidden" name="rns_transmissions_meta_nonce" value="<?php echo wp_create_nonce( plugin_basename( __FILE__ ) ); ?>" />

<?php /* Construct the HTML for the checkboxes */
	foreach ( $groups as $group ) {
		$checked = '';

		/* If the list has been selected, make sure it's checked */
		if ( is_array( $recipients ) && in_array( $group['bit'], $recipients ) )
			$checked = ' checked="checked" '; ?>

		<label for="rns_transmission_recipients[<?php echo $group['name']; ?>]">
			<input <?php echo $checked; ?>
				type="checkbox"
				name="rns_transmission_recipients[<?php echo $group['name']; ?>]"
				id="rns_transmission_recipients[<?php echo $group['name']; ?>]"
				value="<?php echo $group['bit']; ?>" /><?php echo $group['name']; ?><br>
		</label>
<?php }
	if ( current_user_can( 'manage_plugins' ) ) {
?>
  <p><em><?php _e( "Update the list of recipients by visiting the <a href='" . admin_url() . "options-general.php?page=rns_transmissions'>plugin options page</a>", 'rns_transmissions' ); ?></em></p>
<?php
	}
}

function rns_transmissions_post_page_metabox( $post ) {
  if ( $transmission = get_post_meta( $post->ID, '_rns_transmission_id', true ) ) {
    $url = get_edit_post_link( $transmission );
    echo "<p>Transmission created. <a href='{$url}'>Edit Transmission</a></p>";
    echo '<p><br /><input type="submit" id="rns_recreate_transmission" name="rns_recreate_transmission" value="Recreate Transmission" class="button"></p>';
    echo '<span style="color: #c00;">Warning! Existing Transmission will be deleted</span>';
    ?>
    <?php
  } else {
    echo '<p><input type="submit" id="rns_create_transmission" name="rns_create_transmission" value="Create Transmission" class="button"></p>';
  }
  echo '<p><input type="checkbox" id="rns_create_transmission_skip_alert" name="rns_create_transmission_skip_alert" value="1" /> Don\'t alert distributors yet</p>';
}

function rns_transmissions_fix_for_ap_metabox() {
	echo '
		<form action="post.php">
		<input type="submit" name="save" id="" value="Save Changes" class="button">
		<ul>
			<li>
			<button type="submit" id="rns_fix_for_ap_txt" name="rns_fix_for_ap" value="txt" class="button">Download as text file</button>
			</li>
		</ul>
		</form>';
}

/**
 * Save metabox data
 */
function rns_transmissions_save_meta( $post_id, $post ) {
	$available_lists = get_option( 'rns_transmissions_lists' );

	/* Verify the nonce */
	if ( ! isset( $_POST['rns_transmissions_meta_nonce'] ) || ! wp_verify_nonce( $_POST['rns_transmissions_meta_nonce'], plugin_basename( __FILE__ ) ) )
		return false;

	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
	if ( defined('DOING_AJAX') && DOING_AJAX ) return;
	if ( defined('DOING_CRON') && DOING_CRON ) return;

	/* Get the post type object */
	$post_type = get_post_type_object( $post->post_type );

	/* Confirm that the current user has permission to edit the post */
	if ( ! current_user_can( $post_type->cap->edit_post, $post_ID ) )
		return $post_id;

	/* Don't save if the post is only a revision */
	if ( 'revision' == $post->post_type )
		return;

	/* Recipients list */
	$meta_values = get_post_meta( $post_id, '_rns_transmission_recipients', false );

	if ( isset( $_POST['rns_transmission_recipients'] ) && is_array( $_POST['rns_transmission_recipients'] ) ) {

		foreach ( $_POST['rns_transmission_recipients'] as $recipient ) {
			if ( ! in_array( $recipient, $meta_values ) )
				add_post_meta( $post_id, '_rns_transmission_recipients', $recipient, false );
		}

		foreach ( $available_lists as $name => $ID ) {
			if ( ! in_array( $ID, $_POST['rns_transmission_recipients'] ) && in_array( $ID, $meta_values ) )
				delete_post_meta( $post_id, '_rns_transmission_recipients', $ID );
		}
	}

	/* "About this Transmission" metabox */
	$about_this_transmission = rns_transmissions_info_metabox();
	$fields = $about_this_transmission[0]['fields'];

	foreach ( $fields as $key => $field ) {
		if ( isset( $_POST[$field['id']] ) ) {
			update_post_meta( $post_id, $field['id'], $_POST[$field['id']] );
		}
	}
}

/**
 * Add the "About this Transmission" meta box to the post edit page
 */
function rns_transmissions_add_metaboxes() {
	$meta_boxes = rns_transmissions_info_metabox();
	$current_screen = get_current_screen();

	foreach ($meta_boxes as $key => $meta_box) {
		add_meta_box(
			$meta_box['id'],
			$meta_box['title'],
			'rns_transmissions_render_info_metabox',
			'rns_transmission',
			$meta_box['context'],
			$meta_box['priority'],
			$meta_box['fields']
		);
	}
}
add_action( 'add_meta_boxes', 'rns_transmissions_add_metaboxes' );

/**
 * Render metabox fields
 */
function rns_transmissions_render_info_metabox($post, $args) {
	$fields = $args['args'];
	$custom_fields = get_post_custom();

	foreach ( $fields as $key => $field ) {
		$field_value = get_post_meta( $post->ID, $field['id'], true );

		if ( $field['type'] == 'text' ) {
			$input_tmpl = '<div class="form-group"><label class="for-group" for="%1$s">%2$s</label><input type="text" name="%1$s" id="%1$s" value="%3$s" /></div>';
			$val = ( ! empty( $field_value ) ) ? $field_value : $field['value'];
			$input = sprintf( $input_tmpl, $field['id'], $field['name'], $val );
			print $input;
		} else if ( $field['type'] == 'radio' ) {
			printf( '<div class="form-group"><label class="for-group" for="%1$s">%2$s</label>', $field['id'], $field['name'] );

			foreach ( $field['options'] as $idx => $option ) {
				$option_tmpl = '<label class="for-option" for="%1$s"><input type="radio" name="%1$s" id="%1$s-type%4$s" value="%3$s" %5$s> %2$s</label>';
				$option = sprintf(
					$option_tmpl, $field['id'], $option['name'], $option['value'], $idx,
					checked( $field_value, $option['value'], false )
				);
				print $option;
			}
			print '</div>';
		} else if ( $field['type'] == 'multicheck' ) {

			printf( '<div class="form-group"><label class="for-group" for="%1$s">%2$s</label>', $field['id'], $field['name'] );

			foreach ( $field['options'] as $value => $label ) {
				$option_tmpl = '<label class="for-option" for="%1$s"><input type="checkbox" name="%1$s[]" id="%1$s" value="%3$s" %5$s> %2$s</label>';
				$option = sprintf(
					$option_tmpl, $field['id'], $label, $value, $idx,
					checked( $value == $field_value || in_array( $value, (array) $field_value ), true, false )
				);
				print $option;
			}
			print '</div>';
		}
	}
}

/**
 * Returns a data structure to describe various aspects of the
 * "About this Transmission" meta box
 */
function rns_transmissions_info_metabox($meta_boxes=array()) {

  // Start with an underscore to hide fields from custom fields list
  $prefix = '_rns_transmission_';

  $meta_boxes[] = array(
    'id'         => 'rns_transmissions_info',
    'title'      => 'About This Transmission',
    'pages'      => array( 'rns_transmission' ), // Post type
    'context'    => 'normal',
    'priority'   => 'high',
    'show_names' => true, // Show field names on the left
    'fields'     => array(
      array(
        'name' => 'Type',
        'desc' => '',
        'id'   => $prefix . 'type',
        'type' => 'radio',
        'options' => array(
          array( 'name' => 'Budget', 'value' => 'budget' ),
          array( 'name' => 'Story / Advisory', 'value' => 'story' ),
          array( 'name' => 'Daily Report', 'value' => 'report' ),
        ),
      ),
      array(
        'name' => 'This Daily Report Includes',
        'desc' => '',
        'id' => $prefix . 'daily_report_includes',
        'type' => 'multicheck',
        'options' => array(
          'quote' => 'Quote of the Day',
          'advisory' => 'Coverage Advisory',
          'calendar' => 'Updated Religion Calendar'
        ),
      ),
      array(
        'name' => 'Slug',
        'desc' => '',
        'id'   => $prefix . 'slug',
        'type' => 'text',
      ),
      array(
        'name' => 'Overline',
        'desc' => 'e.g., "NEWS STORY," "COMMENTARY"',
        'id'   => $prefix . 'overline',
        'type' => 'text',
      ),
      array(
        'name' => 'Word Count',
        'desc' => '',
        'id'   => $prefix . 'wordcount',
        'type' => 'text',
      ),
      array(
        'name' => '"Eds:" Note',
        'desc' => '',
        'id'   => $prefix . 'eds_note',
        'type' => 'textarea_small',
      ),
      array(
        'name' => 'Categories',
        'desc' => '',
        'id'   => $prefix . 'categories',
        'type' => 'text',
      ),
      array(
        'name' => 'Byline',
        'desc' => '',
        'id'   => $prefix . 'byline',
        'type' => 'text',
      ),
      array(
        'name' => 'Copyright',
        'desc' => '',
        'id'   => $prefix . 'copyright',
        'type' => 'text',
      ),
      array(
        'name' => '"END" Line',
        'desc' => '',
        'id'   => $prefix . 'end_line',
        'type' => 'text',
      ),
    ),
  );

  return $meta_boxes;
}
