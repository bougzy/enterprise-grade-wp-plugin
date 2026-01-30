<?php
/**
 * License key management.
 *
 * @package FlavorFlow\License
 */

declare(strict_types=1);

namespace flavor_flow\License;

/**
 * Handles license activation, deactivation, and validation.
 *
 * Designed to work with an external license server (e.g. EDD, WooCommerce SL, custom).
 */
final class LicenseManager {

	private const API_URL = 'https://api.flavancio.io/license/';

	/**
	 * Whether the current installation has an active Pro license.
	 */
	public function is_pro(): bool {
		$settings = get_option( 'flavor_flow_settings', [] );
		return 'active' === ( $settings['license_status'] ?? 'inactive' );
	}

	/**
	 * Activate a license key against the remote server.
	 *
	 * @param string $key License key.
	 * @return array{ success: bool, message: string }
	 */
	public function activate( string $key ): array {
		$response = $this->api_request( 'activate', $key );

		if ( $response['success'] ) {
			$this->update_status( $key, 'active' );
		}

		return $response;
	}

	/**
	 * Deactivate the current license.
	 */
	public function deactivate(): array {
		$settings = get_option( 'flavor_flow_settings', [] );
		$key      = $settings['license_key'] ?? '';

		$response = $this->api_request( 'deactivate', $key );
		$this->update_status( '', 'inactive' );

		return $response;
	}

	/**
	 * Validate the stored license.
	 */
	public function validate(): array {
		$settings = get_option( 'flavor_flow_settings', [] );
		$key      = $settings['license_key'] ?? '';

		if ( empty( $key ) ) {
			return [ 'success' => false, 'message' => __( 'No license key.', 'flavor-flow' ) ];
		}

		$response = $this->api_request( 'validate', $key );

		if ( ! $response['success'] ) {
			$this->update_status( $key, 'invalid' );
		}

		return $response;
	}

	/**
	 * Make an API request to the license server.
	 *
	 * @param string $action Action (activate, deactivate, validate).
	 * @param string $key    License key.
	 * @return array{ success: bool, message: string }
	 */
	private function api_request( string $action, string $key ): array {
		$response = wp_remote_post( self::API_URL, [
			'timeout' => 15,
			'body'    => [
				'action'      => $action,
				'license_key' => $key,
				'site_url'    => home_url(),
				'plugin'      => 'flavor-flow',
				'version'     => FLAVOR_FLOW_VERSION,
			],
		] );

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => $response->get_error_message(),
			];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return [
			'success' => ! empty( $body['success'] ),
			'message' => $body['message'] ?? __( 'Unknown response.', 'flavor-flow' ),
		];
	}

	/**
	 * Persist license status.
	 */
	private function update_status( string $key, string $status ): void {
		$settings                   = get_option( 'flavor_flow_settings', [] );
		$settings['license_key']    = sanitize_text_field( $key );
		$settings['license_status'] = sanitize_key( $status );
		update_option( 'flavor_flow_settings', $settings );
	}
}
