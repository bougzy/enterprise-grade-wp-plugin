<?php
/**
 * Lightweight PSR-11-inspired dependency injection container.
 *
 * @package FlavorFlow\Core
 */

declare(strict_types=1);

namespace flavor_flow\Core;

use InvalidArgumentException;

/**
 * Service container with lazy instantiation.
 */
final class Container {

	/** @var array<string, callable> Factory callbacks keyed by service ID. */
	private array $factories = [];

	/** @var array<string, object> Resolved singleton instances. */
	private array $instances = [];

	/**
	 * Register a service factory.
	 *
	 * @param string   $id      Service identifier (usually FQCN).
	 * @param callable $factory Callback that receives the container and returns the service.
	 */
	public function set( string $id, callable $factory ): void {
		$this->factories[ $id ] = $factory;
		unset( $this->instances[ $id ] );
	}

	/**
	 * Retrieve a service. Resolves lazily, caches as singleton.
	 *
	 * @param string $id Service identifier.
	 * @return object
	 * @throws InvalidArgumentException If the service is not registered.
	 */
	public function get( string $id ): object {
		if ( isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		if ( ! isset( $this->factories[ $id ] ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Service "%s" is not registered in the container.', $id )
			);
		}

		$this->instances[ $id ] = ( $this->factories[ $id ] )( $this );

		return $this->instances[ $id ];
	}

	/**
	 * Check whether a service is registered.
	 *
	 * @param string $id Service identifier.
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->factories[ $id ] ) || isset( $this->instances[ $id ] );
	}
}
