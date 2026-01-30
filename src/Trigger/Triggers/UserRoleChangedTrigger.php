<?php
/**
 * Trigger: User Role Changed.
 *
 * @package FlavorFlow\Trigger\Triggers
 */

declare(strict_types=1);

namespace flavor_flow\Trigger\Triggers;

use flavor_flow\Trigger\AbstractTrigger;

final class UserRoleChangedTrigger extends AbstractTrigger {

	public function get_slug(): string {
		return 'user_role_changed';
	}

	public function get_label(): string {
		return __( 'User Role Changed', 'flavor-flow' );
	}

	public function get_group(): string {
		return __( 'Users', 'flavor-flow' );
	}

	public function listen(): void {
		add_action( 'set_user_role', [ $this, 'handle' ], 10, 3 );
	}

	/**
	 * @param int    $user_id   User ID.
	 * @param string $role      New role.
	 * @param array  $old_roles Previous roles.
	 */
	public function handle( int $user_id, string $role, array $old_roles ): void {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$this->dispatch( [
			'user_id'    => $user_id,
			'user_email' => $user->user_email,
			'new_role'   => $role,
			'old_roles'  => $old_roles,
		] );
	}

	public function get_payload_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'user_id'    => [ 'type' => 'integer' ],
				'user_email' => [ 'type' => 'string', 'format' => 'email' ],
				'new_role'   => [ 'type' => 'string' ],
				'old_roles'  => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
			],
		];
	}
}
