<?php
/**
 * Container configuration reference.
 *
 * This file documents the services registered in the DI container.
 * It is not executed at runtime — it serves as a living reference.
 *
 * @package FlavorFlow
 */

return [
	// Core.
	\flavor_flow\Core\Plugin::class,
	\flavor_flow\Core\Container::class,

	// Database.
	\flavor_flow\Database\Migrator::class,

	// Logging.
	\flavor_flow\Logging\Logger::class,

	// Post Types.
	\flavor_flow\PostType\WorkflowPostType::class,

	// Taxonomies.
	\flavor_flow\Taxonomy\WorkflowCategoryTaxonomy::class,

	// Triggers.
	\flavor_flow\Trigger\TriggerRegistry::class,

	// Conditions.
	\flavor_flow\Condition\ConditionEvaluator::class,

	// Actions.
	\flavor_flow\Action\ActionRegistry::class,

	// Queue.
	\flavor_flow\Queue\QueueManager::class,
	\flavor_flow\Queue\WorkflowEngine::class,

	// API.
	\flavor_flow\API\WorkflowController::class,
	\flavor_flow\API\LogController::class,
	\flavor_flow\API\WebhookIngressController::class,
	\flavor_flow\API\SystemInfoController::class,

	// Admin.
	\flavor_flow\Admin\AdminPage::class,
	\flavor_flow\Admin\SettingsPage::class,

	// License.
	\flavor_flow\License\LicenseManager::class,
];
