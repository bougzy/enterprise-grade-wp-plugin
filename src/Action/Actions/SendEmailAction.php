<?php
/**
 * Action: Send Email.
 *
 * @package FlavorFlow\Action\Actions
 */

declare(strict_types=1);

namespace flavor_flow\Action\Actions;

use flavor_flow\Action\ActionInterface;
use flavor_flow\Action\ActionResult;

final class SendEmailAction implements ActionInterface {

	public function get_slug(): string {
		return 'send_email';
	}

	public function get_label(): string {
		return __( 'Send Email', 'flavor-flow' );
	}

	public function get_group(): string {
		return __( 'Communication', 'flavor-flow' );
	}

	public function execute( array $config, array $payload ): ActionResult {
		$to      = $this->interpolate( $config['to'] ?? '', $payload );
		$subject = $this->interpolate( $config['subject'] ?? '', $payload );
		$body    = $this->interpolate( $config['body'] ?? '', $payload );
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		if ( ! is_email( $to ) ) {
			return ActionResult::failure(
				sprintf( 'Invalid email address: %s', $to )
			);
		}

		$sent = wp_mail( $to, $subject, $body, $headers );

		if ( ! $sent ) {
			return ActionResult::failure( 'wp_mail() returned false.' );
		}

		return ActionResult::success( sprintf( 'Email sent to %s.', $to ) );
	}

	public function get_config_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [ 'to', 'subject', 'body' ],
			'properties' => [
				'to'      => [
					'type'        => 'string',
					'description' => 'Recipient email. Supports {{payload.field}} placeholders.',
				],
				'subject' => [
					'type'        => 'string',
					'description' => 'Email subject line.',
				],
				'body'    => [
					'type'        => 'string',
					'description' => 'Email body (HTML supported).',
				],
			],
		];
	}

	/**
	 * Replace {{field}} placeholders with payload values.
	 */
	private function interpolate( string $template, array $payload ): string {
		return preg_replace_callback(
			'/\{\{\s*([\w.]+)\s*\}\}/',
			static function ( array $matches ) use ( $payload ): string {
				$keys   = explode( '.', $matches[1] );
				$value  = $payload;

				foreach ( $keys as $key ) {
					if ( is_array( $value ) && array_key_exists( $key, $value ) ) {
						$value = $value[ $key ];
					} else {
						return $matches[0]; // Leave placeholder as-is if unresolved.
					}
				}

				return is_scalar( $value ) ? (string) $value : wp_json_encode( $value );
			},
			$template
		) ?? $template;
	}
}
