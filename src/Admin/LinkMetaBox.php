<?php
/**
 * Meta boxes for the link edit screen.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Admin;

use AIFeedDigest\Core\Links;

/**
 * LinkMetaBox class.
 */
class LinkMetaBox {

	/**
	 * Initialize the meta boxes.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'add_meta_boxes_link', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'edit_link', array( __CLASS__, 'save_meta' ) );
		add_action( 'add_link', array( __CLASS__, 'save_meta' ) );
	}

	/**
	 * Add meta boxes to the link edit screen.
	 *
	 * @return void
	 */
	public static function add_meta_boxes(): void {
		add_meta_box(
			'afd_digest_settings',
			__( 'AI Digest Settings', 'ai-feed-digest' ),
			array( __CLASS__, 'render_digest_settings_box' ),
			'link',
			'normal',
			'high'
		);

		add_meta_box(
			'afd_feed_status',
			__( 'Feed Status', 'ai-feed-digest' ),
			array( __CLASS__, 'render_feed_status_box' ),
			'link',
			'side',
			'default'
		);
	}

	/**
	 * Render the digest settings meta box.
	 *
	 * @param object $link The link object.
	 * @return void
	 */
	public static function render_digest_settings_box( object $link ): void {
		wp_nonce_field( 'afd_save_link_meta', 'afd_link_meta_nonce' );

		$link_id = $link->link_id ?? 0;

		include AFD_PLUGIN_DIR . 'templates/admin/link-meta-box.php';
	}

	/**
	 * Render the feed status meta box.
	 *
	 * @param object $link The link object.
	 * @return void
	 */
	public static function render_feed_status_box( object $link ): void {
		$link_id = $link->link_id ?? 0;

		if ( ! $link_id ) {
			echo '<p>' . esc_html__( 'Save the link to view status.', 'ai-feed-digest' ) . '</p>';
			return;
		}

		$last_fetched = Links::get_feed_meta( $link_id, '_afd_last_fetched' );
		$last_digest  = Links::get_feed_meta( $link_id, '_afd_last_digest_sent' );
		$is_active    = Links::get_feed_meta( $link_id, '_afd_is_active', true );

		$item_count     = \AIFeedDigest\Feed\Repository::get_item_count( $link_id );
		$undigested     = \AIFeedDigest\Feed\Repository::get_item_count( $link_id, true );
		?>
		<div class="afd-feed-status">
			<p>
				<strong><?php esc_html_e( 'Status:', 'ai-feed-digest' ); ?></strong>
				<?php if ( $is_active ) : ?>
					<span class="afd-status-active"><?php esc_html_e( 'Active', 'ai-feed-digest' ); ?></span>
				<?php else : ?>
					<span class="afd-status-inactive"><?php esc_html_e( 'Inactive', 'ai-feed-digest' ); ?></span>
				<?php endif; ?>
			</p>

			<p>
				<strong><?php esc_html_e( 'Last Fetched:', 'ai-feed-digest' ); ?></strong><br />
				<?php echo $last_fetched ? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_fetched ) ) ) : esc_html__( 'Never', 'ai-feed-digest' ); ?>
			</p>

			<p>
				<strong><?php esc_html_e( 'Last Digest:', 'ai-feed-digest' ); ?></strong><br />
				<?php echo $last_digest ? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_digest ) ) ) : esc_html__( 'Never', 'ai-feed-digest' ); ?>
			</p>

			<p>
				<strong><?php esc_html_e( 'Items:', 'ai-feed-digest' ); ?></strong><br />
				<?php
				printf(
					/* translators: 1: total items, 2: undigested items */
					esc_html__( '%1$d total, %2$d pending digest', 'ai-feed-digest' ),
					$item_count,
					$undigested
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Save link meta data.
	 *
	 * @param int $link_id The link ID.
	 * @return void
	 */
	public static function save_meta( int $link_id ): void {
		// Verify nonce.
		if ( ! isset( $_POST['afd_link_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['afd_link_meta_nonce'] ), 'afd_save_link_meta' ) ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_links' ) ) {
			return;
		}

		// Save digest frequency.
		$frequency = isset( $_POST['afd_digest_frequency'] ) ? sanitize_text_field( wp_unslash( $_POST['afd_digest_frequency'] ) ) : 'weekly';
		if ( in_array( $frequency, array( 'weekly', 'monthly' ), true ) ) {
			Links::update_feed_meta( $link_id, '_afd_digest_frequency', $frequency );
		}

		// Save fetch full content option.
		$fetch_full = ! empty( $_POST['afd_fetch_full_content'] );
		Links::update_feed_meta( $link_id, '_afd_fetch_full_content', $fetch_full );

		// Save active status.
		$is_active = ! empty( $_POST['afd_is_active'] );
		Links::update_feed_meta( $link_id, '_afd_is_active', $is_active );

		// Save custom prompt.
		$custom_prompt = isset( $_POST['afd_custom_prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['afd_custom_prompt'] ) ) : '';
		Links::update_feed_meta( $link_id, '_afd_custom_prompt', $custom_prompt );

		// Save feed type.
		$feed_type = isset( $_POST['afd_feed_type'] ) ? sanitize_text_field( wp_unslash( $_POST['afd_feed_type'] ) ) : 'general';
		$valid_types = array_keys( \AIFeedDigest\Core\Plugin::get_feed_types() );
		if ( in_array( $feed_type, $valid_types, true ) ) {
			Links::update_feed_meta( $link_id, '_afd_feed_type', $feed_type );
		}
	}

	/**
	 * Get the digest frequency for a link.
	 *
	 * @param int $link_id The link ID.
	 * @return string The frequency.
	 */
	public static function get_frequency( int $link_id ): string {
		$frequency = Links::get_feed_meta( $link_id, '_afd_digest_frequency' );

		if ( empty( $frequency ) ) {
			$settings = get_option( 'afd_general_settings', array() );
			return $settings['default_frequency'] ?? 'weekly';
		}

		return $frequency;
	}

	/**
	 * Get the fetch full content setting for a link.
	 *
	 * @param int $link_id The link ID.
	 * @return bool Whether to fetch full content.
	 */
	public static function get_fetch_full_content( int $link_id ): bool {
		$value = Links::get_feed_meta( $link_id, '_afd_fetch_full_content' );

		if ( '' === $value ) {
			$settings = get_option( 'afd_general_settings', array() );
			return ! empty( $settings['fetch_full_content'] );
		}

		return (bool) $value;
	}

	/**
	 * Get the active status for a link.
	 *
	 * @param int $link_id The link ID.
	 * @return bool Whether the link is active.
	 */
	public static function get_is_active( int $link_id ): bool {
		$value = Links::get_feed_meta( $link_id, '_afd_is_active' );

		// Default to active if not set.
		return '' === $value || (bool) $value;
	}

	/**
	 * Get the feed type for a link.
	 *
	 * @param int $link_id The link ID.
	 * @return string The feed type.
	 */
	public static function get_feed_type( int $link_id ): string {
		$feed_type = Links::get_feed_meta( $link_id, '_afd_feed_type' );

		if ( empty( $feed_type ) ) {
			return \AIFeedDigest\Core\Plugin::FEED_TYPE_GENERAL;
		}

		return $feed_type;
	}
}
