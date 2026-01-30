<?php
/**
 * Workflow custom post type registration.
 *
 * @package FlavorFlow\PostType
 */

declare(strict_types=1);

namespace flavor_flow\PostType;

/**
 * Registers the ff_workflow post type.
 */
final class WorkflowPostType {

	public const SLUG = 'ff_workflow';

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
	}

	/**
	 * Register the custom post type.
	 */
	public function register_post_type(): void {
		$labels = [
			'name'                  => _x( 'Workflows', 'Post type plural', 'flavor-flow' ),
			'singular_name'         => _x( 'Workflow', 'Post type singular', 'flavor-flow' ),
			'add_new'               => __( 'Add New Workflow', 'flavor-flow' ),
			'add_new_item'          => __( 'Add New Workflow', 'flavor-flow' ),
			'edit_item'             => __( 'Edit Workflow', 'flavor-flow' ),
			'new_item'              => __( 'New Workflow', 'flavor-flow' ),
			'view_item'             => __( 'View Workflow', 'flavor-flow' ),
			'search_items'          => __( 'Search Workflows', 'flavor-flow' ),
			'not_found'             => __( 'No workflows found.', 'flavor-flow' ),
			'not_found_in_trash'    => __( 'No workflows found in Trash.', 'flavor-flow' ),
			'all_items'             => __( 'All Workflows', 'flavor-flow' ),
			'menu_name'             => __( 'FlavorFlow', 'flavor-flow' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'rest_base'           => 'ff-workflows',
			'menu_position'       => 58,
			'menu_icon'           => 'dashicons-randomize',
			'capability_type'     => 'ff_workflow',
			'map_meta_cap'        => true,
			'hierarchical'        => false,
			'supports'            => [ 'title', 'author', 'custom-fields' ],
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
		];

		register_post_type( self::SLUG, $args );

		$this->register_capabilities();
	}

	/**
	 * Map custom capabilities to administrator role.
	 */
	private function register_capabilities(): void {
		$role = get_role( 'administrator' );

		if ( ! $role ) {
			return;
		}

		$caps = [
			'edit_ff_workflow',
			'read_ff_workflow',
			'delete_ff_workflow',
			'edit_ff_workflows',
			'edit_others_ff_workflows',
			'publish_ff_workflows',
			'read_private_ff_workflows',
			'delete_ff_workflows',
			'delete_private_ff_workflows',
			'delete_published_ff_workflows',
			'delete_others_ff_workflows',
			'edit_private_ff_workflows',
			'edit_published_ff_workflows',
		];

		foreach ( $caps as $cap ) {
			if ( ! $role->has_cap( $cap ) ) {
				$role->add_cap( $cap );
			}
		}
	}
}
