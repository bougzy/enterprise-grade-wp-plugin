<?php
/**
 * String comparison condition.
 *
 * @package FlavorFlow\Condition\Conditions
 */

declare(strict_types=1);

namespace flavor_flow\Condition\Conditions;

use flavor_flow\Condition\ConditionInterface;

final class StringCondition implements ConditionInterface {

	public function get_slug(): string {
		return 'string';
	}

	public function get_label(): string {
		return __( 'Text', 'flavor-flow' );
	}

	public function evaluate( mixed $actual_value, string $operator, mixed $expected_value ): bool {
		$actual   = (string) $actual_value;
		$expected = (string) $expected_value;

		return match ( $operator ) {
			'equals'         => $actual === $expected,
			'not_equals'     => $actual !== $expected,
			'contains'       => str_contains( $actual, $expected ),
			'not_contains'   => ! str_contains( $actual, $expected ),
			'starts_with'    => str_starts_with( $actual, $expected ),
			'ends_with'      => str_ends_with( $actual, $expected ),
			'is_empty'       => '' === $actual,
			'is_not_empty'   => '' !== $actual,
			default          => false,
		};
	}

	public function get_operators(): array {
		return [
			'equals'       => __( 'Equals', 'flavor-flow' ),
			'not_equals'   => __( 'Does not equal', 'flavor-flow' ),
			'contains'     => __( 'Contains', 'flavor-flow' ),
			'not_contains' => __( 'Does not contain', 'flavor-flow' ),
			'starts_with'  => __( 'Starts with', 'flavor-flow' ),
			'ends_with'    => __( 'Ends with', 'flavor-flow' ),
			'is_empty'     => __( 'Is empty', 'flavor-flow' ),
			'is_not_empty' => __( 'Is not empty', 'flavor-flow' ),
		];
	}
}
