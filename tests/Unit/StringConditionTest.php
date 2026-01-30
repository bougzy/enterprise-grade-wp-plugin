<?php
/**
 * Unit tests for the String condition type.
 *
 * @package FlavorFlow\Tests\Unit
 */

declare(strict_types=1);

namespace flavor_flow\Tests\Unit;

use flavor_flow\Condition\Conditions\StringCondition;
use PHPUnit\Framework\TestCase;

final class StringConditionTest extends TestCase {

	private StringCondition $condition;

	protected function setUp(): void {
		$this->condition = new StringCondition();
	}

	public function test_slug(): void {
		$this->assertSame( 'string', $this->condition->get_slug() );
	}

	/**
	 * @dataProvider operator_provider
	 */
	public function test_operators( string $operator, string $actual, string $expected, bool $result ): void {
		$this->assertSame( $result, $this->condition->evaluate( $actual, $operator, $expected ) );
	}

	public static function operator_provider(): array {
		return [
			'equals true'          => [ 'equals', 'hello', 'hello', true ],
			'equals false'         => [ 'equals', 'hello', 'world', false ],
			'not_equals true'      => [ 'not_equals', 'a', 'b', true ],
			'not_equals false'     => [ 'not_equals', 'a', 'a', false ],
			'contains true'        => [ 'contains', 'hello world', 'world', true ],
			'contains false'       => [ 'contains', 'hello', 'world', false ],
			'not_contains true'    => [ 'not_contains', 'hello', 'xyz', true ],
			'not_contains false'   => [ 'not_contains', 'hello', 'ell', false ],
			'starts_with true'     => [ 'starts_with', 'hello', 'hel', true ],
			'starts_with false'    => [ 'starts_with', 'hello', 'ell', false ],
			'ends_with true'       => [ 'ends_with', 'hello', 'llo', true ],
			'ends_with false'      => [ 'ends_with', 'hello', 'hel', false ],
			'is_empty true'        => [ 'is_empty', '', '', true ],
			'is_empty false'       => [ 'is_empty', 'x', '', false ],
			'is_not_empty true'    => [ 'is_not_empty', 'x', '', true ],
			'is_not_empty false'   => [ 'is_not_empty', '', '', false ],
			'unknown operator'     => [ 'bogus', 'a', 'b', false ],
		];
	}
}
