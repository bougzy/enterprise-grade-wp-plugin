<?php
/**
 * Registry for all available triggers.
 *
 * @package FlavorFlow\Trigger
 */

declare(strict_types=1);

namespace flavor_flow\Trigger;

/**
 * Central registry that holds all trigger instances and starts listeners.
 */
final class TriggerRegistry {

	/** @var TriggerInterface[] Keyed by trigger slug. */
	private array $triggers = [];

	/**
	 * Register a trigger.
	 */
	public function add( TriggerInterface $trigger ): void {
		$this->triggers[ $trigger->get_slug() ] = $trigger;
	}

	/**
	 * Get a trigger by slug.
	 */
	public function get( string $slug ): ?TriggerInterface {
		return $this->triggers[ $slug ] ?? null;
	}

	/**
	 * Return all registered triggers.
	 *
	 * @return TriggerInterface[]
	 */
	public function all(): array {
		return $this->triggers;
	}

	/**
	 * Start all trigger listeners.
	 */
	public function listen_all(): void {
		foreach ( $this->triggers as $trigger ) {
			$trigger->listen();
		}
	}

	/**
	 * Return triggers grouped by category.
	 *
	 * @return array<string, TriggerInterface[]>
	 */
	public function grouped(): array {
		$groups = [];

		foreach ( $this->triggers as $trigger ) {
			$groups[ $trigger->get_group() ][] = $trigger;
		}

		return $groups;
	}
}
