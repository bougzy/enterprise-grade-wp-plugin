<?php
/**
 * Queue service provider.
 *
 * @package FlavorFlow\Queue
 */

declare(strict_types=1);

namespace flavor_flow\Queue;

use flavor_flow\Action\ActionRegistry;
use flavor_flow\Condition\ConditionEvaluator;
use flavor_flow\Core\Container;
use flavor_flow\Core\ServiceProvider;
use flavor_flow\Logging\Logger;

final class QueueServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->set(
			QueueManager::class,
			static fn() => new QueueManager()
		);

		$this->container->set(
			WorkflowEngine::class,
			static fn( Container $c ) => new WorkflowEngine(
				$c->get( ConditionEvaluator::class ),
				$c->get( ActionRegistry::class ),
				$c->get( QueueManager::class ),
				$c->get( Logger::class ),
			)
		);
	}

	public function boot(): void {
		// Wire trigger dispatch to the workflow engine.
		add_action( 'flavor_flow_trigger_dispatched', [ $this, 'on_trigger' ], 10, 2 );

		// WP-Cron hook for queue processing.
		add_action( 'flavor_flow_process_queue', [ $this, 'process_queue' ] );

		// Register custom cron interval.
		add_filter( 'cron_schedules', [ $this, 'add_cron_interval' ] );
	}

	/**
	 * Relay trigger dispatch to the workflow engine.
	 */
	public function on_trigger( string $trigger_slug, array $payload ): void {
		/** @var WorkflowEngine $engine */
		$engine = $this->container->get( WorkflowEngine::class );
		$engine->handle_trigger( $trigger_slug, $payload );
	}

	/**
	 * Process queue via cron.
	 */
	public function process_queue(): void {
		/** @var WorkflowEngine $engine */
		$engine = $this->container->get( WorkflowEngine::class );
		$engine->process_queue();
	}

	/**
	 * Add every-minute cron interval.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public function add_cron_interval( array $schedules ): array {
		$schedules['every_minute'] = [
			'interval' => 60,
			'display'  => __( 'Every Minute', 'flavor-flow' ),
		];

		return $schedules;
	}
}
