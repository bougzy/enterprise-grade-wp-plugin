<?php
/**
 * License service provider.
 *
 * @package FlavorFlow\License
 */

declare(strict_types=1);

namespace flavor_flow\License;

use flavor_flow\Core\ServiceProvider;

final class LicenseServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->set(
			LicenseManager::class,
			static fn() => new LicenseManager()
		);
	}

	public function boot(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );

		// Weekly license re-validation.
		add_action( 'flavor_flow_validate_license', [ $this, 'handle_validation' ] );

		if ( ! wp_next_scheduled( 'flavor_flow_validate_license' ) ) {
			wp_schedule_event( time(), 'weekly', 'flavor_flow_validate_license' );
		}
	}

	/**
	 * Register license REST endpoints.
	 */
	public function register_routes(): void {
		register_rest_route( 'flavor-flow/v1', '/license/activate', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'rest_activate' ],
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
				'args'                => [
					'license_key' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			],
		] );

		register_rest_route( 'flavor-flow/v1', '/license/deactivate', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'rest_deactivate' ],
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
			],
		] );

		register_rest_route( 'flavor-flow/v1', '/license/status', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_status' ],
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
			],
		] );
	}

	public function rest_activate( \WP_REST_Request $request ): \WP_REST_Response {
		/** @var LicenseManager $manager */
		$manager = $this->container->get( LicenseManager::class );

		$result = $manager->activate( $request->get_param( 'license_key' ) );

		return new \WP_REST_Response( $result, $result['success'] ? 200 : 400 );
	}

	public function rest_deactivate(): \WP_REST_Response {
		/** @var LicenseManager $manager */
		$manager = $this->container->get( LicenseManager::class );

		$result = $manager->deactivate();

		return new \WP_REST_Response( $result );
	}

	public function rest_status(): \WP_REST_Response {
		/** @var LicenseManager $manager */
		$manager = $this->container->get( LicenseManager::class );

		$settings = get_option( 'flavor_flow_settings', [] );

		return new \WP_REST_Response( [
			'is_pro'  => $manager->is_pro(),
			'status'  => $settings['license_status'] ?? 'inactive',
		] );
	}

	public function handle_validation(): void {
		/** @var LicenseManager $manager */
		$manager = $this->container->get( LicenseManager::class );
		$manager->validate();
	}
}
