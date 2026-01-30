<?php
/**
 * Database migration manager.
 *
 * @package FlavorFlow\Database
 */

declare(strict_types=1);

namespace flavor_flow\Database;

/**
 * Creates and drops custom database tables.
 */
final class Migrator {

	private const DB_VERSION = '1.0.0';

	/**
	 * Run all up-migrations.
	 */
	public function up(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Execution log table.
		$table_log = $wpdb->prefix . 'flavor_flow_logs';
		$sql_log   = "CREATE TABLE {$table_log} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			workflow_id bigint(20) unsigned NOT NULL DEFAULT 0,
			trigger_name varchar(191) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'pending',
			message text NOT NULL,
			context longtext NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY workflow_id (workflow_id),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql_log );

		// Queue table for async job processing.
		$table_queue = $wpdb->prefix . 'flavor_flow_queue';
		$sql_queue   = "CREATE TABLE {$table_queue} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			workflow_id bigint(20) unsigned NOT NULL DEFAULT 0,
			payload longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
			max_attempts tinyint(3) unsigned NOT NULL DEFAULT 3,
			scheduled_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			started_at datetime DEFAULT NULL,
			completed_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status_scheduled (status, scheduled_at),
			KEY workflow_id (workflow_id)
		) {$charset_collate};";

		dbDelta( $sql_queue );

		update_option( 'flavor_flow_db_version', self::DB_VERSION );
	}

	/**
	 * Drop all custom tables (used on uninstall).
	 */
	public function down(): void {
		global $wpdb;

		$tables = [
			$wpdb->prefix . 'flavor_flow_logs',
			$wpdb->prefix . 'flavor_flow_queue',
		];

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		delete_option( 'flavor_flow_db_version' );
	}
}
