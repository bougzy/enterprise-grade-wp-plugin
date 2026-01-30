<?php
/**
 * Uninstall handler — called from uninstall.php.
 *
 * @package FlavorFlow\Core
 */

declare(strict_types=1);

namespace flavor_flow\Core;

use flavor_flow\Database\Migrator;

/**
 * Cleans up all plugin data on uninstall.
 */
final class Uninstaller {

	/**
	 * Remove all plugin data.
	 */
	public function uninstall(): void {
		// Drop custom tables.
		( new Migrator() )->down();

		// Remove options.
		delete_option( 'flavor_flow_settings' );
		delete_option( 'flavor_flow_version' );
		delete_option( 'flavor_flow_db_version' );

		// Remove all workflow posts.
		$this->delete_post_type_data( 'ff_workflow' );

		// Clear any remaining transients.
		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%flavor_flow%'"
		);
	}

	/**
	 * Delete all posts of a given type.
	 */
	private function delete_post_type_data( string $post_type ): void {
		$posts = get_posts( [
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );

		foreach ( $posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}
}
