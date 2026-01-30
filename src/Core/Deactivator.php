<?php
/**
 * Plugin deactivation handler.
 *
 * @package FlavorFlow\Core
 */

declare(strict_types=1);

namespace flavor_flow\Core;

/**
 * Runs on plugin deactivation.
 */
final class Deactivator {

	/**
	 * Execute deactivation tasks.
	 */
	public function deactivate(): void {
		$this->clear_scheduled_events();
		flush_rewrite_rules();
	}

	/**
	 * Remove all plugin cron events.
	 */
	private function clear_scheduled_events(): void {
		$hooks = [
			'flavor_flow_process_queue',
			'flavor_flow_cleanup_logs',
		];

		foreach ( $hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}
	}
}
