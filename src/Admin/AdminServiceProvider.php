<?php
/**
 * Admin service provider.
 *
 * @package FlavorFlow\Admin
 */

declare(strict_types=1);

namespace flavor_flow\Admin;

use flavor_flow\Core\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->set(
			AdminPage::class,
			static fn() => new AdminPage()
		);

		$this->container->set(
			SettingsPage::class,
			static fn() => new SettingsPage()
		);
	}

	public function boot(): void {
		if ( ! is_admin() && ! wp_doing_cron() ) {
			return;
		}

		/** @var AdminPage $admin_page */
		$admin_page = $this->container->get( AdminPage::class );
		$admin_page->register();

		/** @var SettingsPage $settings_page */
		$settings_page = $this->container->get( SettingsPage::class );
		$settings_page->register();

		// Add "Settings" link on the plugins list page.
		add_filter(
			'plugin_action_links_' . FLAVOR_FLOW_BASENAME,
			static function ( array $links ): array {
				$url  = admin_url( 'admin.php?page=flavor-flow-settings' );
				$link = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $url ),
					esc_html__( 'Settings', 'flavor-flow' )
				);
				array_unshift( $links, $link );
				return $links;
			}
		);
	}
}
