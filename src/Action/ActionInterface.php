<?php
/**
 * Action contract.
 *
 * @package FlavorFlow\Action
 */

declare(strict_types=1);

namespace flavor_flow\Action;

/**
 * An action that can be executed by the workflow engine.
 */
interface ActionInterface {

	/**
	 * Unique machine-readable slug.
	 */
	public function get_slug(): string;

	/**
	 * Human-readable label.
	 */
	public function get_label(): string;

	/**
	 * Group label (e.g. "Communication", "Data").
	 */
	public function get_group(): string;

	/**
	 * Execute the action.
	 *
	 * @param array $config  Action-specific configuration from the workflow.
	 * @param array $payload Trigger payload (with variable replacements available).
	 * @return ActionResult
	 */
	public function execute( array $config, array $payload ): ActionResult;

	/**
	 * Return JSON-Schema for the action's configuration fields.
	 *
	 * @return array<string, mixed>
	 */
	public function get_config_schema(): array;
}
