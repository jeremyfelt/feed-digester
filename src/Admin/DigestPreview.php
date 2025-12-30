<?php
/**
 * Digest preview admin page.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Admin;

/**
 * DigestPreview class.
 */
class DigestPreview {

	/**
	 * Initialize the digest preview page.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		add_action( 'wp_ajax_afd_resend_digest', array( __CLASS__, 'ajax_resend_digest' ) );
	}

	/**
	 * Add the menu page.
	 *
	 * @return void
	 */
	public static function add_menu_page(): void {
		add_submenu_page(
			'ai-feed-digest',
			__( 'Digest Preview', 'ai-feed-digest' ),
			__( 'Preview', 'ai-feed-digest' ),
			'manage_options',
			'afd-digest-preview',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render the digest preview page.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include AFD_PLUGIN_DIR . 'templates/admin/digest-preview.php';
	}

	/**
	 * Get recent digests.
	 *
	 * @param int $limit Number of digests to return.
	 * @return array Array of digest posts.
	 */
	public static function get_recent_digests( int $limit = 20 ): array {
		return get_posts(
			array(
				'post_type'      => 'afd_digest',
				'posts_per_page' => $limit,
				'post_status'    => 'publish',
				'orderby'        => 'post_date',
				'order'          => 'DESC',
			)
		);
	}

	/**
	 * Get digest details.
	 *
	 * @param int $digest_id The digest post ID.
	 * @return array|null The digest details or null.
	 */
	public static function get_digest_details( int $digest_id ): ?array {
		$digest = get_post( $digest_id );

		if ( ! $digest || 'afd_digest' !== $digest->post_type ) {
			return null;
		}

		$feed_id     = get_post_meta( $digest_id, '_afd_feed_id', true );
		$items_count = get_post_meta( $digest_id, '_afd_items_count', true );
		$sent_at     = get_post_meta( $digest_id, '_afd_sent_at', true );
		$recipient   = get_post_meta( $digest_id, '_afd_recipient', true );

		$feed = $feed_id ? get_bookmark( $feed_id ) : null;

		return array(
			'id'          => $digest_id,
			'title'       => $digest->post_title,
			'content'     => $digest->post_content,
			'date'        => $digest->post_date,
			'feed_id'     => $feed_id,
			'feed_name'   => $feed ? $feed->link_name : __( 'Unknown Feed', 'ai-feed-digest' ),
			'items_count' => $items_count,
			'sent_at'     => $sent_at,
			'recipient'   => $recipient,
		);
	}

	/**
	 * AJAX handler for resending a digest.
	 *
	 * @return void
	 */
	public static function ajax_resend_digest(): void {
		check_ajax_referer( 'afd_resend_digest', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-feed-digest' ) ) );
		}

		$digest_id = isset( $_POST['digest_id'] ) ? absint( $_POST['digest_id'] ) : 0;

		if ( ! $digest_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid digest ID.', 'ai-feed-digest' ) ) );
		}

		$details = self::get_digest_details( $digest_id );

		if ( ! $details ) {
			wp_send_json_error( array( 'message' => __( 'Digest not found.', 'ai-feed-digest' ) ) );
		}

		$feed = $details['feed_id'] ? get_bookmark( $details['feed_id'] ) : null;

		if ( ! $feed ) {
			// Create a mock feed object for sending.
			$feed = (object) array(
				'link_id'   => 0,
				'link_name' => $details['feed_name'],
			);
		}

		$newsletter = new \AIFeedDigest\Email\Newsletter();
		$result     = $newsletter->send( $feed, $digest_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Digest resent successfully!', 'ai-feed-digest' ),
			)
		);
	}

	/**
	 * Delete a digest.
	 *
	 * @param int $digest_id The digest ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_digest( int $digest_id ): bool {
		$digest = get_post( $digest_id );

		if ( ! $digest || 'afd_digest' !== $digest->post_type ) {
			return false;
		}

		return (bool) wp_delete_post( $digest_id, true );
	}
}
