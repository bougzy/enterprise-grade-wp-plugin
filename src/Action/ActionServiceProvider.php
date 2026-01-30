<?php
/**
 * Action service provider.
 *
 * @package FlavorFlow\Action
 */

declare(strict_types=1);

namespace flavor_flow\Action;

use flavor_flow\Action\Actions\SendEmailAction;
use flavor_flow\Action\Actions\SendWebhookAction;
use flavor_flow\Action\Actions\UpdatePostMetaAction;
use flavor_flow\Action\Actions\UpdateUserMetaAction;
use flavor_flow\Core\ServiceProvider;

final class ActionServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->set(
			ActionRegistry::class,
			static function (): ActionRegistry {
				$registry = new ActionRegistry();

				$actions = [
					new SendEmailAction(),
					new SendWebhookAction(),
					new UpdatePostMetaAction(),
					new UpdateUserMetaAction(),
				];

				/**
				 * Filters available workflow actions.
				 *
				 * @param ActionInterface[] $actions Action instances.
				 */
				$actions = apply_filters( 'flavor_flow_actions', $actions );

				foreach ( $actions as $action ) {
					$registry->add( $action );
				}

				return $registry;
			}
		);
	}
}
