<?php
/**
 * Default workflow notification email template.
 *
 * Available variables:
 *   $subject  — Email subject.
 *   $message  — Email body (HTML).
 *   $workflow — Workflow post object.
 *
 * @package FlavorFlow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $subject ?? '' ); ?></title>
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
		.container { max-width: 600px; margin: 0 auto; padding: 20px; }
		.header { background: #2271b1; color: #fff; padding: 20px; border-radius: 4px 4px 0 0; }
		.content { padding: 20px; background: #fff; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 4px 4px; }
		.footer { padding: 15px; text-align: center; font-size: 12px; color: #999; }
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<h1 style="margin: 0; font-size: 18px;">FlavorFlow Notification</h1>
		</div>
		<div class="content">
			<?php echo wp_kses_post( $message ?? '' ); ?>
		</div>
		<div class="footer">
			<p>Sent by FlavorFlow Workflow Automation</p>
		</div>
	</div>
</body>
</html>
