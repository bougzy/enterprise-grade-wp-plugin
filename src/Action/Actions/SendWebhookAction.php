<?php
/**
 * Action: Send Webhook.
 *
 * @package FlavorFlow\Action\Actions
 */

declare(strict_types=1);

namespace flavor_flow\Action\Actions;

use flavor_flow\Action\ActionInterface;
use flavor_flow\Action\ActionResult;

final class SendWebhookAction implements ActionInterface {

	public function get_slug(): string {
		return 'send_webhook';
	}

	public function get_label(): string {
		return __( 'Send Webhook', 'flavor-flow' );
	}

	public function get_group(): string {
		return __( 'Integration', 'flavor-flow' );
	}

	public function execute( array $config, array $payload ): ActionResult {
		$url     = esc_url_raw( $config['url'] ?? '' );
		$method  = strtoupper( $config['method'] ?? 'POST' );
		$headers = $config['headers'] ?? [];

		if ( empty( $url ) ) {
			return ActionResult::failure( 'Webhook URL is empty.' );
		}

		$settings = get_option( 'flavor_flow_settings', [] );
		$timeout  = (int) ( $settings['webhook_timeout'] ?? 15 );

		$args = [
			'method'    => in_array( $method, [ 'POST', 'PUT', 'PATCH', 'GET', 'DELETE' ], true ) ? $method : 'POST',
			'timeout'   => min( $timeout, 30 ),
			'headers'   => array_merge(
				[ 'Content-Type' => 'application/json' ],
				$headers
			),
			'body'      => wp_json_encode( [
				'event'   => $config['event_name'] ?? 'flavor_flow_event',
				'payload' => $payload,
			] ),
			'sslverify' => true,
		];

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return ActionResult::failure(
				sprintf( 'Webhook failed: %s', $response->get_error_message() )
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code >= 200 && $code < 300 ) {
			return ActionResult::success(
				sprintf( 'Webhook sent. Status: %d', $code ),
				[ 'response_code' => $code, 'response_body' => $body ]
			);
		}

		return ActionResult::failure(
			sprintf( 'Webhook returned status %d.', $code ),
			[ 'response_code' => $code, 'response_body' => $body ]
		);
	}

	public function get_config_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [ 'url' ],
			'properties' => [
				'url'        => [
					'type'   => 'string',
					'format' => 'uri',
				],
				'method'     => [
					'type'    => 'string',
					'enum'    => [ 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ],
					'default' => 'POST',
				],
				'headers'    => [
					'type'                 => 'object',
					'additionalProperties' => [ 'type' => 'string' ],
				],
				'event_name' => [
					'type'    => 'string',
					'default' => 'flavor_flow_event',
				],
			],
		];
	}
}
