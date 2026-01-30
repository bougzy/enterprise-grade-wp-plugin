<?php
/**
 * Workflow Category taxonomy.
 *
 * @package FlavorFlow\Taxonomy
 */

declare(strict_types=1);

namespace flavor_flow\Taxonomy;

use flavor_flow\PostType\WorkflowPostType;

/**
 * Registers the Workflow Category taxonomy.
 */
final class WorkflowCategoryTaxonomy {

	public const SLUG = 'ff_workflow_category';

	public function register(): void {
		add_action( 'init', [ $this, 'register_taxonomy' ] );
	}

	public function register_taxonomy(): void {
		$labels = [
			'name'              => _x( 'Workflow Categories', 'Taxonomy plural', 'flavor-flow' ),
			'singular_name'     => _x( 'Workflow Category', 'Taxonomy singular', 'flavor-flow' ),
			'search_items'      => __( 'Search Categories', 'flavor-flow' ),
			'all_items'         => __( 'All Categories', 'flavor-flow' ),
			'parent_item'       => __( 'Parent Category', 'flavor-flow' ),
			'parent_item_colon' => __( 'Parent Category:', 'flavor-flow' ),
			'edit_item'         => __( 'Edit Category', 'flavor-flow' ),
			'update_item'       => __( 'Update Category', 'flavor-flow' ),
			'add_new_item'      => __( 'Add New Category', 'flavor-flow' ),
			'new_item_name'     => __( 'New Category Name', 'flavor-flow' ),
			'menu_name'         => __( 'Categories', 'flavor-flow' ),
		];

		$args = [
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rest_base'         => 'ff-workflow-categories',
			'query_var'         => false,
			'rewrite'           => false,
		];

		register_taxonomy( self::SLUG, WorkflowPostType::SLUG, $args );
	}
}
