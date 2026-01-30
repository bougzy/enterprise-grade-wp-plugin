<?php
/**
 * Trigger: Post Published.
 *
 * @package FlavorFlow\Trigger\Triggers
 */

declare(strict_types=1);

namespace flavor_flow\Trigger\Triggers;

use flavor_flow\Trigger\AbstractTrigger;
use WP_Post;

final class PostPublishedTrigger extends AbstractTrigger {

	public function get_slug(): string {
		return 'post_published';
	}

	public function get_label(): string {
		return __( 'Post Published', 'flavor-flow' );
	}

	public function get_group(): string {
		return __( 'Posts', 'flavor-flow' );
	}

	public function listen(): void {
		add_action( 'transition_post_status', [ $this, 'handle' ], 10, 3 );
	}

	/**
	 * Fires when a post transitions to "publish".
	 */
	public function handle( string $new_status, string $old_status, WP_Post $post ): void {
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		// Ignore our own post type to prevent recursion.
		if ( 'ff_workflow' === $post->post_type ) {
			return;
		}

		$this->dispatch( [
			'post_id'    => $post->ID,
			'post_type'  => $post->post_type,
			'post_title' => $post->post_title,
			'author_id'  => (int) $post->post_author,
			'old_status' => $old_status,
		] );
	}

	public function get_payload_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'    => [ 'type' => 'integer' ],
				'post_type'  => [ 'type' => 'string' ],
				'post_title' => [ 'type' => 'string' ],
				'author_id'  => [ 'type' => 'integer' ],
				'old_status' => [ 'type' => 'string' ],
			],
		];
	}
}
