<?php
/**
 * Condition service provider.
 *
 * @package FlavorFlow\Condition
 */

declare(strict_types=1);

namespace flavor_flow\Condition;

use flavor_flow\Condition\Conditions\NumericCondition;
use flavor_flow\Condition\Conditions\StringCondition;
use flavor_flow\Core\ServiceProvider;

final class ConditionServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->set(
			ConditionEvaluator::class,
			static function (): ConditionEvaluator {
				$evaluator = new ConditionEvaluator();

				$types = [
					new StringCondition(),
					new NumericCondition(),
				];

				/**
				 * Filters available condition types.
				 *
				 * @param ConditionInterface[] $types Condition type instances.
				 */
				$types = apply_filters( 'flavor_flow_condition_types', $types );

				foreach ( $types as $type ) {
					$evaluator->add_condition( $type );
				}

				return $evaluator;
			}
		);
	}
}
