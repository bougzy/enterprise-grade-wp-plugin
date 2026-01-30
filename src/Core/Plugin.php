<?php
/**
 * Main plugin orchestrator.
 *
 * @package FlavorFlow\Core
 */

declare(strict_types=1);

namespace flavor_flow\Core;

use flavor_flow\Admin\AdminServiceProvider;
use flavor_flow\API\ApiServiceProvider;
use flavor_flow\Action\ActionServiceProvider;
use flavor_flow\Condition\ConditionServiceProvider;
use flavor_flow\Database\DatabaseServiceProvider;
use flavor_flow\License\LicenseServiceProvider;
use flavor_flow\Logging\LogServiceProvider;
use flavor_flow\PostType\PostTypeServiceProvider;
use flavor_flow\Queue\QueueServiceProvider;
use flavor_flow\Taxonomy\TaxonomyServiceProvider;
use flavor_flow\Trigger\TriggerServiceProvider;

/**
 * Central plugin class responsible for wiring all components.
 */
final class Plugin {

	private Container $container;

	/** @var ServiceProvider[] */
	private array $providers = [];

	private bool $booted = false;

	public function __construct() {
		$this->container = new Container();
	}

	/**
	 * Boot the plugin: register all service providers then boot them.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->register_providers();

		foreach ( $this->providers as $provider ) {
			$provider->register();
		}

		foreach ( $this->providers as $provider ) {
			$provider->boot();
		}

		$this->booted = true;

		/**
		 * Fires after FlavorFlow has fully booted.
		 *
		 * @param Plugin $plugin Plugin instance.
		 */
		do_action( 'flavor_flow_loaded', $this );
	}

	/**
	 * Access the DI container.
	 */
	public function container(): Container {
		return $this->container;
	}

	/**
	 * Instantiate and collect all service providers.
	 */
	private function register_providers(): void {
		$provider_classes = [
			DatabaseServiceProvider::class,
			LogServiceProvider::class,
			PostTypeServiceProvider::class,
			TaxonomyServiceProvider::class,
			TriggerServiceProvider::class,
			ConditionServiceProvider::class,
			ActionServiceProvider::class,
			QueueServiceProvider::class,
			ApiServiceProvider::class,
			AdminServiceProvider::class,
			LicenseServiceProvider::class,
		];

		/**
		 * Filters the list of service provider class names.
		 *
		 * @param string[] $provider_classes Fully-qualified class names.
		 */
		$provider_classes = apply_filters( 'flavor_flow_service_providers', $provider_classes );

		foreach ( $provider_classes as $class ) {
			$this->providers[] = new $class( $this->container );
		}
	}
}
