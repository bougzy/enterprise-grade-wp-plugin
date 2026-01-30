<?php
/**
 * Unit tests for the Condition Evaluator.
 *
 * @package FlavorFlow\Tests\Unit
 */

declare(strict_types=1);

namespace flavor_flow\Tests\Unit;

use flavor_flow\Condition\ConditionEvaluator;
use flavor_flow\Condition\Conditions\NumericCondition;
use flavor_flow\Condition\Conditions\StringCondition;
use PHPUnit\Framework\TestCase;

final class ConditionEvaluatorTest extends TestCase {

	private ConditionEvaluator $evaluator;

	protected function setUp(): void {
		$this->evaluator = new ConditionEvaluator();
		$this->evaluator->add_condition( new StringCondition() );
		$this->evaluator->add_condition( new NumericCondition() );
	}

	public function test_empty_rules_return_true(): void {
		$group   = [ 'logic' => 'AND', 'rules' => [] ];
		$payload = [ 'post_type' => 'post' ];

		$this->assertTrue( $this->evaluator->evaluate( $group, $payload ) );
	}

	public function test_string_equals(): void {
		$group = [
			'logic' => 'AND',
			'rules' => [
				[ 'field' => 'post_type', 'type' => 'string', 'operator' => 'equals', 'value' => 'post' ],
			],
		];

		$this->assertTrue( $this->evaluator->evaluate( $group, [ 'post_type' => 'post' ] ) );
		$this->assertFalse( $this->evaluator->evaluate( $group, [ 'post_type' => 'page' ] ) );
	}

	public function test_string_contains(): void {
		$group = [
			'logic' => 'AND',
			'rules' => [
				[ 'field' => 'post_title', 'type' => 'string', 'operator' => 'contains', 'value' => 'Hello' ],
			],
		];

		$this->assertTrue( $this->evaluator->evaluate( $group, [ 'post_title' => 'Hello World' ] ) );
		$this->assertFalse( $this->evaluator->evaluate( $group, [ 'post_title' => 'Goodbye' ] ) );
	}

	public function test_numeric_greater_than(): void {
		$group = [
			'logic' => 'AND',
			'rules' => [
				[ 'field' => 'author_id', 'type' => 'numeric', 'operator' => 'greater_than', 'value' => '5' ],
			],
		];

		$this->assertTrue( $this->evaluator->evaluate( $group, [ 'author_id' => 10 ] ) );
		$this->assertFalse( $this->evaluator->evaluate( $group, [ 'author_id' => 3 ] ) );
	}

	public function test_and_logic(): void {
		$group = [
			'logic' => 'AND',
			'rules' => [
				[ 'field' => 'post_type', 'type' => 'string', 'operator' => 'equals', 'value' => 'post' ],
				[ 'field' => 'author_id', 'type' => 'numeric', 'operator' => 'equals', 'value' => '1' ],
			],
		];

		$this->assertTrue( $this->evaluator->evaluate( $group, [ 'post_type' => 'post', 'author_id' => 1 ] ) );
		$this->assertFalse( $this->evaluator->evaluate( $group, [ 'post_type' => 'post', 'author_id' => 2 ] ) );
		$this->assertFalse( $this->evaluator->evaluate( $group, [ 'post_type' => 'page', 'author_id' => 1 ] ) );
	}

	public function test_or_logic(): void {
		$group = [
			'logic' => 'OR',
			'rules' => [
				[ 'field' => 'post_type', 'type' => 'string', 'operator' => 'equals', 'value' => 'post' ],
				[ 'field' => 'post_type', 'type' => 'string', 'operator' => 'equals', 'value' => 'page' ],
			],
		];

		$this->assertTrue( $this->evaluator->evaluate( $group, [ 'post_type' => 'post' ] ) );
		$this->assertTrue( $this->evaluator->evaluate( $group, [ 'post_type' => 'page' ] ) );
		$this->assertFalse( $this->evaluator->evaluate( $group, [ 'post_type' => 'product' ] ) );
	}

	public function test_nested_groups(): void {
		$group = [
			'logic' => 'AND',
			'rules' => [
				[ 'field' => 'post_type', 'type' => 'string', 'operator' => 'equals', 'value' => 'post' ],
				[
					'logic' => 'OR',
					'rules' => [
						[ 'field' => 'author_id', 'type' => 'numeric', 'operator' => 'equals', 'value' => '1' ],
						[ 'field' => 'author_id', 'type' => 'numeric', 'operator' => 'equals', 'value' => '2' ],
					],
				],
			],
		];

		$this->assertTrue( $this->evaluator->evaluate( $group, [ 'post_type' => 'post', 'author_id' => 1 ] ) );
		$this->assertTrue( $this->evaluator->evaluate( $group, [ 'post_type' => 'post', 'author_id' => 2 ] ) );
		$this->assertFalse( $this->evaluator->evaluate( $group, [ 'post_type' => 'post', 'author_id' => 3 ] ) );
		$this->assertFalse( $this->evaluator->evaluate( $group, [ 'post_type' => 'page', 'author_id' => 1 ] ) );
	}

	public function test_dot_notation_field_access(): void {
		$group = [
			'logic' => 'AND',
			'rules' => [
				[ 'field' => 'user.role', 'type' => 'string', 'operator' => 'equals', 'value' => 'admin' ],
			],
		];

		$this->assertTrue( $this->evaluator->evaluate( $group, [ 'user' => [ 'role' => 'admin' ] ] ) );
		$this->assertFalse( $this->evaluator->evaluate( $group, [ 'user' => [ 'role' => 'editor' ] ] ) );
	}

	public function test_missing_field_returns_false(): void {
		$group = [
			'logic' => 'AND',
			'rules' => [
				[ 'field' => 'nonexistent', 'type' => 'string', 'operator' => 'equals', 'value' => 'anything' ],
			],
		];

		$this->assertFalse( $this->evaluator->evaluate( $group, [ 'post_type' => 'post' ] ) );
	}
}
