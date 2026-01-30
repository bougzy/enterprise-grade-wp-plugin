<?php
/**
 * Trigger contract.
 *
 * @package FlavorFlow\Trigger
 */

declare(strict_types=1);

namespace flavor_flow\Trigger;

/**
 * Every trigger must implement this interface.
 */
interface TriggerInterface {

	/**
	 * Unique machine-readable slug (e.g. "post_published").
	 */
	public function get_slug(): string;

	/**
	 * Human-readable label for the admin UI.
	 */
	public function get_label(): string;

	/**
	 * Group / category label (e.g. "Posts", "Users", "WooCommerce").
	 */
	public function get_group(): string;

	/**
	 * Register the WordPress hooks that fire this trigger.
	 */
	public function listen(): void;

	/**
	 * Return JSON-Schema array describing the payload this trigger produces.
	 *
	 * @return array<string, mixed>
	 */
	public function get_payload_schema(): array;
}
