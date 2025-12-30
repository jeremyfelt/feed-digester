<?php
/**
 * Feed fetching and parsing functionality.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Feed;

use AIFeedDigest\Core\Links;
use SimplePie;
use WP_Error;

/**
 * Fetcher class.
 */
class Fetcher {

	/**
	 * Fetch and process a feed.
	 *
	 * @param object $feed The feed link object.
	 * @return int|WP_Error Number of new items on success, WP_Error on failure.
	 */
	public function fetch( object $feed ): int|WP_Error {
		$feed_url = $this->get_feed_url( $feed );

		if ( empty( $feed_url ) ) {
			return new WP_Error( 'no_feed_url', __( 'No feed URL configured for this link.', 'ai-feed-digest' ) );
		}

		// Validate the URL.
		if ( ! filter_var( $feed_url, FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_feed_url', __( 'Invalid feed URL.', 'ai-feed-digest' ) );
		}

		// Fetch the feed using WordPress's built-in SimplePie wrapper.
		$rss = fetch_feed( $feed_url );

		if ( is_wp_error( $rss ) ) {
			return $rss;
		}

		$items_added = $this->process_feed_items( $feed, $rss );

		// Update last fetched time.
		Links::update_feed_meta( $feed->link_id, '_afd_last_fetched', current_time( 'mysql' ) );

		return $items_added;
	}

	/**
	 * Get the feed URL from a link object.
	 *
	 * @param object $feed The feed link object.
	 * @return string The feed URL.
	 */
	private function get_feed_url( object $feed ): string {
		// Prefer link_rss field as it's designed for RSS URLs.
		if ( ! empty( $feed->link_rss ) ) {
			return $feed->link_rss;
		}

		// Fall back to main link URL.
		return $feed->link_url ?? '';
	}

	/**
	 * Process feed items and store them.
	 *
	 * @param object    $feed The feed link object.
	 * @param SimplePie $rss  The SimplePie feed object.
	 * @return int Number of new items added.
	 */
	private function process_feed_items( object $feed, SimplePie $rss ): int {
		$items_added     = 0;
		$fetch_full      = Links::get_feed_meta( $feed->link_id, '_afd_fetch_full_content', false );
		$content_extractor = new ContentExtractor();

		$max_items = $rss->get_item_quantity( 50 );

		for ( $i = 0; $i < $max_items; $i++ ) {
			$item = $rss->get_item( $i );

			if ( ! $item ) {
				continue;
			}

			$guid = $item->get_id();

			// Skip if item already exists.
			if ( Repository::item_exists( $feed->link_id, $guid ) ) {
				continue;
			}

			$item_data = $this->extract_item_data( $item );
			$post_id   = Repository::create_item( $feed->link_id, $item_data );

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			// Fetch full content if enabled.
			if ( $fetch_full && ! empty( $item_data['url'] ) ) {
				$full_content = $content_extractor->extract( $item_data['url'] );

				if ( ! is_wp_error( $full_content ) && ! empty( $full_content ) ) {
					Repository::update_item_content( $post_id, $full_content );
				}
			}

			++$items_added;
		}

		return $items_added;
	}

	/**
	 * Extract data from a SimplePie item.
	 *
	 * @param \SimplePie_Item $item The feed item.
	 * @return array The extracted item data.
	 */
	private function extract_item_data( \SimplePie_Item $item ): array {
		$author = $item->get_author();

		return array(
			'title'   => $item->get_title() ?? '',
			'content' => $item->get_content() ?? '',
			'excerpt' => $item->get_description() ?? '',
			'url'     => $item->get_permalink() ?? '',
			'guid'    => $item->get_id() ?? '',
			'date'    => $this->format_date( $item->get_date( 'Y-m-d H:i:s' ) ),
			'author'  => $author ? $author->get_name() : '',
		);
	}

	/**
	 * Format a date string to MySQL format.
	 *
	 * @param string|null $date The date string.
	 * @return string The formatted date or current time if invalid.
	 */
	private function format_date( ?string $date ): string {
		if ( empty( $date ) ) {
			return current_time( 'mysql' );
		}

		$timestamp = strtotime( $date );

		if ( false === $timestamp ) {
			return current_time( 'mysql' );
		}

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Validate a feed URL by attempting to fetch it.
	 *
	 * @param string $url The feed URL to validate.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public static function validate_feed_url( string $url ): bool|WP_Error {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', __( 'Invalid URL format.', 'ai-feed-digest' ) );
		}

		$rss = fetch_feed( $url );

		if ( is_wp_error( $rss ) ) {
			return $rss;
		}

		if ( 0 === $rss->get_item_quantity() ) {
			return new WP_Error( 'empty_feed', __( 'The feed appears to be empty or invalid.', 'ai-feed-digest' ) );
		}

		return true;
	}
}
