<?php
/**
 * Trigger service provider.
 *
 * @package FlavorFlow\Trigger
 */

declare(strict_types=1);

namespace flavor_flow\Trigger;

use flavor_flow\Core\ServiceProvider;
use flavor_flow\Trigger\Triggers\CommentPostedTrigger;
use flavor_flow\Trigger\Triggers\PostPublishedTrigger;
use flavor_flow\Trigger\Triggers\UserRegisteredTrigger;
use flavor_flow\Trigger\Triggers\UserRoleChangedTrigger;
use flavor_flow\Trigger\Triggers\WooCommerceOrderTrigger;

final class TriggerServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->set(
			TriggerRegistry::class,
			static function (): TriggerRegistry {
				$registry = new TriggerRegistry();

				$triggers = [
					new PostPublishedTrigger(),
					new UserRegisteredTrigger(),
					new UserRoleChangedTrigger(),
					new CommentPostedTrigger(),
					new WooCommerceOrderTrigger(),
				];

				/**
				 * Filters the list of triggers registered in FlavorFlow.
				 *
				 * @param TriggerInterface[] $triggers Trigger instances.
				 */
				$triggers = apply_filters( 'flavor_flow_triggers', $triggers );

				foreach ( $triggers as $trigger ) {
					$registry->add( $trigger );
				}

				return $registry;
			}
		);
	}

	public function boot(): void {
		/** @var TriggerRegistry $registry */
		$registry = $this->container->get( TriggerRegistry::class );
		$registry->listen_all();
	}
}
