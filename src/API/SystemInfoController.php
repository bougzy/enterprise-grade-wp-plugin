<?php
/**
 * REST controller for system information.
 *
 * @package FlavorFlow\API
 */

declare(strict_types=1);

namespace flavor_flow\API;

use flavor_flow\Action\ActionRegistry;
use flavor_flow\Condition\ConditionEvaluator;
use flavor_flow\Trigger\TriggerRegistry;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Exposes available triggers, conditions, and actions for the admin UI.
 *
 * Route: GET /flavor-flow/v1/system
 */
final class SystemInfoController extends WP_REST_Controller {

	protected $namespace = 'flavor-flow/v1';
	protected $rest_base = 'system';

	public function __construct(
		private readonly TriggerRegistry $triggers,
		private readonly ConditionEvaluator $conditions,
		private readonly ActionRegistry $actions,
	) {}

	public function register_routes(): void {
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_info' ],
				'permission_callback' => static fn() => current_user_can( 'manage_options' ),
			],
		] );
	}

	public function get_info(): WP_REST_Response {
		$triggers = [];
		foreach ( $this->triggers->all() as $t ) {
			$triggers[] = [
				'slug'           => $t->get_slug(),
				'label'          => $t->get_label(),
				'group'          => $t->get_group(),
				'payload_schema' => $t->get_payload_schema(),
			];
		}

		$conditions = [];
		foreach ( $this->conditions->get_conditions() as $c ) {
			$conditions[] = [
				'slug'      => $c->get_slug(),
				'label'     => $c->get_label(),
				'operators' => $c->get_operators(),
			];
		}

		$actions = [];
		foreach ( $this->actions->all() as $a ) {
			$actions[] = [
				'slug'          => $a->get_slug(),
				'label'         => $a->get_label(),
				'group'         => $a->get_group(),
				'config_schema' => $a->get_config_schema(),
			];
		}

		return new WP_REST_Response( [
			'version'    => FLAVOR_FLOW_VERSION,
			'triggers'   => $triggers,
			'conditions' => $conditions,
			'actions'    => $actions,
		] );
	}
}
