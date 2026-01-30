<?php
/**
 * Workflow execution engine.
 *
 * @package FlavorFlow\Queue
 */

declare(strict_types=1);

namespace flavor_flow\Queue;

use flavor_flow\Action\ActionRegistry;
use flavor_flow\Condition\ConditionEvaluator;
use flavor_flow\Logging\Logger;
use flavor_flow\PostType\WorkflowPostType;

/**
 * Orchestrates workflow evaluation and execution.
 *
 * Workflow post meta structure:
 * - _ff_trigger:    string   Trigger slug.
 * - _ff_conditions: array    Condition group (JSON-decoded).
 * - _ff_actions:    array    Ordered list of action configs.
 * - _ff_enabled:    string   "1" or "0".
 */
final class WorkflowEngine {

	/** @var ConditionEvaluator */
	private $evaluator;

	/** @var ActionRegistry */
	private $actions;

	/** @var QueueManager */
	private $queue;

	/** @var Logger */
	private $logger;

	public function __construct(
		ConditionEvaluator $evaluator,
		ActionRegistry $actions,
		QueueManager $queue,
		Logger $logger
	) {
		$this->evaluator = $evaluator;
		$this->actions   = $actions;
		$this->queue     = $queue;
		$this->logger    = $logger;
	}

	/**
	 * Handle a dispatched trigger — find matching workflows and enqueue them.
	 *
	 * @param string $trigger_slug Trigger slug.
	 * @param array  $payload      Event payload.
	 */
	public function handle_trigger( string $trigger_slug, array $payload ): void {
		$workflows = $this->find_workflows( $trigger_slug );

		foreach ( $workflows as $workflow_id ) {
			$settings = get_option( 'flavor_flow_settings', [] );
			$mode     = $settings['execution_mode'] ?? 'async';

			if ( 'sync' === $mode ) {
				$this->execute_workflow( $workflow_id, $payload );
			} else {
				$this->queue->push( $workflow_id, $payload );
				$this->logger->info(
					$workflow_id,
					$trigger_slug,
					'Workflow queued for async execution.'
				);
			}
		}
	}

	/**
	 * Execute a single workflow.
	 *
	 * @param int   $workflow_id Workflow post ID.
	 * @param array $payload     Trigger payload.
	 */
	public function execute_workflow( int $workflow_id, array $payload ): void {
		$trigger_slug = (string) get_post_meta( $workflow_id, '_ff_trigger', true );
		$conditions   = (array) get_post_meta( $workflow_id, '_ff_conditions', true );
		$action_list  = (array) get_post_meta( $workflow_id, '_ff_actions', true );

		// Evaluate conditions.
		if ( ! empty( $conditions ) && ! $this->evaluator->evaluate( $conditions, $payload ) ) {
			$this->logger->info(
				$workflow_id,
				$trigger_slug,
				'Conditions not met — skipping execution.',
				[ 'payload' => $payload ]
			);
			return;
		}

		// Execute each action in order.
		foreach ( $action_list as $action_config ) {
			$action_slug = $action_config['type'] ?? '';
			$action      = $this->actions->get( $action_slug );

			if ( ! $action ) {
				$this->logger->warning(
					$workflow_id,
					$trigger_slug,
					sprintf( 'Unknown action type: %s', $action_slug )
				);
				continue;
			}

			try {
				$result = $action->execute( $action_config['config'] ?? [], $payload );

				if ( $result->is_success() ) {
					$this->logger->info(
						$workflow_id,
						$trigger_slug,
						sprintf( 'Action "%s" succeeded: %s', $action_slug, $result->get_message() ),
						$result->get_data()
					);
				} else {
					$this->logger->error(
						$workflow_id,
						$trigger_slug,
						sprintf( 'Action "%s" failed: %s', $action_slug, $result->get_message() ),
						$result->get_data()
					);

					// Stop the chain on failure (fail-fast).
					break;
				}
			} catch ( \Throwable $e ) {
				$this->logger->error(
					$workflow_id,
					$trigger_slug,
					sprintf( 'Action "%s" threw an exception: %s', $action_slug, $e->getMessage() ),
					[ 'trace' => $e->getTraceAsString() ]
				);
				break;
			}
		}
	}

	/**
	 * Process queued jobs (called by WP-Cron).
	 */
	public function process_queue(): void {
		$jobs = $this->queue->claim( 10 );

		foreach ( $jobs as $job ) {
			$payload = json_decode( $job->payload, true ) ?: [];

			try {
				$this->execute_workflow( (int) $job->workflow_id, $payload );
				$this->queue->complete( (int) $job->id );
			} catch ( \Throwable $e ) {
				$this->logger->error(
					(int) $job->workflow_id,
					'queue',
					sprintf( 'Queue job %d failed: %s', $job->id, $e->getMessage() )
				);
				$this->queue->fail( (int) $job->id );
			}
		}
	}

	/**
	 * Find enabled workflows that match a given trigger.
	 *
	 * @param string $trigger_slug Trigger slug.
	 * @return int[] Workflow post IDs.
	 */
	private function find_workflows( string $trigger_slug ): array {
		$query = new \WP_Query( [
			'post_type'      => WorkflowPostType::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				[
					'key'   => '_ff_trigger',
					'value' => sanitize_text_field( $trigger_slug ),
				],
				[
					'key'   => '_ff_enabled',
					'value' => '1',
				],
			],
		] );

		return array_map( 'intval', $query->posts );
	}
}
