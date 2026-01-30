<?php
/**
 * Trigger: Comment Posted.
 *
 * @package FlavorFlow\Trigger\Triggers
 */

declare(strict_types=1);

namespace flavor_flow\Trigger\Triggers;

use flavor_flow\Trigger\AbstractTrigger;
use WP_Comment;

final class CommentPostedTrigger extends AbstractTrigger {

	public function get_slug(): string {
		return 'comment_posted';
	}

	public function get_label(): string {
		return __( 'Comment Posted', 'flavor-flow' );
	}

	public function get_group(): string {
		return __( 'Comments', 'flavor-flow' );
	}

	public function listen(): void {
		add_action( 'wp_insert_comment', [ $this, 'handle' ], 10, 2 );
	}

	public function handle( int $comment_id, WP_Comment $comment ): void {
		$this->dispatch( [
			'comment_id'     => $comment_id,
			'post_id'        => (int) $comment->comment_post_ID,
			'author_name'    => $comment->comment_author,
			'author_email'   => $comment->comment_author_email,
			'comment_status' => $comment->comment_approved,
		] );
	}

	public function get_payload_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'comment_id'     => [ 'type' => 'integer' ],
				'post_id'        => [ 'type' => 'integer' ],
				'author_name'    => [ 'type' => 'string' ],
				'author_email'   => [ 'type' => 'string', 'format' => 'email' ],
				'comment_status' => [ 'type' => 'string' ],
			],
		];
	}
}
