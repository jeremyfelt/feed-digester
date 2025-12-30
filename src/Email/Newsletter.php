<?php
/**
 * Newsletter composition and sending.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Email;

use WP_Error;

/**
 * Newsletter class.
 */
class Newsletter {

	/**
	 * The email sender.
	 *
	 * @var Sender
	 */
	private Sender $sender;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->sender = new Sender();
	}

	/**
	 * Send a digest newsletter.
	 *
	 * @param object $feed      The feed link object.
	 * @param int    $digest_id The digest post ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function send( object $feed, int $digest_id ): bool|WP_Error {
		$digest = get_post( $digest_id );

		if ( ! $digest ) {
			return new WP_Error( 'digest_not_found', __( 'Digest not found.', 'ai-feed-digest' ) );
		}

		$subject      = $digest->post_title;
		$html_content = $this->wrap_in_template( $digest->post_content, $feed );
		$plain_content = $this->generate_plain_text( $digest->post_content, $feed );

		$result = $this->sender->send( $subject, $html_content, $plain_content );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Update digest meta.
		update_post_meta( $digest_id, '_afd_sent_at', current_time( 'mysql' ) );
		update_post_meta( $digest_id, '_afd_recipient', $this->sender->get_recipient() );

		return true;
	}

	/**
	 * Wrap content in the HTML email template.
	 *
	 * @param string $content The digest content.
	 * @param object $feed    The feed link object.
	 * @return string The wrapped HTML.
	 */
	private function wrap_in_template( string $content, object $feed ): string {
		ob_start();
		include AFD_PLUGIN_DIR . 'templates/email/newsletter.php';
		return ob_get_clean();
	}

	/**
	 * Generate plain text version of the newsletter.
	 *
	 * @param string $content The digest content.
	 * @param object $feed    The feed link object.
	 * @return string The plain text content.
	 */
	private function generate_plain_text( string $content, object $feed ): string {
		ob_start();
		include AFD_PLUGIN_DIR . 'templates/email/newsletter-plain.php';
		return ob_get_clean();
	}

	/**
	 * Send a test email.
	 *
	 * @param string $recipient The recipient email address.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function send_test( string $recipient ): bool|WP_Error {
		$subject = __( 'AI Feed Digest - Test Email', 'ai-feed-digest' );

		$html_content = $this->get_test_email_html();
		$plain_content = $this->get_test_email_plain();

		return $this->sender->send( $subject, $html_content, $plain_content, $recipient );
	}

	/**
	 * Get test email HTML content.
	 *
	 * @return string The HTML content.
	 */
	private function get_test_email_html(): string {
		$site_name  = get_bloginfo( 'name' );
		$admin_url  = admin_url( 'admin.php?page=ai-feed-digest' );

		return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5;">
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
        <tr>
            <td style="padding: 40px 30px;">
                <h1 style="color: #333333; font-size: 24px; margin: 0 0 20px;">' . esc_html__( 'Test Email from AI Feed Digest', 'ai-feed-digest' ) . '</h1>
                <p style="color: #666666; font-size: 16px; line-height: 1.5; margin: 0 0 20px;">' . esc_html__( 'Congratulations! Your email settings are configured correctly.', 'ai-feed-digest' ) . '</p>
                <p style="color: #666666; font-size: 16px; line-height: 1.5; margin: 0 0 20px;">' . esc_html__( 'You will receive digest newsletters at this email address when they are generated.', 'ai-feed-digest' ) . '</p>
                <p style="color: #999999; font-size: 14px; margin: 0;">
                    ' . sprintf(
							/* translators: %s: site name */
							esc_html__( 'Sent from %s', 'ai-feed-digest' ),
							esc_html( $site_name )
						) . '
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding: 20px 30px; background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                <p style="color: #999999; font-size: 12px; margin: 0; text-align: center;">
                    <a href="' . esc_url( $admin_url ) . '" style="color: #0073aa;">' . esc_html__( 'Manage Settings', 'ai-feed-digest' ) . '</a>
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';
	}

	/**
	 * Get test email plain text content.
	 *
	 * @return string The plain text content.
	 */
	private function get_test_email_plain(): string {
		$site_name = get_bloginfo( 'name' );

		return __( 'Test Email from AI Feed Digest', 'ai-feed-digest' ) . "\n\n"
			. __( 'Congratulations! Your email settings are configured correctly.', 'ai-feed-digest' ) . "\n\n"
			. __( 'You will receive digest newsletters at this email address when they are generated.', 'ai-feed-digest' ) . "\n\n"
			. sprintf(
				/* translators: %s: site name */
				__( 'Sent from %s', 'ai-feed-digest' ),
				$site_name
			);
	}

	/**
	 * Preview a digest email without sending.
	 *
	 * @param int $digest_id The digest post ID.
	 * @return string|WP_Error The HTML preview or WP_Error.
	 */
	public function preview( int $digest_id ): string|WP_Error {
		$digest = get_post( $digest_id );

		if ( ! $digest ) {
			return new WP_Error( 'digest_not_found', __( 'Digest not found.', 'ai-feed-digest' ) );
		}

		$feed_id = get_post_meta( $digest_id, '_afd_feed_id', true );
		$feed    = $feed_id ? get_bookmark( $feed_id ) : null;

		if ( ! $feed ) {
			// Create a mock feed object.
			$feed = (object) array(
				'link_id'          => 0,
				'link_name'        => __( 'Sample Feed', 'ai-feed-digest' ),
				'link_description' => '',
			);
		}

		return $this->wrap_in_template( $digest->post_content, $feed );
	}
}
