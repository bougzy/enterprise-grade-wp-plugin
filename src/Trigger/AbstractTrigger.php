<?php
/**
 * Base trigger with shared dispatch logic.
 *
 * @package FlavorFlow\Trigger
 */

declare(strict_types=1);

namespace flavor_flow\Trigger;

/**
 * Provides the dispatch() helper used by all concrete triggers.
 */
abstract class AbstractTrigger implements TriggerInterface {

	/**
	 * Dispatch the trigger payload to the workflow engine.
	 *
	 * @param array<string, mixed> $payload Data from the triggering event.
	 */
	protected function dispatch( array $payload ): void {
		/**
		 * Fires when any FlavorFlow trigger is dispatched.
		 *
		 * @param string $slug    Trigger slug.
		 * @param array  $payload Event payload.
		 */
		do_action( 'flavor_flow_trigger_dispatched', $this->get_slug(), $payload );
	}
}
