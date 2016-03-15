<?php

/**
 * Manage RNS Transmissions migration tasks
 */
class Transmissions_WP_CLI_Command extends WP_CLI_Command {
	/**
	 * Migrate Transmissions from P2P plugin requirement
	 *
	 * ## EXAMPLES
	 *
	 *    wp transmissions migrate
	 *
	 */
	public function migrate() {
		$args = array(
			'nopaging' => true,
			'post_type' => 'rns_transmission'
		);
		$query = new WP_Query($args);

		$progress = \WP_CLI\Utils\make_progress_bar( 'Migrating connected posts', count( $query->posts ) );

		foreach ( $query->posts as $post ) {
			$result = rns_get_daily_report_p2p_connections( $post );

			if ( empty( $result->posts ) ) {
				$progress->tick();
				continue;
			}

			$connected = SplFixedArray::fromArray( $result->posts );
			unset( $result );

			if ( ! empty( $connected ) ) {
				$connected_ids = array();
				foreach ( $connected as $connected_post ) {
					$connected_ids[] = $connected_post->ID;
				}
				update_post_meta( $post->ID, 'rns_transmissions_connected_posts', $connected_ids );
			}
			$progress->tick();
		}

		$progress->finish();
		WP_CLI::success("Finished.");
	}
}
