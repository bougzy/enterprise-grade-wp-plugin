<?php
/**
 * Plugin Name:       FlavorFlow – Workflow Automation Engine
 * Plugin URI:        https://flavancio.io/flavor-flow
 * Description:       Enterprise-grade workflow automation for WordPress. Define triggers, conditions, and actions to automate any business process.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Flavancio Engineering
 * Author URI:        https://flavancio.io
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       flavor-flow
 * Domain Path:       /languages
 * Network:           false
 *
 * @package FlavorFlow
 */

declare(strict_types=1);

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'FLAVOR_FLOW_VERSION', '1.0.0' );
define( 'FLAVOR_FLOW_MINIMUM_WP', '6.0' );
define( 'FLAVOR_FLOW_MINIMUM_PHP', '8.0' );

define( 'FLAVOR_FLOW_FILE', __FILE__ );
define( 'FLAVOR_FLOW_DIR', plugin_dir_path( __FILE__ ) );
define( 'FLAVOR_FLOW_URL', plugin_dir_url( __FILE__ ) );
define( 'FLAVOR_FLOW_BASENAME', plugin_basename( __FILE__ ) );

// Load Composer autoloader.
if ( file_exists( FLAVOR_FLOW_DIR . 'vendor/autoload.php' ) ) {
	require_once FLAVOR_FLOW_DIR . 'vendor/autoload.php';
}

/**
 * Returns the main plugin instance, creating it on first call.
 *
 * @return \flavor_flow\Core\Plugin
 */
function flavor_flow(): \flavor_flow\Core\Plugin {
	static $instance = null;

	if ( null === $instance ) {
		$instance = new \flavor_flow\Core\Plugin();
	}

	return $instance;
}

/**
 * Activation hook — runs migrations and sets default options.
 */
function flavor_flow_activate(): void {
	$activator = new \flavor_flow\Core\Activator();
	$activator->activate();
}

/**
 * Deactivation hook — cleans up scheduled events.
 */
function flavor_flow_deactivate(): void {
	$deactivator = new \flavor_flow\Core\Deactivator();
	$deactivator->deactivate();
}

register_activation_hook( __FILE__, 'flavor_flow_activate' );
register_deactivation_hook( __FILE__, 'flavor_flow_deactivate' );

// Boot the plugin after all plugins are loaded.
add_action( 'plugins_loaded', static function (): void {
	flavor_flow()->boot();
}, 10 );
