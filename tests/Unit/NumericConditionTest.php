<?php
/**
 * Unit tests for the Numeric condition type.
 *
 * @package FlavorFlow\Tests\Unit
 */

declare(strict_types=1);

namespace flavor_flow\Tests\Unit;

use flavor_flow\Condition\Conditions\NumericCondition;
use PHPUnit\Framework\TestCase;

final class NumericConditionTest extends TestCase {

	private NumericCondition $condition;

	protected function setUp(): void {
		$this->condition = new NumericCondition();
	}

	public function test_slug(): void {
		$this->assertSame( 'numeric', $this->condition->get_slug() );
	}

	/**
	 * @dataProvider operator_provider
	 */
	public function test_operators( string $operator, float $actual, float $expected, bool $result ): void {
		$this->assertSame( $result, $this->condition->evaluate( $actual, $operator, $expected ) );
	}

	public static function operator_provider(): array {
		return [
			'equals true'            => [ 'equals', 5.0, 5.0, true ],
			'equals false'           => [ 'equals', 5.0, 6.0, false ],
			'not_equals true'        => [ 'not_equals', 5.0, 6.0, true ],
			'not_equals false'       => [ 'not_equals', 5.0, 5.0, false ],
			'greater_than true'      => [ 'greater_than', 10.0, 5.0, true ],
			'greater_than false'     => [ 'greater_than', 3.0, 5.0, false ],
			'less_than true'         => [ 'less_than', 3.0, 5.0, true ],
			'less_than false'        => [ 'less_than', 10.0, 5.0, false ],
			'gte true'              => [ 'greater_than_equal', 5.0, 5.0, true ],
			'gte false'             => [ 'greater_than_equal', 4.0, 5.0, false ],
			'lte true'              => [ 'less_than_equal', 5.0, 5.0, true ],
			'lte false'             => [ 'less_than_equal', 6.0, 5.0, false ],
			'unknown operator'       => [ 'bogus', 5.0, 5.0, false ],
		];
	}
}
