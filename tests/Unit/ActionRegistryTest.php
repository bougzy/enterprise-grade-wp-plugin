<?php
/**
 * Unit tests for ActionRegistry.
 *
 * @package FlavorFlow\Tests\Unit
 */

declare(strict_types=1);

namespace flavor_flow\Tests\Unit;

use flavor_flow\Action\ActionInterface;
use flavor_flow\Action\ActionRegistry;
use flavor_flow\Action\ActionResult;
use PHPUnit\Framework\TestCase;

final class ActionRegistryTest extends TestCase {

	public function test_add_and_get(): void {
		$action   = $this->create_action( 'test_action', 'Test', 'Group' );
		$registry = new ActionRegistry();
		$registry->add( $action );

		$this->assertSame( $action, $registry->get( 'test_action' ) );
	}

	public function test_get_unknown_returns_null(): void {
		$registry = new ActionRegistry();
		$this->assertNull( $registry->get( 'nonexistent' ) );
	}

	public function test_all_and_grouped(): void {
		$registry = new ActionRegistry();
		$registry->add( $this->create_action( 'a', 'A', 'Communication' ) );
		$registry->add( $this->create_action( 'b', 'B', 'Data' ) );
		$registry->add( $this->create_action( 'c', 'C', 'Communication' ) );

		$this->assertCount( 3, $registry->all() );

		$grouped = $registry->grouped();
		$this->assertCount( 2, $grouped['Communication'] );
		$this->assertCount( 1, $grouped['Data'] );
	}

	private function create_action( string $slug, string $label, string $group ): ActionInterface {
		return new class( $slug, $label, $group ) implements ActionInterface {
			public function __construct(
				private string $slug,
				private string $label,
				private string $group,
			) {}

			public function get_slug(): string { return $this->slug; }
			public function get_label(): string { return $this->label; }
			public function get_group(): string { return $this->group; }
			public function execute( array $config, array $payload ): ActionResult {
				return ActionResult::success();
			}
			public function get_config_schema(): array { return []; }
		};
	}
}
