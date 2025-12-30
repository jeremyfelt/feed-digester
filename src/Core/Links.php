<?php
/**
 * Enable and extend the WordPress Link Manager.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Core;

/**
 * Links class.
 */
class Links {

	/**
	 * Initialize the Links functionality.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Re-enable the Links Manager.
		add_filter( 'pre_option_link_manager_enabled', '__return_true' );

		// Add row actions for links.
		add_filter( 'link_row_actions', array( __CLASS__, 'add_row_actions' ), 10, 2 );

		// Handle AJAX actions.
		add_action( 'wp_ajax_afd_fetch_feed', array( __CLASS__, 'ajax_fetch_feed' ) );
		add_action( 'wp_ajax_afd_generate_digest', array( __CLASS__, 'ajax_generate_digest' ) );
	}

	/**
	 * Add custom row actions to the links list.
	 *
	 * @param array  $actions Existing row actions.
	 * @param object $link    The link object.
	 * @return array Modified row actions.
	 */
	public static function add_row_actions( array $actions, object $link ): array {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $actions;
		}

		$fetch_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'  => 'afd_fetch_feed',
					'link_id' => $link->link_id,
				),
				admin_url( 'admin-ajax.php' )
			),
			'afd_fetch_feed_' . $link->link_id
		);

		$digest_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'  => 'afd_generate_digest',
					'link_id' => $link->link_id,
				),
				admin_url( 'admin-ajax.php' )
			),
			'afd_generate_digest_' . $link->link_id
		);

		$actions['afd_fetch'] = sprintf(
			'<a href="%s" class="afd-fetch-feed" data-link-id="%d">%s</a>',
			esc_url( $fetch_url ),
			esc_attr( $link->link_id ),
			esc_html__( 'Fetch Now', 'ai-feed-digest' )
		);

		$actions['afd_digest'] = sprintf(
			'<a href="%s" class="afd-generate-digest" data-link-id="%d">%s</a>',
			esc_url( $digest_url ),
			esc_attr( $link->link_id ),
			esc_html__( 'Generate Digest', 'ai-feed-digest' )
		);

		return $actions;
	}

	/**
	 * AJAX handler for fetching a feed.
	 *
	 * @return void
	 */
	public static function ajax_fetch_feed(): void {
		$link_id = isset( $_GET['link_id'] ) ? absint( $_GET['link_id'] ) : 0;

		if ( ! $link_id || ! check_admin_referer( 'afd_fetch_feed_' . $link_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'ai-feed-digest' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-feed-digest' ) ) );
		}

		$link = get_bookmark( $link_id );

		if ( ! $link ) {
			wp_send_json_error( array( 'message' => __( 'Feed not found.', 'ai-feed-digest' ) ) );
		}

		$fetcher = new \AIFeedDigest\Feed\Fetcher();
		$result  = $fetcher->fetch( $link );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message'     => sprintf(
					/* translators: %d: number of items fetched */
					__( 'Successfully fetched %d items.', 'ai-feed-digest' ),
					$result
				),
				'items_count' => $result,
			)
		);
	}

	/**
	 * AJAX handler for generating a digest.
	 *
	 * @return void
	 */
	public static function ajax_generate_digest(): void {
		$link_id = isset( $_GET['link_id'] ) ? absint( $_GET['link_id'] ) : 0;

		if ( ! $link_id || ! check_admin_referer( 'afd_generate_digest_' . $link_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'ai-feed-digest' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-feed-digest' ) ) );
		}

		$link = get_bookmark( $link_id );

		if ( ! $link ) {
			wp_send_json_error( array( 'message' => __( 'Feed not found.', 'ai-feed-digest' ) ) );
		}

		$generator = new \AIFeedDigest\AI\DigestGenerator( $link );
		$digest    = $generator->generate();

		if ( is_wp_error( $digest ) ) {
			wp_send_json_error( array( 'message' => $digest->get_error_message() ) );
		}

		// Send the newsletter.
		$newsletter = new \AIFeedDigest\Email\Newsletter();
		$sent       = $newsletter->send( $link, $digest );

		if ( is_wp_error( $sent ) ) {
			wp_send_json_success(
				array(
					'message'   => __( 'Digest generated but email failed to send.', 'ai-feed-digest' ),
					'digest_id' => $digest,
				)
			);
		}

		// Update last digest sent time.
		update_metadata( 'link', $link_id, '_afd_last_digest_sent', current_time( 'mysql' ) );

		wp_send_json_success(
			array(
				'message'   => __( 'Digest generated and sent successfully!', 'ai-feed-digest' ),
				'digest_id' => $digest,
			)
		);
	}

	/**
	 * Get all active feed links.
	 *
	 * @return array Array of link objects.
	 */
	public static function get_active_feeds(): array {
		$all_links = get_bookmarks( array( 'hide_invisible' => false ) );
		$active    = array();

		foreach ( $all_links as $link ) {
			$is_active = get_metadata( 'link', $link->link_id, '_afd_is_active', true );
			// Default to active if not set.
			if ( '' === $is_active || $is_active ) {
				$active[] = $link;
			}
		}

		return $active;
	}

	/**
	 * Get feed metadata.
	 *
	 * @param int    $link_id  The link ID.
	 * @param string $meta_key The meta key.
	 * @param mixed  $default  Default value if not found.
	 * @return mixed The meta value.
	 */
	public static function get_feed_meta( int $link_id, string $meta_key, $default = '' ) {
		$value = get_metadata( 'link', $link_id, $meta_key, true );
		return '' === $value ? $default : $value;
	}

	/**
	 * Update feed metadata.
	 *
	 * @param int    $link_id    The link ID.
	 * @param string $meta_key   The meta key.
	 * @param mixed  $meta_value The meta value.
	 * @return bool True on success, false on failure.
	 */
	public static function update_feed_meta( int $link_id, string $meta_key, $meta_value ): bool {
		return (bool) update_metadata( 'link', $link_id, $meta_key, $meta_value );
	}
}
