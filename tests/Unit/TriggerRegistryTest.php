<?php
/**
 * Unit tests for TriggerRegistry.
 *
 * @package FlavorFlow\Tests\Unit
 */

declare(strict_types=1);

namespace flavor_flow\Tests\Unit;

use flavor_flow\Trigger\TriggerInterface;
use flavor_flow\Trigger\TriggerRegistry;
use PHPUnit\Framework\TestCase;

final class TriggerRegistryTest extends TestCase {

	public function test_add_and_get(): void {
		$trigger = $this->create_trigger( 'test_trigger', 'Test', 'Tests' );

		$registry = new TriggerRegistry();
		$registry->add( $trigger );

		$this->assertSame( $trigger, $registry->get( 'test_trigger' ) );
	}

	public function test_get_unknown_returns_null(): void {
		$registry = new TriggerRegistry();
		$this->assertNull( $registry->get( 'nonexistent' ) );
	}

	public function test_all(): void {
		$registry = new TriggerRegistry();
		$registry->add( $this->create_trigger( 'a', 'A', 'Group' ) );
		$registry->add( $this->create_trigger( 'b', 'B', 'Group' ) );

		$this->assertCount( 2, $registry->all() );
	}

	public function test_grouped(): void {
		$registry = new TriggerRegistry();
		$registry->add( $this->create_trigger( 'a', 'A', 'Posts' ) );
		$registry->add( $this->create_trigger( 'b', 'B', 'Users' ) );
		$registry->add( $this->create_trigger( 'c', 'C', 'Posts' ) );

		$grouped = $registry->grouped();

		$this->assertCount( 2, $grouped );
		$this->assertCount( 2, $grouped['Posts'] );
		$this->assertCount( 1, $grouped['Users'] );
	}

	private function create_trigger( string $slug, string $label, string $group ): TriggerInterface {
		return new class( $slug, $label, $group ) implements TriggerInterface {
			public function __construct(
				private string $slug,
				private string $label,
				private string $group,
			) {}

			public function get_slug(): string {
				return $this->slug;
			}

			public function get_label(): string {
				return $this->label;
			}

			public function get_group(): string {
				return $this->group;
			}

			public function listen(): void {}

			public function get_payload_schema(): array {
				return [];
			}
		};
	}
}
