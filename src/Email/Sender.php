<?php
/**
 * Email sending functionality.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Email;

use WP_Error;

/**
 * Sender class.
 */
class Sender {

	/**
	 * Email settings.
	 *
	 * @var array
	 */
	private array $settings;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings = get_option(
			'afd_email_settings',
			array(
				'recipient_email' => get_option( 'admin_email' ),
				'from_name'       => get_bloginfo( 'name' ),
				'subject_prefix'  => '[Feed Digest]',
			)
		);
	}

	/**
	 * Send an email.
	 *
	 * @param string $subject The email subject.
	 * @param string $html_content The HTML email content.
	 * @param string $plain_content The plain text email content.
	 * @param string $recipient Optional recipient email override.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function send( string $subject, string $html_content, string $plain_content = '', string $recipient = '' ): bool|WP_Error {
		$to = $recipient ?: $this->settings['recipient_email'];

		if ( empty( $to ) || ! is_email( $to ) ) {
			return new WP_Error( 'invalid_recipient', __( 'Invalid recipient email address.', 'ai-feed-digest' ) );
		}

		$subject = $this->format_subject( $subject );
		$headers = $this->get_headers();

		// Build the email body.
		$boundary = 'boundary-' . wp_generate_password( 12, false );
		$headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

		$body = $this->build_multipart_body( $html_content, $plain_content, $boundary );

		// Add filter to set content type.
		add_filter( 'wp_mail_content_type', array( $this, 'set_content_type' ) );

		$sent = wp_mail( $to, $subject, $body, $headers );

		// Remove the filter.
		remove_filter( 'wp_mail_content_type', array( $this, 'set_content_type' ) );

		if ( ! $sent ) {
			return new WP_Error( 'send_failed', __( 'Failed to send email.', 'ai-feed-digest' ) );
		}

		return true;
	}

	/**
	 * Format the email subject.
	 *
	 * @param string $subject The subject.
	 * @return string The formatted subject.
	 */
	private function format_subject( string $subject ): string {
		$prefix = $this->settings['subject_prefix'] ?? '';

		if ( ! empty( $prefix ) ) {
			return $prefix . ' ' . $subject;
		}

		return $subject;
	}

	/**
	 * Get email headers.
	 *
	 * @return array The headers.
	 */
	private function get_headers(): array {
		$headers = array();

		$from_name = $this->settings['from_name'] ?? get_bloginfo( 'name' );
		$from_email = get_option( 'admin_email' );

		$headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
		$headers[] = 'Reply-To: ' . $from_email;

		return $headers;
	}

	/**
	 * Build a multipart email body.
	 *
	 * @param string $html_content The HTML content.
	 * @param string $plain_content The plain text content.
	 * @param string $boundary The MIME boundary.
	 * @return string The multipart body.
	 */
	private function build_multipart_body( string $html_content, string $plain_content, string $boundary ): string {
		// Generate plain text if not provided.
		if ( empty( $plain_content ) ) {
			$plain_content = $this->html_to_plain_text( $html_content );
		}

		$body = "--{$boundary}\r\n";
		$body .= "Content-Type: text/plain; charset=UTF-8\r\n";
		$body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
		$body .= quoted_printable_encode( $plain_content ) . "\r\n";

		$body .= "--{$boundary}\r\n";
		$body .= "Content-Type: text/html; charset=UTF-8\r\n";
		$body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
		$body .= quoted_printable_encode( $html_content ) . "\r\n";

		$body .= "--{$boundary}--";

		return $body;
	}

	/**
	 * Convert HTML to plain text.
	 *
	 * @param string $html The HTML content.
	 * @return string The plain text content.
	 */
	private function html_to_plain_text( string $html ): string {
		// Convert links to text format.
		$html = preg_replace( '/<a\s+href=["\']([^"\']+)["\'][^>]*>([^<]+)<\/a>/i', '$2 ($1)', $html );

		// Convert headers to uppercase with spacing.
		$html = preg_replace( '/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', "\n\n--- $1 ---\n\n", $html );

		// Convert line breaks and paragraphs.
		$html = preg_replace( '/<br\s*\/?>/i', "\n", $html );
		$html = preg_replace( '/<\/p>/i', "\n\n", $html );
		$html = preg_replace( '/<p[^>]*>/i', '', $html );

		// Convert lists.
		$html = preg_replace( '/<li[^>]*>/i', "  - ", $html );
		$html = preg_replace( '/<\/li>/i', "\n", $html );
		$html = preg_replace( '/<\/?[uo]l[^>]*>/i', "\n", $html );

		// Convert horizontal rules.
		$html = preg_replace( '/<hr\s*\/?>/i', "\n---\n", $html );

		// Strip remaining HTML.
		$text = wp_strip_all_tags( $html );

		// Clean up whitespace.
		$text = preg_replace( '/[ \t]+/', ' ', $text );
		$text = preg_replace( '/\n{3,}/', "\n\n", $text );

		return trim( $text );
	}

	/**
	 * Set the content type for wp_mail.
	 *
	 * @return string The content type.
	 */
	public function set_content_type(): string {
		return 'multipart/alternative';
	}

	/**
	 * Get the recipient email.
	 *
	 * @return string The recipient email.
	 */
	public function get_recipient(): string {
		return $this->settings['recipient_email'] ?? get_option( 'admin_email' );
	}

	/**
	 * Get the from name.
	 *
	 * @return string The from name.
	 */
	public function get_from_name(): string {
		return $this->settings['from_name'] ?? get_bloginfo( 'name' );
	}
}
