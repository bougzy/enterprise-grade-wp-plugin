<?php
/**
 * Database service provider.
 *
 * @package FlavorFlow\Database
 */

declare(strict_types=1);

namespace flavor_flow\Database;

use flavor_flow\Core\ServiceProvider;

/**
 * Registers database services.
 */
final class DatabaseServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->set(
			Migrator::class,
			static fn() => new Migrator()
		);
	}

	public function boot(): void {
		$this->maybe_upgrade();
	}

	/**
	 * Run migrations when the DB version is outdated.
	 */
	private function maybe_upgrade(): void {
		$installed = get_option( 'flavor_flow_db_version', '0' );

		if ( version_compare( (string) $installed, FLAVOR_FLOW_VERSION, '<' ) ) {
			( new Migrator() )->up();
		}
	}
}
