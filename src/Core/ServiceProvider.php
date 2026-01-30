<?php
/**
 * Abstract service provider.
 *
 * @package FlavorFlow\Core
 */

declare(strict_types=1);

namespace flavor_flow\Core;

/**
 * Base class for all service providers.
 *
 * Each provider registers its services on the container during `register()`
 * and hooks into WordPress during `boot()`.
 */
abstract class ServiceProvider {

	protected Container $container;

	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register services on the container.
	 */
	abstract public function register(): void;

	/**
	 * Boot services — called after all providers have been registered.
	 */
	public function boot(): void {}
}
