<?php
/**
 * Unit tests for ActionResult.
 *
 * @package FlavorFlow\Tests\Unit
 */

declare(strict_types=1);

namespace flavor_flow\Tests\Unit;

use flavor_flow\Action\ActionResult;
use PHPUnit\Framework\TestCase;

final class ActionResultTest extends TestCase {

	public function test_success(): void {
		$result = ActionResult::success( 'Done.', [ 'key' => 'val' ] );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'Done.', $result->get_message() );
		$this->assertSame( [ 'key' => 'val' ], $result->get_data() );
	}

	public function test_failure(): void {
		$result = ActionResult::failure( 'Oops.' );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'Oops.', $result->get_message() );
		$this->assertSame( [], $result->get_data() );
	}

	public function test_to_array(): void {
		$result = ActionResult::success( 'OK' );
		$arr    = $result->to_array();

		$this->assertArrayHasKey( 'success', $arr );
		$this->assertArrayHasKey( 'message', $arr );
		$this->assertArrayHasKey( 'data', $arr );
		$this->assertTrue( $arr['success'] );
	}
}
