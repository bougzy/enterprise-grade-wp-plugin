<?php
/**
 * Action execution result DTO.
 *
 * @package FlavorFlow\Action
 */

declare(strict_types=1);

namespace flavor_flow\Action;

/**
 * Immutable result object returned by every action execution.
 */
final class ActionResult {

	private bool $success;
	private string $message;
	private array $data;

	private function __construct( bool $success, string $message, array $data ) {
		$this->success = $success;
		$this->message = $message;
		$this->data    = $data;
	}

	public static function success( string $message = '', array $data = [] ): self {
		return new self( true, $message, $data );
	}

	public static function failure( string $message = '', array $data = [] ): self {
		return new self( false, $message, $data );
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_message(): string {
		return $this->message;
	}

	public function get_data(): array {
		return $this->data;
	}

	public function to_array(): array {
		return [
			'success' => $this->success,
			'message' => $this->message,
			'data'    => $this->data,
		];
	}
}
