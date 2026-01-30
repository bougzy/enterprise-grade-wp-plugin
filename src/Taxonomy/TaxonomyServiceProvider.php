<?php
/**
 * Taxonomy service provider.
 *
 * @package FlavorFlow\Taxonomy
 */

declare(strict_types=1);

namespace flavor_flow\Taxonomy;

use flavor_flow\Core\ServiceProvider;

final class TaxonomyServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->set(
			WorkflowCategoryTaxonomy::class,
			static fn() => new WorkflowCategoryTaxonomy()
		);
	}

	public function boot(): void {
		/** @var WorkflowCategoryTaxonomy $taxonomy */
		$taxonomy = $this->container->get( WorkflowCategoryTaxonomy::class );
		$taxonomy->register();
	}
}
