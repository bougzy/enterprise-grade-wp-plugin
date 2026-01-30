<?php
/**
 * Action: Update Post Meta.
 *
 * @package FlavorFlow\Action\Actions
 */

declare(strict_types=1);

namespace flavor_flow\Action\Actions;

use flavor_flow\Action\ActionInterface;
use flavor_flow\Action\ActionResult;

final class UpdatePostMetaAction implements ActionInterface {

	public function get_slug(): string {
		return 'update_post_meta';
	}

	public function get_label(): string {
		return __( 'Update Post Meta', 'flavor-flow' );
	}

	public function get_group(): string {
		return __( 'Data', 'flavor-flow' );
	}

	public function execute( array $config, array $payload ): ActionResult {
		$post_id  = (int) ( $config['post_id'] ?? ( $payload['post_id'] ?? 0 ) );
		$meta_key = sanitize_key( $config['meta_key'] ?? '' );
		$value    = sanitize_text_field( $config['meta_value'] ?? '' );

		if ( $post_id <= 0 ) {
			return ActionResult::failure( 'Invalid post ID.' );
		}

		if ( '' === $meta_key ) {
			return ActionResult::failure( 'Meta key is required.' );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return ActionResult::failure( sprintf( 'Post %d not found.', $post_id ) );
		}

		update_post_meta( $post_id, $meta_key, $value );

		return ActionResult::success(
			sprintf( 'Post meta "%s" updated on post %d.', $meta_key, $post_id )
		);
	}

	public function get_config_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [ 'meta_key', 'meta_value' ],
			'properties' => [
				'post_id'    => [
					'type'        => 'integer',
					'description' => 'Target post ID. Falls back to payload.post_id.',
				],
				'meta_key'   => [ 'type' => 'string' ],
				'meta_value' => [ 'type' => 'string' ],
			],
		];
	}
}
