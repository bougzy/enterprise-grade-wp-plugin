<?php
/**
 * Numeric comparison condition.
 *
 * @package FlavorFlow\Condition\Conditions
 */

declare(strict_types=1);

namespace flavor_flow\Condition\Conditions;

use flavor_flow\Condition\ConditionInterface;

final class NumericCondition implements ConditionInterface {

	public function get_slug(): string {
		return 'numeric';
	}

	public function get_label(): string {
		return __( 'Number', 'flavor-flow' );
	}

	public function evaluate( $actual_value, string $operator, $expected_value ): bool {
		$actual   = (float) $actual_value;
		$expected = (float) $expected_value;

		switch ( $operator ) {
			case 'equals':
				return abs( $actual - $expected ) < PHP_FLOAT_EPSILON;
			case 'not_equals':
				return abs( $actual - $expected ) >= PHP_FLOAT_EPSILON;
			case 'greater_than':
				return $actual > $expected;
			case 'less_than':
				return $actual < $expected;
			case 'greater_than_equal':
				return $actual >= $expected;
			case 'less_than_equal':
				return $actual <= $expected;
			default:
				return false;
		}
	}

	public function get_operators(): array {
		return [
			'equals'             => __( 'Equals', 'flavor-flow' ),
			'not_equals'         => __( 'Does not equal', 'flavor-flow' ),
			'greater_than'       => __( 'Greater than', 'flavor-flow' ),
			'less_than'          => __( 'Less than', 'flavor-flow' ),
			'greater_than_equal' => __( 'Greater than or equal', 'flavor-flow' ),
			'less_than_equal'    => __( 'Less than or equal', 'flavor-flow' ),
		];
	}
}
