<?php
/**
 * REST controller for inbound webhooks.
 *
 * @package FlavorFlow\API
 */

declare(strict_types=1);

namespace flavor_flow\API;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Accepts external webhook calls and dispatches them as triggers.
 *
 * Route: POST /flavor-flow/v1/webhook/{token}
 */
final class WebhookIngressController extends WP_REST_Controller {

	protected $namespace = 'flavor-flow/v1';
	protected $rest_base = 'webhook';

	public function register_routes(): void {
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<token>[a-zA-Z0-9_-]{32,64})', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_webhook' ],
				'permission_callback' => '__return_true', // Token-based auth.
			],
		] );
	}

	/**
	 * Validate the token and dispatch the payload as a trigger.
	 */
	public function handle_webhook( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$token   = sanitize_text_field( $request->get_param( 'token' ) );
		$payload = $request->get_json_params();

		// Look up workflow with this webhook token.
		$query = new \WP_Query( [
			'post_type'      => 'ff_workflow',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'   => '_ff_webhook_token',
					'value' => $token,
				],
			],
		] );

		if ( empty( $query->posts ) ) {
			return new WP_Error(
				'invalid_token',
				__( 'Invalid webhook token.', 'flavor-flow' ),
				[ 'status' => 403 ]
			);
		}

		/**
		 * Dispatch as an inbound_webhook trigger.
		 */
		do_action( 'flavor_flow_trigger_dispatched', 'inbound_webhook', [
			'webhook_token' => $token,
			'payload'       => $payload,
		] );

		return new WP_REST_Response( [ 'received' => true ], 202 );
	}
}
