<?php
/**
 * REST controller for workflows.
 *
 * @package FlavorFlow\API
 */

declare(strict_types=1);

namespace flavor_flow\API;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Custom REST endpoints for workflow management.
 *
 * Route: /flavor-flow/v1/workflows
 */
final class WorkflowController extends WP_REST_Controller {

	protected $namespace = 'flavor-flow/v1';
	protected $rest_base = 'workflows';

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
				'args'                => $this->get_collection_params(),
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			],
			'schema' => [ $this, 'get_public_item_schema' ],
		] );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
				'args'                => [
					'id' => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
				],
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_item' ],
				'permission_callback' => [ $this, 'delete_item_permissions_check' ],
				'args'                => [
					'id' => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
				],
			],
			'schema' => [ $this, 'get_public_item_schema' ],
		] );

		// Duplicate workflow.
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/duplicate', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'duplicate_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
		] );

		// Toggle enabled/disabled.
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/toggle', [
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'toggle_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
			],
		] );
	}

	// ---- Permission callbacks ----

	public function get_items_permissions_check( $request ): bool|WP_Error {
		return current_user_can( 'edit_ff_workflows' );
	}

	public function get_item_permissions_check( $request ): bool|WP_Error {
		return current_user_can( 'edit_ff_workflow', $request['id'] );
	}

	public function create_item_permissions_check( $request ): bool|WP_Error {
		return current_user_can( 'publish_ff_workflows' );
	}

	public function update_item_permissions_check( $request ): bool|WP_Error {
		return current_user_can( 'edit_ff_workflow', $request['id'] );
	}

	public function delete_item_permissions_check( $request ): bool|WP_Error {
		return current_user_can( 'delete_ff_workflow', $request['id'] );
	}

	// ---- CRUD callbacks ----

	public function get_items( $request ): WP_REST_Response|WP_Error {
		$args = [
			'post_type'      => 'ff_workflow',
			'post_status'    => 'any',
			'posts_per_page' => (int) $request->get_param( 'per_page' ) ?: 20,
			'paged'          => (int) $request->get_param( 'page' ) ?: 1,
			'orderby'        => sanitize_key( $request->get_param( 'orderby' ) ?: 'date' ),
			'order'          => 'ASC' === strtoupper( (string) $request->get_param( 'order' ) ) ? 'ASC' : 'DESC',
		];

		$search = $request->get_param( 'search' );
		if ( $search ) {
			$args['s'] = sanitize_text_field( $search );
		}

		$query = new \WP_Query( $args );
		$items = [];

		foreach ( $query->posts as $post ) {
			$items[] = $this->prepare_item_for_response( $post, $request )->get_data();
		}

		$response = new WP_REST_Response( $items );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $response;
	}

	public function get_item( $request ): WP_REST_Response|WP_Error {
		$post = get_post( $request['id'] );

		if ( ! $post || 'ff_workflow' !== $post->post_type ) {
			return new WP_Error( 'not_found', __( 'Workflow not found.', 'flavor-flow' ), [ 'status' => 404 ] );
		}

		return $this->prepare_item_for_response( $post, $request );
	}

	public function create_item( $request ): WP_REST_Response|WP_Error {
		$post_id = wp_insert_post( [
			'post_type'   => 'ff_workflow',
			'post_title'  => sanitize_text_field( $request->get_param( 'title' ) ?: __( 'Untitled Workflow', 'flavor-flow' ) ),
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
		], true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->save_meta( $post_id, $request );

		$post = get_post( $post_id );
		return $this->prepare_item_for_response( $post, $request );
	}

	public function update_item( $request ): WP_REST_Response|WP_Error {
		$post = get_post( $request['id'] );

		if ( ! $post || 'ff_workflow' !== $post->post_type ) {
			return new WP_Error( 'not_found', __( 'Workflow not found.', 'flavor-flow' ), [ 'status' => 404 ] );
		}

		$title = $request->get_param( 'title' );
		if ( null !== $title ) {
			wp_update_post( [
				'ID'         => $post->ID,
				'post_title' => sanitize_text_field( $title ),
			] );
		}

		$this->save_meta( $post->ID, $request );

		$post = get_post( $post->ID );
		return $this->prepare_item_for_response( $post, $request );
	}

	public function delete_item( $request ): WP_REST_Response|WP_Error {
		$post = get_post( $request['id'] );

		if ( ! $post || 'ff_workflow' !== $post->post_type ) {
			return new WP_Error( 'not_found', __( 'Workflow not found.', 'flavor-flow' ), [ 'status' => 404 ] );
		}

		wp_delete_post( $post->ID, true );

		return new WP_REST_Response( [ 'deleted' => true ], 200 );
	}

	public function duplicate_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post = get_post( $request['id'] );

		if ( ! $post || 'ff_workflow' !== $post->post_type ) {
			return new WP_Error( 'not_found', __( 'Workflow not found.', 'flavor-flow' ), [ 'status' => 404 ] );
		}

		$new_id = wp_insert_post( [
			'post_type'   => 'ff_workflow',
			'post_title'  => sprintf(
				/* translators: %s: original title */
				__( '%s (Copy)', 'flavor-flow' ),
				$post->post_title
			),
			'post_status' => 'draft',
			'post_author' => get_current_user_id(),
		], true );

		if ( is_wp_error( $new_id ) ) {
			return $new_id;
		}

		// Copy meta.
		foreach ( [ '_ff_trigger', '_ff_conditions', '_ff_actions', '_ff_enabled' ] as $key ) {
			$val = get_post_meta( $post->ID, $key, true );
			if ( $val ) {
				update_post_meta( $new_id, $key, $val );
			}
		}

		return $this->prepare_item_for_response( get_post( $new_id ), $request );
	}

	public function toggle_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post = get_post( $request['id'] );

		if ( ! $post || 'ff_workflow' !== $post->post_type ) {
			return new WP_Error( 'not_found', __( 'Workflow not found.', 'flavor-flow' ), [ 'status' => 404 ] );
		}

		$current = get_post_meta( $post->ID, '_ff_enabled', true );
		$new     = '1' === $current ? '0' : '1';
		update_post_meta( $post->ID, '_ff_enabled', $new );

		return $this->prepare_item_for_response( get_post( $post->ID ), $request );
	}

	// ---- Helpers ----

	public function prepare_item_for_response( $post, $request ): WP_REST_Response {
		$data = [
			'id'         => $post->ID,
			'title'      => $post->post_title,
			'status'     => $post->post_status,
			'author'     => (int) $post->post_author,
			'created_at' => $post->post_date_gmt,
			'updated_at' => $post->post_modified_gmt,
			'trigger'    => get_post_meta( $post->ID, '_ff_trigger', true ) ?: '',
			'conditions' => get_post_meta( $post->ID, '_ff_conditions', true ) ?: [],
			'actions'    => get_post_meta( $post->ID, '_ff_actions', true ) ?: [],
			'enabled'    => '1' === get_post_meta( $post->ID, '_ff_enabled', true ),
		];

		return new WP_REST_Response( $data );
	}

	/**
	 * Persist workflow meta from a request.
	 */
	private function save_meta( int $post_id, WP_REST_Request $request ): void {
		$trigger = $request->get_param( 'trigger' );
		if ( null !== $trigger ) {
			update_post_meta( $post_id, '_ff_trigger', sanitize_text_field( $trigger ) );
		}

		$conditions = $request->get_param( 'conditions' );
		if ( null !== $conditions ) {
			update_post_meta( $post_id, '_ff_conditions', $this->sanitize_deep( $conditions ) );
		}

		$actions = $request->get_param( 'actions' );
		if ( null !== $actions ) {
			update_post_meta( $post_id, '_ff_actions', $this->sanitize_deep( $actions ) );
		}

		$enabled = $request->get_param( 'enabled' );
		if ( null !== $enabled ) {
			update_post_meta( $post_id, '_ff_enabled', $enabled ? '1' : '0' );
		}
	}

	/**
	 * Recursively sanitize an array/scalar.
	 */
	private function sanitize_deep( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return array_map( [ $this, 'sanitize_deep' ], $value );
		}

		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
			return $value;
		}

		return '';
	}

	/**
	 * JSON Schema for the workflow resource.
	 */
	public function get_item_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'ff_workflow',
			'type'       => 'object',
			'properties' => [
				'id'         => [ 'type' => 'integer', 'readonly' => true ],
				'title'      => [ 'type' => 'string' ],
				'status'     => [ 'type' => 'string', 'readonly' => true ],
				'author'     => [ 'type' => 'integer', 'readonly' => true ],
				'trigger'    => [ 'type' => 'string' ],
				'conditions' => [ 'type' => [ 'object', 'array' ] ],
				'actions'    => [ 'type' => 'array' ],
				'enabled'    => [ 'type' => 'boolean' ],
				'created_at' => [ 'type' => 'string', 'format' => 'date-time', 'readonly' => true ],
				'updated_at' => [ 'type' => 'string', 'format' => 'date-time', 'readonly' => true ],
			],
		];
	}
}
