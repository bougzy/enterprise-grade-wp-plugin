<?php
/**
 * Queue manager for async job processing.
 *
 * @package FlavorFlow\Queue
 */

declare(strict_types=1);

namespace flavor_flow\Queue;

/**
 * Manages the custom queue table for deferred workflow execution.
 */
final class QueueManager {

	/**
	 * Add a job to the queue.
	 *
	 * @param int   $workflow_id Workflow post ID.
	 * @param array $payload     Trigger payload.
	 * @param int   $delay       Seconds to delay execution.
	 * @return int|false Inserted row ID or false.
	 */
	public function push( int $workflow_id, array $payload, int $delay = 0 ): int|false {
		global $wpdb;

		$settings     = get_option( 'flavor_flow_settings', [] );
		$max_attempts = (int) ( $settings['max_retries'] ?? 3 );

		$scheduled = gmdate( 'Y-m-d H:i:s', time() + $delay );

		$inserted = $wpdb->insert(
			$wpdb->prefix . 'flavor_flow_queue',
			[
				'workflow_id'  => $workflow_id,
				'payload'      => wp_json_encode( $payload ),
				'status'       => 'pending',
				'attempts'     => 0,
				'max_attempts' => $max_attempts,
				'scheduled_at' => $scheduled,
				'created_at'   => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%d', '%d', '%s', '%s' ]
		);

		return $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Claim the next batch of pending jobs.
	 *
	 * @param int $limit Maximum jobs to claim.
	 * @return array
	 */
	public function claim( int $limit = 10 ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'flavor_flow_queue';
		$now   = current_time( 'mysql', true );

		// Atomically claim jobs by setting status to "processing".
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				 SET status = 'processing', started_at = %s
				 WHERE status = 'pending'
				   AND scheduled_at <= %s
				   AND attempts < max_attempts
				 ORDER BY scheduled_at ASC
				 LIMIT %d",
				$now,
				$now,
				$limit
			)
		);

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE status = 'processing' AND started_at = %s ORDER BY id ASC",
				$now
			)
		) ?: [];
	}

	/**
	 * Mark a job as completed.
	 */
	public function complete( int $job_id ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'flavor_flow_queue',
			[
				'status'       => 'completed',
				'completed_at' => current_time( 'mysql', true ),
			],
			[ 'id' => $job_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * Mark a job as failed and increment its attempt counter.
	 */
	public function fail( int $job_id ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'flavor_flow_queue';

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				 SET status = IF(attempts + 1 >= max_attempts, 'failed', 'pending'),
				     attempts = attempts + 1,
				     started_at = NULL
				 WHERE id = %d",
				$job_id
			)
		);
	}

	/**
	 * Purge completed jobs older than $days.
	 */
	public function purge( int $days = 7 ): int {
		global $wpdb;

		$table = $wpdb->prefix . 'flavor_flow_queue';

		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE status IN ('completed','failed') AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);
	}
}
