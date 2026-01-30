<?php
/**
 * API service provider.
 *
 * @package FlavorFlow\API
 */

declare(strict_types=1);

namespace flavor_flow\API;

use flavor_flow\Action\ActionRegistry;
use flavor_flow\Condition\ConditionEvaluator;
use flavor_flow\Core\Container;
use flavor_flow\Core\ServiceProvider;
use flavor_flow\Logging\Logger;
use flavor_flow\Trigger\TriggerRegistry;

final class ApiServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->set(
			WorkflowController::class,
			static fn() => new WorkflowController()
		);

		$this->container->set(
			LogController::class,
			static fn( Container $c ) => new LogController(
				$c->get( Logger::class )
			)
		);

		$this->container->set(
			WebhookIngressController::class,
			static fn() => new WebhookIngressController()
		);

		$this->container->set(
			SystemInfoController::class,
			static fn( Container $c ) => new SystemInfoController(
				$c->get( TriggerRegistry::class ),
				$c->get( ConditionEvaluator::class ),
				$c->get( ActionRegistry::class ),
			)
		);
	}

	public function boot(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		$controllers = [
			WorkflowController::class,
			LogController::class,
			WebhookIngressController::class,
			SystemInfoController::class,
		];

		foreach ( $controllers as $class ) {
			$controller = $this->container->get( $class );
			$controller->register_routes();
		}
	}
}
