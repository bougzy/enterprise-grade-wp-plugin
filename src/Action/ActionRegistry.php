<?php
/**
 * Action registry.
 *
 * @package FlavorFlow\Action
 */

declare(strict_types=1);

namespace flavor_flow\Action;

final class ActionRegistry {

	/** @var ActionInterface[] Keyed by slug. */
	private array $actions = [];

	public function add( ActionInterface $action ): void {
		$this->actions[ $action->get_slug() ] = $action;
	}

	public function get( string $slug ): ?ActionInterface {
		return $this->actions[ $slug ] ?? null;
	}

	/** @return ActionInterface[] */
	public function all(): array {
		return $this->actions;
	}

	/**
	 * @return array<string, ActionInterface[]>
	 */
	public function grouped(): array {
		$groups = [];
		foreach ( $this->actions as $action ) {
			$groups[ $action->get_group() ][] = $action;
		}
		return $groups;
	}
}
