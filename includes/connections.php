<?php

function rns_add_connections_meta_box_callback() {
	global $post;
	$connected = rns_get_daily_report_connections($post);
?>
	<div id="rns-connections-view">
		<div class="rns-connections"><?php
		if ( ! empty( $connected->posts ) ) {
			foreach ( $connected->posts as $connection) { ?>
				<div class="rns-connection" data-id="<?php echo $connection->ID; ?>"><a class="rns-remove-connection" href="#">x</a> <?php echo $connection->post_title; ?>
					<span class="sortable-handle"></span></div>
			<?php }
		}
		?></div>
		<div class="rns-connection-add"><a href="#">+ Select articles to include</a></div>
		<div class="rns-create-connections">
			<input type="text" name="rns_transmission_search" placeholder="Search Transmissions" />
			<div class="rns-available-connections"></div>
		</div>
		<span class="spinner"></span>
	</div>

	<?php rns_add_connections_js_templates(); ?>
<?php }

function rns_add_connections_js_templates() { ?>
<script type="text/template" id="rns-connection-item-tmpl">
	<div class="rns-connection" data-id="<%= ID %>"><a class="rns-remove-connection" href="#">x</a> <%= post_title %> <span class="sortable-handle"></span></div>
</script>

<script type="text/template" id="rns-connection-add-item-tmpl">
	<div class="rns-available-connection" data-id="<%= ID %>"><a class="rns-add-connection" href="#">+</a> <%= post_title %> <span class="sortable-handle"></span></div>
</script>
<?php }

function rns_transmissions_ajax() {
	$path = $_POST['path'];
	$data = json_decode(stripslashes($_POST['data']), true);

	if ( $path == 'transmissions' ) {
		if ( isset( $data['post_ids'] ) ) {
			echo rns_transmissions_collection_json($data['post_ids']);
		} else if ( isset( $data['search_term'] ) ) {
			echo rns_transmissions_collection_json(null, $data['search_term']);
		} else {
			echo rns_transmissions_collection_json();
		}
	} else if ( preg_match( '/^transmission\/(\d+)\/update$/', $path, $matches ) ) {
		if ( ! empty( $data ) ) {
			echo rns_transmissions_post_save_json($matches[1], $data);
		}
	} else if ( preg_match( '/^transmission\/(\d+)$/', $path, $matches ) ) {
		if ( ! empty( $data ) ) {
			echo rns_transmission_post_json($matches[1]);
		}
	}
	die();
}
add_action( 'wp_ajax_rns_transmissions_ajax', 'rns_transmissions_ajax' );

function rns_transmissions_collection_json($post_ids=array(), $search_term=null) {
	$args = array(
		'post_status' => 'any',
		'post_type' => array( 'rns_transmission' ),
		'posts_per_page' => 10,
		'paged' => '1'
	);

	if ( ! empty( $post_ids ) ) {
		$args['post__in'] = $post_ids;
		$args['orderby'] = 'post__in';
	} else {
		$args['order'] = 'DESC';
	}

	if ( ! empty( $search_term ) ) {
		$args['s'] = $search_term;
	}

	$posts = new WP_Query( $args );
	return json_encode( $posts->posts );
}

function rns_transmission_post_json($id) {
	$custom_fields = get_post_custom( $id );
	$post_meta = array_map( function($x) { return maybe_unserialize( $x[0] ); }, $custom_fields );
	$post = get_post( $id );
	return json_encode( array_merge( (array) $post, array( 'post_meta' => $post_meta ) ) );
}

function rns_transmissions_post_save_json($id, $data) {
	$post_meta = (array) $data['post_meta'];
	unset($data['post_meta']);
	$result = wp_update_post( $data, true );

	if ( is_wp_error( $result ) ) {
		header( "HTTP/1.1 500 Internal Server Error" );
		return json_encode( $result );
	}

	foreach ( $post_meta as $key => $value ) {
		update_post_meta( $id, $key, $value );
	}

	return json_encode( get_post( $id ) );
}
