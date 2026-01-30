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

	public function evaluate( mixed $actual_value, string $operator, mixed $expected_value ): bool {
		$actual   = (float) $actual_value;
		$expected = (float) $expected_value;

		return match ( $operator ) {
			'equals'              => abs( $actual - $expected ) < PHP_FLOAT_EPSILON,
			'not_equals'          => abs( $actual - $expected ) >= PHP_FLOAT_EPSILON,
			'greater_than'        => $actual > $expected,
			'less_than'           => $actual < $expected,
			'greater_than_equal'  => $actual >= $expected,
			'less_than_equal'     => $actual <= $expected,
			default               => false,
		};
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
