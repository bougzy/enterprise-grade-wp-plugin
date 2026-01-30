<?php
/**
 * Unit tests for the DI Container.
 *
 * @package FlavorFlow\Tests\Unit
 */

declare(strict_types=1);

namespace flavor_flow\Tests\Unit;

use flavor_flow\Core\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ContainerTest extends TestCase {

	public function test_set_and_get(): void {
		$container = new Container();

		$container->set( 'service', static fn() => new stdClass() );

		$this->assertTrue( $container->has( 'service' ) );
		$this->assertInstanceOf( stdClass::class, $container->get( 'service' ) );
	}

	public function test_singleton_behaviour(): void {
		$container = new Container();

		$container->set( 'service', static function () {
			$obj       = new stdClass();
			$obj->rand = random_int( 1, 999999 );
			return $obj;
		} );

		$first  = $container->get( 'service' );
		$second = $container->get( 'service' );

		$this->assertSame( $first, $second );
	}

	public function test_get_unknown_service_throws(): void {
		$container = new Container();

		$this->expectException( InvalidArgumentException::class );
		$container->get( 'nonexistent' );
	}

	public function test_has_returns_false_for_unknown(): void {
		$container = new Container();

		$this->assertFalse( $container->has( 'nope' ) );
	}

	public function test_container_passed_to_factory(): void {
		$container = new Container();

		$container->set( 'dep', static fn() => (object) [ 'name' => 'dependency' ] );

		$container->set( 'service', static function ( Container $c ) {
			$obj      = new stdClass();
			$obj->dep = $c->get( 'dep' );
			return $obj;
		} );

		$service = $container->get( 'service' );
		$this->assertSame( 'dependency', $service->dep->name );
	}
}
