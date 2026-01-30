<?php
/**
 * Condition contract.
 *
 * @package FlavorFlow\Condition
 */

declare(strict_types=1);

namespace flavor_flow\Condition;

/**
 * A condition evaluates a single rule against the trigger payload.
 */
interface ConditionInterface {

	/**
	 * Unique machine-readable slug.
	 */
	public function get_slug(): string;

	/**
	 * Human-readable label.
	 */
	public function get_label(): string;

	/**
	 * Evaluate the condition.
	 *
	 * @param mixed  $actual_value   Value from the payload.
	 * @param string $operator       Comparison operator.
	 * @param mixed  $expected_value Value set by the user in the workflow config.
	 * @return bool
	 */
	/**
	 * @param mixed $actual_value
	 * @param mixed $expected_value
	 */
	public function evaluate( $actual_value, string $operator, $expected_value ): bool;

	/**
	 * Available operators for this condition type.
	 *
	 * @return array<string, string> slug => label.
	 */
	public function get_operators(): array;
}
