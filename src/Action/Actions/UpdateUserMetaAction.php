<?php
/**
 * Action: Update User Meta.
 *
 * @package FlavorFlow\Action\Actions
 */

declare(strict_types=1);

namespace flavor_flow\Action\Actions;

use flavor_flow\Action\ActionInterface;
use flavor_flow\Action\ActionResult;

final class UpdateUserMetaAction implements ActionInterface {

	public function get_slug(): string {
		return 'update_user_meta';
	}

	public function get_label(): string {
		return __( 'Update User Meta', 'flavor-flow' );
	}

	public function get_group(): string {
		return __( 'Data', 'flavor-flow' );
	}

	public function execute( array $config, array $payload ): ActionResult {
		$user_id  = (int) ( $config['user_id'] ?? ( $payload['user_id'] ?? 0 ) );
		$meta_key = sanitize_key( $config['meta_key'] ?? '' );
		$value    = sanitize_text_field( $config['meta_value'] ?? '' );

		if ( $user_id <= 0 ) {
			return ActionResult::failure( 'Invalid user ID.' );
		}

		if ( '' === $meta_key ) {
			return ActionResult::failure( 'Meta key is required.' );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return ActionResult::failure( sprintf( 'User %d not found.', $user_id ) );
		}

		update_user_meta( $user_id, $meta_key, $value );

		return ActionResult::success(
			sprintf( 'User meta "%s" updated for user %d.', $meta_key, $user_id )
		);
	}

	public function get_config_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [ 'meta_key', 'meta_value' ],
			'properties' => [
				'user_id'    => [
					'type'        => 'integer',
					'description' => 'Target user ID. Falls back to payload.user_id.',
				],
				'meta_key'   => [ 'type' => 'string' ],
				'meta_value' => [ 'type' => 'string' ],
			],
		];
	}
}
