<?php
/**
 * Structured logging service.
 *
 * @package FlavorFlow\Logging
 */

declare(strict_types=1);

namespace flavor_flow\Logging;

/**
 * Writes structured log entries to the custom logs table.
 */
final class Logger {

	public const LEVEL_DEBUG   = 'debug';
	public const LEVEL_INFO    = 'info';
	public const LEVEL_WARNING = 'warning';
	public const LEVEL_ERROR   = 'error';

	/**
	 * Write a log entry.
	 *
	 * @param int    $workflow_id  Associated workflow post ID (0 for system logs).
	 * @param string $trigger_name Trigger that initiated the event.
	 * @param string $status       Log level / status.
	 * @param string $message      Human-readable message.
	 * @param array  $context      Arbitrary context data (JSON-serialised).
	 */
	public function log(
		int $workflow_id,
		string $trigger_name,
		string $status,
		string $message,
		array $context = []
	): void {
		$settings = get_option( 'flavor_flow_settings', [] );

		if ( empty( $settings['enable_logging'] ) ) {
			return;
		}

		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'flavor_flow_logs',
			[
				'workflow_id'  => $workflow_id,
				'trigger_name' => sanitize_text_field( $trigger_name ),
				'status'       => sanitize_key( $status ),
				'message'      => sanitize_text_field( $message ),
				'context'      => wp_json_encode( $context ),
				'created_at'   => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Convenience helpers.
	 */
	public function info( int $workflow_id, string $trigger, string $message, array $context = [] ): void {
		$this->log( $workflow_id, $trigger, self::LEVEL_INFO, $message, $context );
	}

	public function error( int $workflow_id, string $trigger, string $message, array $context = [] ): void {
		$this->log( $workflow_id, $trigger, self::LEVEL_ERROR, $message, $context );
	}

	public function warning( int $workflow_id, string $trigger, string $message, array $context = [] ): void {
		$this->log( $workflow_id, $trigger, self::LEVEL_WARNING, $message, $context );
	}

	public function debug( int $workflow_id, string $trigger, string $message, array $context = [] ): void {
		$this->log( $workflow_id, $trigger, self::LEVEL_DEBUG, $message, $context );
	}

	/**
	 * Delete log entries older than $days days.
	 */
	public function purge( int $days = 30 ): int {
		global $wpdb;

		$table   = $wpdb->prefix . 'flavor_flow_logs';
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < DATE_SUB( NOW(), INTERVAL %d DAY )",
				$days
			)
		);

		return (int) $deleted;
	}

	/**
	 * Query log entries.
	 *
	 * @param array $args {
	 *     Optional. Query parameters.
	 *     @type int    $workflow_id Filter by workflow ID.
	 *     @type string $status      Filter by status.
	 *     @type int    $per_page    Number of results.
	 *     @type int    $page        Page number (1-based).
	 *     @type string $orderby     Column to order by.
	 *     @type string $order       ASC or DESC.
	 * }
	 * @return array{ items: array, total: int }
	 */
	public function query( array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'workflow_id' => 0,
			'status'      => '',
			'per_page'    => 20,
			'page'        => 1,
			'orderby'     => 'created_at',
			'order'       => 'DESC',
		];

		$args  = wp_parse_args( $args, $defaults );
		$table = $wpdb->prefix . 'flavor_flow_logs';
		$where = [];
		$values = [];

		if ( $args['workflow_id'] > 0 ) {
			$where[]  = 'workflow_id = %d';
			$values[] = $args['workflow_id'];
		}

		if ( '' !== $args['status'] ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		$where_clause = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$allowed_orderby = [ 'id', 'workflow_id', 'status', 'created_at' ];
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$offset = max( 0, ( $args['page'] - 1 ) * $args['per_page'] );

		$count_sql = "SELECT COUNT(*) FROM {$table} {$where_clause}";
		$data_sql  = "SELECT * FROM {$table} {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$count_values = $values;
		$data_values  = array_merge( $values, [ $args['per_page'], $offset ] );

		if ( $count_values ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$count_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$items = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$data_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$items = $wpdb->get_results( $wpdb->prepare( $data_sql, $args['per_page'], $offset ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return [
			'items' => $items ?: [],
			'total' => $total,
		];
	}
}
