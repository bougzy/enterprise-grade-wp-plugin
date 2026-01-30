<?php
/**
 * Trigger: User Registered.
 *
 * @package FlavorFlow\Trigger\Triggers
 */

declare(strict_types=1);

namespace flavor_flow\Trigger\Triggers;

use flavor_flow\Trigger\AbstractTrigger;

final class UserRegisteredTrigger extends AbstractTrigger {

	public function get_slug(): string {
		return 'user_registered';
	}

	public function get_label(): string {
		return __( 'User Registered', 'flavor-flow' );
	}

	public function get_group(): string {
		return __( 'Users', 'flavor-flow' );
	}

	public function listen(): void {
		add_action( 'user_register', [ $this, 'handle' ], 10, 2 );
	}

	/**
	 * @param int   $user_id  Newly created user ID.
	 * @param array $userdata Data passed to wp_insert_user().
	 */
	public function handle( int $user_id, array $userdata = [] ): void {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$this->dispatch( [
			'user_id'    => $user_id,
			'user_email' => $user->user_email,
			'user_login' => $user->user_login,
			'roles'      => $user->roles,
		] );
	}

	public function get_payload_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'user_id'    => [ 'type' => 'integer' ],
				'user_email' => [ 'type' => 'string', 'format' => 'email' ],
				'user_login' => [ 'type' => 'string' ],
				'roles'      => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
			],
		];
	}
}
