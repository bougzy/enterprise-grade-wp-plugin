<?php
/**
 * Trigger: WooCommerce Order Status Changed.
 *
 * @package FlavorFlow\Trigger\Triggers
 */

declare(strict_types=1);

namespace flavor_flow\Trigger\Triggers;

use flavor_flow\Trigger\AbstractTrigger;

/**
 * Fires when a WooCommerce order status transitions.
 * Only active when WooCommerce is installed.
 */
final class WooCommerceOrderTrigger extends AbstractTrigger {

	public function get_slug(): string {
		return 'woo_order_status_changed';
	}

	public function get_label(): string {
		return __( 'Order Status Changed', 'flavor-flow' );
	}

	public function get_group(): string {
		return __( 'WooCommerce', 'flavor-flow' );
	}

	public function listen(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'woocommerce_order_status_changed', [ $this, 'handle' ], 10, 4 );
	}

	/**
	 * @param int    $order_id   Order ID.
	 * @param string $old_status Previous status.
	 * @param string $new_status New status.
	 * @param object $order      WC_Order instance.
	 */
	public function handle( int $order_id, string $old_status, string $new_status, object $order ): void {
		$this->dispatch( [
			'order_id'     => $order_id,
			'old_status'   => $old_status,
			'new_status'   => $new_status,
			'customer_id'  => method_exists( $order, 'get_customer_id' ) ? $order->get_customer_id() : 0,
			'order_total'  => method_exists( $order, 'get_total' ) ? $order->get_total() : '0.00',
			'billing_email'=> method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : '',
		] );
	}

	public function get_payload_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'order_id'      => [ 'type' => 'integer' ],
				'old_status'    => [ 'type' => 'string' ],
				'new_status'    => [ 'type' => 'string' ],
				'customer_id'   => [ 'type' => 'integer' ],
				'order_total'   => [ 'type' => 'string' ],
				'billing_email' => [ 'type' => 'string', 'format' => 'email' ],
			],
		];
	}
}
