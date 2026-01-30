<?php
/**
 * Post type service provider.
 *
 * @package FlavorFlow\PostType
 */

declare(strict_types=1);

namespace flavor_flow\PostType;

use flavor_flow\Core\ServiceProvider;

final class PostTypeServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->set(
			WorkflowPostType::class,
			static fn() => new WorkflowPostType()
		);
	}

	public function boot(): void {
		/** @var WorkflowPostType $workflow_pt */
		$workflow_pt = $this->container->get( WorkflowPostType::class );
		$workflow_pt->register();
	}
}
