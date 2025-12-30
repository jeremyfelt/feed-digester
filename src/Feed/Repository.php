<?php
/**
 * Repository for storing and querying feed items.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Feed;

use WP_Error;
use WP_Post;

/**
 * Repository class.
 */
class Repository {

	/**
	 * Get feed items that haven't been included in a digest yet.
	 *
	 * @param int $feed_id The feed (link) ID.
	 * @param int $limit   Maximum number of items to return.
	 * @return array Array of WP_Post objects.
	 */
	public static function get_undigested_items( int $feed_id, int $limit = 50 ): array {
		return get_posts(
			array(
				'post_type'      => 'afd_feed_item',
				'posts_per_page' => $limit,
				'post_status'    => 'publish',
				'orderby'        => 'post_date',
				'order'          => 'DESC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => '_afd_feed_id',
						'value' => $feed_id,
						'type'  => 'NUMERIC',
					),
					array(
						'relation' => 'OR',
						array(
							'key'   => '_afd_included_in_digest',
							'value' => '0',
						),
						array(
							'key'     => '_afd_included_in_digest',
							'compare' => 'NOT EXISTS',
						),
					),
				),
			)
		);
	}

	/**
	 * Get all feed items for a specific feed.
	 *
	 * @param int    $feed_id The feed (link) ID.
	 * @param int    $limit   Maximum number of items to return.
	 * @param string $status  Post status to query.
	 * @return array Array of WP_Post objects.
	 */
	public static function get_items_by_feed( int $feed_id, int $limit = 50, string $status = 'publish' ): array {
		return get_posts(
			array(
				'post_type'      => 'afd_feed_item',
				'posts_per_page' => $limit,
				'post_status'    => $status,
				'orderby'        => 'post_date',
				'order'          => 'DESC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_afd_feed_id',
						'value' => $feed_id,
						'type'  => 'NUMERIC',
					),
				),
			)
		);
	}

	/**
	 * Check if a feed item already exists by GUID.
	 *
	 * @param int    $feed_id The feed (link) ID.
	 * @param string $guid    The unique identifier from the feed.
	 * @return bool True if item exists, false otherwise.
	 */
	public static function item_exists( int $feed_id, string $guid ): bool {
		$existing = get_posts(
			array(
				'post_type'      => 'afd_feed_item',
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => '_afd_feed_id',
						'value' => $feed_id,
						'type'  => 'NUMERIC',
					),
					array(
						'key'   => '_afd_guid',
						'value' => $guid,
					),
				),
				'fields'         => 'ids',
			)
		);

		return ! empty( $existing );
	}

	/**
	 * Create a new feed item from parsed feed data.
	 *
	 * @param int   $feed_id   The feed (link) ID.
	 * @param array $item_data The item data containing title, content, excerpt, etc.
	 * @return int|WP_Error The post ID on success, WP_Error on failure.
	 */
	public static function create_item( int $feed_id, array $item_data ): int|WP_Error {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'afd_feed_item',
				'post_title'   => sanitize_text_field( $item_data['title'] ?? '' ),
				'post_content' => wp_kses_post( $item_data['content'] ?? '' ),
				'post_excerpt' => wp_kses_post( $item_data['excerpt'] ?? '' ),
				'post_date'    => $item_data['date'] ?? current_time( 'mysql' ),
				'post_status'  => 'publish',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_afd_feed_id', $feed_id );
		update_post_meta( $post_id, '_afd_guid', sanitize_text_field( $item_data['guid'] ?? '' ) );
		update_post_meta( $post_id, '_afd_source_url', esc_url_raw( $item_data['url'] ?? '' ) );
		update_post_meta( $post_id, '_afd_author', sanitize_text_field( $item_data['author'] ?? '' ) );
		update_post_meta( $post_id, '_afd_included_in_digest', 0 );

		return $post_id;
	}

	/**
	 * Update a feed item's content.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $content The new content.
	 * @return int|WP_Error The post ID on success, WP_Error on failure.
	 */
	public static function update_item_content( int $post_id, string $content ): int|WP_Error {
		return wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => wp_kses_post( $content ),
			)
		);
	}

	/**
	 * Mark items as included in a digest.
	 *
	 * @param array $post_ids  Array of post IDs.
	 * @param int   $digest_id The digest post ID.
	 * @return void
	 */
	public static function mark_items_digested( array $post_ids, int $digest_id ): void {
		foreach ( $post_ids as $post_id ) {
			update_post_meta( $post_id, '_afd_included_in_digest', 1 );
			update_post_meta( $post_id, '_afd_digest_id', $digest_id );
		}
	}

	/**
	 * Get the count of feed items for a feed.
	 *
	 * @param int  $feed_id     The feed (link) ID.
	 * @param bool $undigested_only Only count undigested items.
	 * @return int The count.
	 */
	public static function get_item_count( int $feed_id, bool $undigested_only = false ): int {
		$meta_query = array(
			array(
				'key'   => '_afd_feed_id',
				'value' => $feed_id,
				'type'  => 'NUMERIC',
			),
		);

		if ( $undigested_only ) {
			$meta_query['relation'] = 'AND';
			$meta_query[]           = array(
				'relation' => 'OR',
				array(
					'key'   => '_afd_included_in_digest',
					'value' => '0',
				),
				array(
					'key'     => '_afd_included_in_digest',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		$query = new \WP_Query(
			array(
				'post_type'      => 'afd_feed_item',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'fields'         => 'ids',
			)
		);

		return $query->found_posts;
	}

	/**
	 * Get feed item by GUID.
	 *
	 * @param int    $feed_id The feed (link) ID.
	 * @param string $guid    The unique identifier from the feed.
	 * @return WP_Post|null The post object or null.
	 */
	public static function get_item_by_guid( int $feed_id, string $guid ): ?WP_Post {
		$posts = get_posts(
			array(
				'post_type'      => 'afd_feed_item',
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => '_afd_feed_id',
						'value' => $feed_id,
						'type'  => 'NUMERIC',
					),
					array(
						'key'   => '_afd_guid',
						'value' => $guid,
					),
				),
			)
		);

		return ! empty( $posts ) ? $posts[0] : null;
	}
}
