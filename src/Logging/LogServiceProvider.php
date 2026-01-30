<?php
/**
 * Log service provider.
 *
 * @package FlavorFlow\Logging
 */

declare(strict_types=1);

namespace flavor_flow\Logging;

use flavor_flow\Core\ServiceProvider;

final class LogServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->set(
			Logger::class,
			static fn() => new Logger()
		);
	}

	public function boot(): void {
		add_action( 'flavor_flow_cleanup_logs', [ $this, 'handle_cleanup' ] );
	}

	/**
	 * Cron callback to purge old logs.
	 */
	public function handle_cleanup(): void {
		$settings  = get_option( 'flavor_flow_settings', [] );
		$retention = (int) ( $settings['log_retention'] ?? 30 );

		/** @var Logger $logger */
		$logger = $this->container->get( Logger::class );
		$logger->purge( $retention );
	}
}
