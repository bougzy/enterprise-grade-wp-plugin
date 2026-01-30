<?php
/**
 * Plugin activation handler.
 *
 * @package FlavorFlow\Core
 */

declare(strict_types=1);

namespace flavor_flow\Core;

use flavor_flow\Database\Migrator;

/**
 * Runs on plugin activation.
 */
final class Activator {

	/**
	 * Execute activation tasks.
	 */
	public function activate(): void {
		$this->check_requirements();
		$this->run_migrations();
		$this->set_defaults();
		$this->schedule_events();

		// Flush rewrite rules for custom post types.
		flush_rewrite_rules();

		update_option( 'flavor_flow_version', FLAVOR_FLOW_VERSION );
	}

	/**
	 * Bail if the server does not meet minimum requirements.
	 */
	private function check_requirements(): void {
		if ( version_compare( PHP_VERSION, FLAVOR_FLOW_MINIMUM_PHP, '<' ) ) {
			deactivate_plugins( FLAVOR_FLOW_BASENAME );
			wp_die(
				sprintf(
					/* translators: %s: required PHP version */
					esc_html__( 'FlavorFlow requires PHP %s or higher.', 'flavor-flow' ),
					esc_html( FLAVOR_FLOW_MINIMUM_PHP )
				),
				'Plugin Activation Error',
				[ 'back_link' => true ]
			);
		}
	}

	/**
	 * Run database migrations.
	 */
	private function run_migrations(): void {
		( new Migrator() )->up();
	}

	/**
	 * Store default option values.
	 */
	private function set_defaults(): void {
		$defaults = [
			'flavor_flow_settings' => [
				'enable_logging'    => true,
				'log_retention'     => 30,
				'execution_mode'    => 'async',
				'max_retries'       => 3,
				'webhook_timeout'   => 15,
				'license_key'       => '',
				'license_status'    => 'inactive',
			],
		];

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}

	/**
	 * Register WP-Cron events.
	 */
	private function schedule_events(): void {
		if ( ! wp_next_scheduled( 'flavor_flow_process_queue' ) ) {
			wp_schedule_event( time(), 'every_minute', 'flavor_flow_process_queue' );
		}

		if ( ! wp_next_scheduled( 'flavor_flow_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'flavor_flow_cleanup_logs' );
		}
	}
}
