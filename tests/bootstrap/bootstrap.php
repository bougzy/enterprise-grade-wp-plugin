<?php
/**
 * PHPUnit bootstrap.
 *
 * For unit tests that do NOT require WordPress loaded, this file sets up
 * the Composer autoloader and stubs/mocks for WP functions.
 *
 * @package FlavorFlow\Tests
 */

declare(strict_types=1);

// Composer autoloader.
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// Define constants that the plugin expects.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wp/' );
}

if ( ! defined( 'FLAVOR_FLOW_VERSION' ) ) {
	define( 'FLAVOR_FLOW_VERSION', '1.0.0' );
}

if ( ! defined( 'FLAVOR_FLOW_DIR' ) ) {
	define( 'FLAVOR_FLOW_DIR', dirname( __DIR__, 2 ) . '/' );
}

if ( ! defined( 'FLAVOR_FLOW_URL' ) ) {
	define( 'FLAVOR_FLOW_URL', 'https://example.com/wp-content/plugins/flavor-flow/' );
}

if ( ! defined( 'FLAVOR_FLOW_BASENAME' ) ) {
	define( 'FLAVOR_FLOW_BASENAME', 'flavor-flow/flavor-flow.php' );
}

if ( ! defined( 'FLAVOR_FLOW_FILE' ) ) {
	define( 'FLAVOR_FLOW_FILE', dirname( __DIR__, 2 ) . '/flavor-flow.php' );
}

// Stub WordPress functions used in unit-testable code.
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $str ): string {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( string $key ): string {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = [] ): array {
		if ( is_object( $args ) ) {
			$args = get_object_vars( $args );
		}
		return array_merge( $defaults, (array) $args );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( '_x' ) ) {
	function _x( string $text, string $context, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( string $hook, ...$args ) {
		return $args[0] ?? null;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( string $hook, ...$args ): void {}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option, $default = false ) {
		return $default;
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ): int {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'is_email' ) ) {
	function is_email( string $email ) {
		return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false ? $email : false;
	}
}
