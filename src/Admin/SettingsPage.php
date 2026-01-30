<?php
/**
 * Settings page using the WordPress Settings API.
 *
 * @package FlavorFlow\Admin
 */

declare(strict_types=1);

namespace flavor_flow\Admin;

/**
 * Registers settings fields and handles sanitization.
 *
 * Settings are also exposed through REST for the React UI.
 */
final class SettingsPage {

	public const OPTION_KEY = 'flavor_flow_settings';

	public function register(): void {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_settings' ] );
	}

	/**
	 * Register settings with the Settings API.
	 */
	public function register_settings(): void {
		register_setting( 'flavor_flow_settings_group', self::OPTION_KEY, [
			'type'              => 'object',
			'sanitize_callback' => [ $this, 'sanitize' ],
			'default'           => $this->defaults(),
		] );
	}

	/**
	 * Expose settings via REST API so the React UI can read/write them.
	 */
	public function register_rest_settings(): void {
		register_rest_route( 'flavor-flow/v1', '/settings', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_get_settings' ],
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
			],
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'rest_update_settings' ],
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
			],
		] );
	}

	public function rest_get_settings(): \WP_REST_Response {
		$settings = wp_parse_args( get_option( self::OPTION_KEY, [] ), $this->defaults() );
		// Never expose the full license key over REST.
		$settings['license_key'] = $this->mask_key( $settings['license_key'] ?? '' );

		return new \WP_REST_Response( $settings );
	}

	public function rest_update_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$input    = $request->get_json_params();
		$current  = get_option( self::OPTION_KEY, [] );
		$merged   = array_merge( $current, $input );
		$sanitized = $this->sanitize( $merged );

		update_option( self::OPTION_KEY, $sanitized );

		return $this->rest_get_settings();
	}

	/**
	 * Sanitize settings.
	 */
	public function sanitize( array $input ): array {
		return [
			'enable_logging'  => ! empty( $input['enable_logging'] ),
			'log_retention'   => absint( $input['log_retention'] ?? 30 ),
			'execution_mode'  => in_array( $input['execution_mode'] ?? '', [ 'sync', 'async' ], true )
				? $input['execution_mode']
				: 'async',
			'max_retries'     => min( absint( $input['max_retries'] ?? 3 ), 10 ),
			'webhook_timeout' => min( absint( $input['webhook_timeout'] ?? 15 ), 30 ),
			'license_key'     => sanitize_text_field( $input['license_key'] ?? '' ),
			'license_status'  => sanitize_key( $input['license_status'] ?? 'inactive' ),
		];
	}

	/**
	 * Default option values.
	 */
	private function defaults(): array {
		return [
			'enable_logging'  => true,
			'log_retention'   => 30,
			'execution_mode'  => 'async',
			'max_retries'     => 3,
			'webhook_timeout' => 15,
			'license_key'     => '',
			'license_status'  => 'inactive',
		];
	}

	/**
	 * Mask a license key for safe display.
	 */
	private function mask_key( string $key ): string {
		if ( strlen( $key ) <= 8 ) {
			return str_repeat( '*', strlen( $key ) );
		}

		return substr( $key, 0, 4 ) . str_repeat( '*', strlen( $key ) - 8 ) . substr( $key, -4 );
	}
}
