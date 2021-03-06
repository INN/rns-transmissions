<?php
function rns_transmissions_load_css() {
	$plugin_url = plugins_url( basename( dirname( __DIR__ ), __DIR__ ) );
	$current_screen = get_current_screen();

	wp_register_style(
		'rns_transmissions_css',
		$plugin_url . '/assets/css/transmissions.css',
		false,
		'1.0.0'
	);
	wp_enqueue_style( 'rns_transmissions_css' );

	wp_register_script(
		'rns-download-js',
		$plugin_url . '/assets/js/rns-download.js',
		array( 'jquery' ),
		'1.0.0',
		true
	);

	wp_register_script(
		'rns-connect-posts-js',
		$plugin_url . '/assets/js/rns-connect-posts.js',
		array( 'jquery', 'jquery-ui-sortable', 'backbone' ),
		'1.0.0',
		true
	);

	if ( 'rns_transmission' === $current_screen->post_type && 'post' === $current_screen->base ) {
		wp_enqueue_script( 'rns-download-js' );
		wp_enqueue_script( 'rns-connect-posts-js' );
	}

	wp_register_script(
		'rns-settings-js',
		$plugin_url . '/assets/js/rns-settings.js',
		array( 'jquery' ),
		'1.0.0',
		true
	);

	if ( 'rns_transmission_page_rns_transmissions' === $current_screen->base ) {
		wp_enqueue_script( 'rns-settings-js' );
	}
}
add_action( 'admin_enqueue_scripts', 'rns_transmissions_load_css' );

/**
 * JS for the post page
 */
function rns_admin_enqueue_assets() {
}
add_action( 'admin_enqueue_scripts', 'rns_admin_enqueue_assets' );

function update_available_lists() {
	$mc_api = mailchimp_tools_get_api_handle();
	if ( empty( $mc_api ) ) {
		wp_die( 'Please <a href="/wp-admin/options-general.php?page=mailchimp_settings">enter your MailChimp API Key</a> before proceeding.' );
	}
	$result = $mc_api->get( 'lists' );
	if ( ! empty( $result ) ) {
		foreach ( $result['lists'] as $list ) {
			$lists[ $list['name'] ] = $list['id'];

			try {
				$list_groups = $mc_api->get( 'lists/' . $list['id'] . '/interest-categories' );
				if ( ! empty( $list_groups ) ) {
					$groups[ $list['id'] ] = $list_groups;
					foreach ( $list_groups['categories'] as $key => $interest_group ) {
						$interest_categories = $mc_api->get( 'lists/' . $interest_group['list_id'] . '/interest-categories/' . $interest_group['id'] . '/interests' );
						if ( ! empty( $interest_categories ) ) {
							$groups[ $list['id'] ]['categories'][$key]['interests'] = $interest_categories['interests']; // @TODO need to work with this to get it assigned to the right location
						}
					}
				}
			} catch ( MailChimp_List_InvalidOption $e ) {
				continue;
			}
		}

		update_option( 'rns_transmissions_lists', $lists );
		update_option( 'rns_transmissions_lists_groups', $groups );

		echo '
			<div class="updated">
			<p>Lists updated.</p>
			</div>
			';
	} else {
		/* Error reporting here */
	}
}

/**
 * Add a submenu item to the Settings menu.
 */
function rns_transmissions_create_menu() {
	add_submenu_page(
		'edit.php?post_type=rns_transmission',
		'RNS Transmissions Settings',
		'Settings',
		'manage_options',
		'rns_transmissions',
		'rns_transmissions_settings_section_page'
	);
}
add_action( 'admin_menu', 'rns_transmissions_create_menu' );

/**
 * Draw the settings page.
 */
function rns_transmissions_settings_section_page() {
	update_available_lists();
	?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2>RNS Transmissions</h2>
		<form action="options.php" method="post" accept-charset="utf-8">
		<?php
			settings_fields( 'rns_transmissions_options' );
			do_settings_sections( 'rns_transmissions' );
		?>
		<p><?php submit_button( 'Save changes', 'submit', 'Submit' ); ?></p>
		</form>
	</div><!--/.wrap-->
<?php
}

/**
 * Register and define the settings.
 */
function rns_transmissions_admin_init() {
	/**
	 * Register Transmission settings
	 */
	register_setting(
		'rns_transmissions_options',
		'rns_transmissions_options',
		'rns_transmissions_validate_options'
	);

	/**
	 * Add section for enable/disable Tranmissions
	 */
	add_settings_section(
		'rns_transmissions_enabled_section',
		'Enabled',
		'__return_false',
		'rns_transmissions'
	);
	add_settings_field(
		'rns_transmissions_is_enabled',
		'Enable Transmission sending',
		'rns_transmissions_is_enabled_input',
		'rns_transmissions',
		'rns_transmissions_enabled_section'
	);

	/**
	 * Add section for Transmission settings
	 */
	add_settings_section(
		'rns_transmissions_settings_section',
		'Settings',
		'__return_false',
		'rns_transmissions'
	);
	add_settings_field(
		'rns_transmissions_from_name',
		'Transmission\'s \'From\' Name',
		'rns_transmissions_from_name_input',
		'rns_transmissions',
		'rns_transmissions_settings_section'
	);
	add_settings_field(
		'rns_transmissions_from_email',
		'Transmission\'s \'From\' Email Address',
		'rns_transmissions_from_email_input',
		'rns_transmissions',
		'rns_transmissions_settings_section'
	);
	add_settings_field(
		'rns_transmissions_lists_available',
		'Default list and groups',
		'rns_transmissions_lists_available_cboxes',
		'rns_transmissions',
		'rns_transmissions_settings_section'
	);

	/**
	 * Add a section for E-WIRE settings
	 */
	add_settings_section(
		'rns_transmissions_ewire_settings',
		'E-WIRE',
		'__return_false',
		'rns_transmissions'
	);
	add_settings_field(
		'rns_transmissions_ewire_heading_boilerplate',
		'Heading Boilerplate',
		'rns_transmissions_ewire_heading_boilerplate_input',
		'rns_transmissions',
		'rns_transmissions_ewire_settings'
	);
	add_settings_field(
		'rns_transmissions_ewire_separator',
		'Separator',
		'rns_transmissions_ewire_separator_input',
		'rns_transmissions',
		'rns_transmissions_ewire_settings'
	);
	add_settings_field(
		'rns_transmissions_default_ap_headers',
		'Default AP Headers',
		'rns_transmissions_default_ap_headers_input',
		'rns_transmissions',
		'rns_transmissions_ewire_settings'
	);
}
add_action( 'admin_init', 'rns_transmissions_admin_init' );

/**
 * Display, fill, and validate the form fields.
 */
function rns_transmissions_api_key_input() {
	/* Get option 'api_key' value from the database */
	$options = get_option( 'rns_transmissions_options' );
	$api_key = $options['api_key'];
	/* Echo the field */
	echo '<input type="text" name="rns_transmissions_options[api_key]" id="api_key" value="' . esc_attr( $api_key ) . '" size="50" />';
}

function rns_transmissions_list_id_input() {
	/* Get option 'list_id' value from the database */
	$options = get_option( 'rns_transmissions_options' );
	$list_id = $options['list_id'];
	/* Echo the field */
	echo '<input type="text" name="rns_transmissions_options[list_id]" id="list_id" value="' . esc_attr( $list_id ) . '" size="50" />';
}

function rns_transmissions_from_name_input() {
	/* Get option 'list_id' value from the database */
	$options = get_option( 'rns_transmissions_options' );
	$from_name = $options['from_name'];
	/* Echo the field */
	echo '<input type="text" name="rns_transmissions_options[from_name]" id="from_name" value="' . esc_attr( $from_name ) . '" size="50" />';
}

function rns_transmissions_from_email_input() {
	/* Get option 'list_id' value from the database */
	$options = get_option( 'rns_transmissions_options' );
	$from_email = $options['from_email'];
	/* Echo the field */
	echo '<input type="email" name="rns_transmissions_options[from_email]" id="from_email" value="' . esc_attr( $from_email ) . '" size="50" />';
}

function rns_transmissions_lists_available_cboxes() {
	$available_lists = get_option( 'rns_transmissions_lists' );
	$available_groups = get_option( 'rns_transmissions_lists_groups' );
	$options = get_option( 'rns_transmissions_options' );
	$default_list = $options['lists_enabled'][0];
	echo '<fieldset>';
	echo '<legend class="screen-reader-text">Available lists</legend>';
	echo '<div id="rns-default-lists-groups">';

	if ( $available_lists ) {
		foreach ( $available_lists as $name => $id ) {
			$disabled = ( empty( $available_groups[ $id ] ) ) ? 'disabled="disabled"' : '';
			echo '<label for="rns_transmissions_options_' . intval( $id ) . '">';
			echo '<input ' . esc_attr( $disabled ) . ' ' . checked( $id, $default_list, false ) . ' name="rns_transmissions_options[lists_enabled]" type="radio" id="rns_transmissions_options_' . $id . '" value="' . $id . '"> ';
			echo esc_html( $name );

			if ( ! empty( $available_groups[ $id ] ) ) {
				$default_group = $options['list_groups'][ $id ]['default_group'];

				echo '<br>';
				echo '<div class="nested-group" style="margin: 10px 0 0 20px;">';
				foreach ( $available_groups[ $id ]['categories'] as $group ) {
					echo '<label for="rns_transmissions_options_' . intval( $group['id'] ) . '_default_group">';
					echo '<input ' . checked( $group['id'], $default_group, false ) . ' name="rns_transmissions_options[list_groups][' . $id . '][default_group]" type="radio" id="rns_transmissions_options_' . $group['id'] . '_default_group" value="' . $group['id'] . '" >';
					echo esc_html( $group['title'] );
					echo '</label><br>';
				}
				echo '</div>';
			}

			echo '</label><br>';
		}
	} else {
		echo '<em>You don\'t have any lists setup in MailChimp.</em>';
	}

	echo '</fieldset>';
	echo '</div>';
}

function rns_transmissions_ewire_heading_boilerplate_input() {
	$options = get_option( 'rns_transmissions_options' );
	$ewire_heading_boilerplate = $options['ewire_heading_boilerplate'];
	echo '<textarea name="rns_transmissions_options[ewire_heading_boilerplate]" id="ewire_heading_boilerplate" rows="5" cols="50">' . esc_html( $ewire_heading_boilerplate ) . '</textarea>';
}

function rns_transmissions_ewire_separator_input() {
	$options = get_option( 'rns_transmissions_options' );
	$ewire_separator = $options['ewire_separator'];
	echo '<input type="text" name="rns_transmissions_options[ewire_separator]" id="ewire_separator" value="' . esc_html( $ewire_separator ) . '" size="50" />';
}

function rns_transmissions_default_ap_headers_input() {
	$options = get_option( 'rns_transmissions_options' );
	$default_ap_headers = $options['default_ap_headers'];
	echo '<textarea name="rns_transmissions_options[default_ap_headers]" id="default_ap_headers" rows="5" cols="50">' . esc_html( $default_ap_headers ) . '</textarea>';
}

function rns_transmissions_is_enabled_input() {
	$options = get_option( 'rns_transmissions_options' );
	$checked = $options['is_enabled'];
	echo '<input type="checkbox" name="rns_transmissions_options[is_enabled]" id="is_enabled" value="1" ' . checked( $checked, true, false ) . ' />';
}

function rns_transmissions_validate_options( $input ) {
	/* Create an empty array and collect in this array only the values you expect */
	$valid = array();

	$valid['from_name'] = sanitize_text_field( $input['from_name'] );
	if ( $valid['from_name'] !== $input['from_name'] ) {
		add_settings_error(
			'rns_transmissions_from_name',
			'rns_transmissions_error',
			'Warning: Invalid characters stripped from From Name',
			'updated'
		);
	}

	$valid['from_email'] = is_email( $input['from_email'] );
	if ( $valid['from_email'] !== $input['from_email'] ) {
		add_settings_error(
			'rns_transmissions_from_email',
			'rns_transmissions_error',
			'Error: Invalid email address',
			'error'
		);
	}

	if ( ctype_alnum( $input['lists_enabled'] ) ) {
		$valid['lists_enabled'] = array( $input['lists_enabled'] );
	} else {
		add_settings_error(
			'rns_transmissions_lists_enabled',
			'rns_transmissions_error',
			'Error: List ID may contain only letters and numbers',
			'error'
		);
	}

	if ( is_array( $input['list_groups'] ) ) {
		$valid['list_groups'] = $input['list_groups'];
	}

	$valid['ewire_heading_boilerplate'] = wp_kses( $input['ewire_heading_boilerplate'], array( 'br' => array() ) );
	if ( $valid['ewire_heading_boilerplate'] !== $input['ewire_heading_boilerplate'] ) {
		add_settings_error(
			'rns_transmissions_ewire_heading_boilerplate',
			'rns_transmissions_error',
			'Warning: Invalid characters stripped from E-WIRE Heading Boilerplate',
			'updated'
		);
	}

	$valid['ewire_separator'] = sanitize_text_field( $input['ewire_separator'] );
	if ( $valid['ewire_separator'] !== $input['ewire_separator'] ) {
		add_settings_error(
			'rns_transmissions_ewire_separator',
			'rns_transmissions_error',
			'Warning: Invalid characters stripped from E-WIRE Separator',
			'updated'
		);
	}

	$valid['default_ap_headers'] = wp_strip_all_tags( $input['default_ap_headers'] );
	if ( $valid['default_ap_headers'] !== $input['default_ap_headers'] ) {
		add_settings_error(
			'rns_transmissions_default_ap_headers',
			'rns_transmissions_error',
			'Warning: Invalid characters stripped from Default AP Headers',
			'updated'
		);
	}

	/* This is a checkbox */
	$valid['is_enabled'] = $input['is_enabled'];

	return $valid;
}


/**
 * Warn users that Pending posts cannot be edited.
 */
function rns_transmissions_pending_transmission_admin_notice() {
	echo '
		<div class="error">
		<p>This Transmission can no longer be edited. Any changes you make will not be saved. Click Publish to have MailChimp send it at the time listed.</p>
		</div>
		';
}

/**
 * Disable the WYSIWYG editor
 *
 * @source http://trepmal.com/2011/12/24/disable-wysiwyg-editor-for-custom-post-types/
 */
function disable_for_cpt( $default ) {
	global $post;
	if ( 'rns_transmission' === get_post_type( $post ) ) {
		return false;
	} else {
		return $default;
	}
}
add_filter( 'user_can_richedit', 'disable_for_cpt' );

/**
 * Autofill the copyright line on new Transmission
 *
 * @uses $hook_suffix The name of the admin page being viewed
 */
function rns_transmissions_admin_scripts() {
	global $post, $hook_suffix;
	if ( $post && 'rns_transmission' === $post->post_type && 'post-new.php' === $hook_suffix ) {
?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			if ( jQuery('#_rns_transmission_copyright').val() == "" ) {
				jQuery('#_rns_transmission_copyright').val('c. ' + new Date().getFullYear() + ' Religion News Service');
	}
	});
	</script>
<?php
	}
}
add_action( 'admin_head', 'rns_transmissions_admin_scripts' );
