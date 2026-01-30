<?php
/**
 * REST controller for execution logs.
 *
 * @package FlavorFlow\API
 */

declare(strict_types=1);

namespace flavor_flow\API;

use flavor_flow\Logging\Logger;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

final class LogController extends WP_REST_Controller {

	protected $namespace = 'flavor-flow/v1';
	protected $rest_base = 'logs';

	private Logger $logger;

	public function __construct( Logger $logger ) {
		$this->logger = $logger;
	}

	public function register_routes(): void {
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'workflow_id' => [
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'status'      => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
					'per_page'    => [
						'type'              => 'integer',
						'default'           => 20,
						'sanitize_callback' => 'absint',
					],
					'page'        => [
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					],
				],
			],
		] );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/purge', [
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'purge' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			],
		] );
	}

	public function permissions_check( $request ): bool {
		return current_user_can( 'manage_options' );
	}

	public function get_items( $request ): WP_REST_Response {
		$result = $this->logger->query( [
			'workflow_id' => (int) $request->get_param( 'workflow_id' ),
			'status'      => (string) $request->get_param( 'status' ),
			'per_page'    => (int) $request->get_param( 'per_page' ),
			'page'        => (int) $request->get_param( 'page' ),
		] );

		$response = new WP_REST_Response( $result['items'] );
		$response->header( 'X-WP-Total', (string) $result['total'] );

		return $response;
	}

	public function purge( WP_REST_Request $request ): WP_REST_Response {
		$deleted = $this->logger->purge( 0 ); // purge all

		return new WP_REST_Response( [
			'deleted' => $deleted,
		] );
	}
}
