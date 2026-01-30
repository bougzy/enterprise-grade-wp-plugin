<?php
/**
 * Uninstall FlavorFlow.
 *
 * Fired when the plugin is deleted via WP Admin.
 *
 * @package FlavorFlow
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

( new \flavor_flow\Core\Uninstaller() )->uninstall();
