<?php
/**
 * Condition evaluator engine.
 *
 * @package FlavorFlow\Condition
 */

declare(strict_types=1);

namespace flavor_flow\Condition;

/**
 * Evaluates a set of condition rules against an event payload.
 *
 * Rules are stored as arrays:
 * [
 *   {
 *     "field":    "post_type",
 *     "type":     "string",
 *     "operator": "equals",
 *     "value":    "post"
 *   }
 * ]
 *
 * Groups support AND / OR logic:
 * {
 *   "logic": "AND",
 *   "rules": [ ... ]
 * }
 */
final class ConditionEvaluator {

	/** @var array<string, ConditionInterface> */
	private array $conditions = [];

	/**
	 * Register a condition type.
	 */
	public function add_condition( ConditionInterface $condition ): void {
		$this->conditions[ $condition->get_slug() ] = $condition;
	}

	/**
	 * Get all registered conditions.
	 *
	 * @return ConditionInterface[]
	 */
	public function get_conditions(): array {
		return $this->conditions;
	}

	/**
	 * Evaluate a condition group against a payload.
	 *
	 * @param array $group   Condition group with "logic" and "rules".
	 * @param array $payload Trigger payload.
	 * @return bool
	 */
	public function evaluate( array $group, array $payload ): bool {
		$logic = strtoupper( $group['logic'] ?? 'AND' );
		$rules = $group['rules'] ?? [];

		if ( empty( $rules ) ) {
			return true;
		}

		foreach ( $rules as $rule ) {
			// Nested group.
			if ( isset( $rule['logic'], $rule['rules'] ) ) {
				$result = $this->evaluate( $rule, $payload );
			} else {
				$result = $this->evaluate_rule( $rule, $payload );
			}

			if ( 'AND' === $logic && ! $result ) {
				return false;
			}

			if ( 'OR' === $logic && $result ) {
				return true;
			}
		}

		return 'AND' === $logic;
	}

	/**
	 * Evaluate a single rule.
	 */
	private function evaluate_rule( array $rule, array $payload ): bool {
		$field    = $rule['field'] ?? '';
		$type     = $rule['type'] ?? 'string';
		$operator = $rule['operator'] ?? 'equals';
		$value    = $rule['value'] ?? '';

		$actual = $this->resolve_field( $field, $payload );

		$condition = $this->conditions[ $type ] ?? null;

		if ( ! $condition ) {
			return false;
		}

		return $condition->evaluate( $actual, $operator, $value );
	}

	/**
	 * Resolve a dot-notation field from the payload.
	 *
	 * Supports paths like "post.author_id" or simple "post_type".
	 */
	/**
	 * @return mixed
	 */
	private function resolve_field( string $field, array $payload ) {
		$keys    = explode( '.', $field );
		$current = $payload;

		foreach ( $keys as $key ) {
			if ( is_array( $current ) && array_key_exists( $key, $current ) ) {
				$current = $current[ $key ];
			} else {
				return null;
			}
		}

		return $current;
	}
}
