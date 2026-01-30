<?php
/**
 * Admin page registration and rendering.
 *
 * @package FlavorFlow\Admin
 */

declare(strict_types=1);

namespace flavor_flow\Admin;

/**
 * Registers the top-level admin page and sub-pages.
 */
final class AdminPage {

	/**
	 * Hook into WP Admin.
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Register menu items.
	 */
	public function add_menu_pages(): void {
		// Top-level page (React SPA).
		add_menu_page(
			__( 'FlavorFlow', 'flavor-flow' ),
			__( 'FlavorFlow', 'flavor-flow' ),
			'edit_ff_workflows',
			'flavor-flow',
			[ $this, 'render_app' ],
			'dashicons-randomize',
			58
		);

		// Sub-pages.
		add_submenu_page(
			'flavor-flow',
			__( 'Workflows', 'flavor-flow' ),
			__( 'Workflows', 'flavor-flow' ),
			'edit_ff_workflows',
			'flavor-flow',
			[ $this, 'render_app' ]
		);

		add_submenu_page(
			'flavor-flow',
			__( 'Logs', 'flavor-flow' ),
			__( 'Logs', 'flavor-flow' ),
			'manage_options',
			'flavor-flow-logs',
			[ $this, 'render_app' ]
		);

		add_submenu_page(
			'flavor-flow',
			__( 'Settings', 'flavor-flow' ),
			__( 'Settings', 'flavor-flow' ),
			'manage_options',
			'flavor-flow-settings',
			[ $this, 'render_app' ]
		);

		add_submenu_page(
			'flavor-flow',
			__( 'License', 'flavor-flow' ),
			__( 'License', 'flavor-flow' ),
			'manage_options',
			'flavor-flow-license',
			[ $this, 'render_app' ]
		);
	}

	/**
	 * Render the React application mount point.
	 */
	public function render_app(): void {
		echo '<div id="flavor-flow-app" class="wrap"></div>';
	}

	/**
	 * Enqueue admin CSS and JS on our pages only.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		$our_pages = [
			'toplevel_page_flavor-flow',
			'flavorflow_page_flavor-flow-logs',
			'flavorflow_page_flavor-flow-settings',
			'flavorflow_page_flavor-flow-license',
		];

		if ( ! in_array( $hook_suffix, $our_pages, true ) ) {
			return;
		}

		$asset_file = FLAVOR_FLOW_DIR . 'assets/js/admin/index.asset.php';

		if ( file_exists( $asset_file ) ) {
			$asset = require $asset_file;
		} else {
			$asset = [
				'dependencies' => [ 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ],
				'version'      => FLAVOR_FLOW_VERSION,
			];
		}

		wp_enqueue_script(
			'flavor-flow-admin',
			FLAVOR_FLOW_URL . 'assets/js/admin/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations( 'flavor-flow-admin', 'flavor-flow', FLAVOR_FLOW_DIR . 'languages' );

		wp_localize_script( 'flavor-flow-admin', 'flavorFlowAdmin', [
			'restBase' => esc_url_raw( rest_url( 'flavor-flow/v1' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'page'     => sanitize_key( $_GET['page'] ?? 'flavor-flow' ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'version'  => FLAVOR_FLOW_VERSION,
		] );

		wp_enqueue_style(
			'flavor-flow-admin',
			FLAVOR_FLOW_URL . 'assets/css/admin/style.css',
			[ 'wp-components' ],
			FLAVOR_FLOW_VERSION
		);
	}
}
