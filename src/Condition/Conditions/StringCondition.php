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

	public function evaluate( $actual_value, string $operator, $expected_value ): bool {
		$actual   = (string) $actual_value;
		$expected = (string) $expected_value;

		switch ( $operator ) {
			case 'equals':
				return $actual === $expected;
			case 'not_equals':
				return $actual !== $expected;
			case 'contains':
				return false !== strpos( $actual, $expected );
			case 'not_contains':
				return false === strpos( $actual, $expected );
			case 'starts_with':
				return 0 === strpos( $actual, $expected );
			case 'ends_with':
				return substr( $actual, -strlen( $expected ) ) === $expected;
			case 'is_empty':
				return '' === $actual;
			case 'is_not_empty':
				return '' !== $actual;
			default:
				return false;
		}
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
