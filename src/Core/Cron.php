<?php
/**
 * WP-Cron scheduling for feed fetching and digest generation.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Core;

use AIFeedDigest\Feed;
use AIFeedDigest\AI;
use AIFeedDigest\Email;

/**
 * Cron class.
 */
class Cron {

	/**
	 * Hook name for fetching feeds.
	 *
	 * @var string
	 */
	public const FETCH_HOOK = 'afd_fetch_feeds';

	/**
	 * Hook name for generating digests.
	 *
	 * @var string
	 */
	public const DIGEST_HOOK = 'afd_generate_digests';

	/**
	 * Hook name for cleaning up old items.
	 *
	 * @var string
	 */
	public const CLEANUP_HOOK = 'afd_cleanup_old_items';

	/**
	 * Hook name for fetching a single feed.
	 *
	 * @var string
	 */
	public const FETCH_SINGLE_HOOK = 'afd_fetch_single_feed';

	/**
	 * Schedule cron events.
	 *
	 * @return void
	 */
	public static function schedule_events(): void {
		if ( ! wp_next_scheduled( self::FETCH_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::FETCH_HOOK );
		}

		if ( ! wp_next_scheduled( self::DIGEST_HOOK ) ) {
			// Run digest check daily; individual feeds checked for their frequency.
			wp_schedule_event( time(), 'daily', self::DIGEST_HOOK );
		}

		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CLEANUP_HOOK );
		}
	}

	/**
	 * Unschedule cron events.
	 *
	 * @return void
	 */
	public static function unschedule_events(): void {
		wp_clear_scheduled_hook( self::FETCH_HOOK );
		wp_clear_scheduled_hook( self::DIGEST_HOOK );
		wp_clear_scheduled_hook( self::CLEANUP_HOOK );

		// Clear all scheduled single feed fetches.
		$feeds = Links::get_active_feeds();
		foreach ( $feeds as $feed ) {
			wp_clear_scheduled_hook( self::FETCH_SINGLE_HOOK, array( $feed->link_id ) );
		}
	}

	/**
	 * Register cron hook callbacks.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( self::FETCH_HOOK, array( __CLASS__, 'schedule_feed_fetches' ) );
		add_action( self::FETCH_SINGLE_HOOK, array( __CLASS__, 'fetch_single_feed' ), 10, 1 );
		add_action( self::DIGEST_HOOK, array( __CLASS__, 'process_digests' ) );
		add_action( self::CLEANUP_HOOK, array( __CLASS__, 'cleanup_old_items' ) );
	}

	/**
	 * Schedule individual feed fetches at random times throughout the day.
	 *
	 * Instead of fetching all feeds at once, this schedules each feed
	 * to be fetched at a random time within the next 24 hours.
	 *
	 * @return void
	 */
	public static function schedule_feed_fetches(): void {
		$feeds = Links::get_active_feeds();

		foreach ( $feeds as $feed ) {
			// Skip if this feed already has a pending fetch scheduled.
			$scheduled = wp_next_scheduled( self::FETCH_SINGLE_HOOK, array( $feed->link_id ) );
			if ( $scheduled ) {
				continue;
			}

			// Schedule fetch at a random time within the next 24 hours.
			// Use feed ID as seed for consistent-ish timing per feed.
			$random_offset = self::get_random_offset_for_feed( $feed->link_id );
			$scheduled_time = time() + $random_offset;

			wp_schedule_single_event( $scheduled_time, self::FETCH_SINGLE_HOOK, array( $feed->link_id ) );
		}
	}

	/**
	 * Get a random time offset for a feed (0 to 24 hours in seconds).
	 *
	 * Uses the feed ID as a seed combined with the current date to provide
	 * consistent timing for each feed on a given day while varying day-to-day.
	 *
	 * @param int $feed_id The feed link ID.
	 * @return int Random offset in seconds (0 to DAY_IN_SECONDS).
	 */
	private static function get_random_offset_for_feed( int $feed_id ): int {
		// Combine feed ID with current date for a seed that varies daily.
		$seed = $feed_id + intval( gmdate( 'Ymd' ) );
		mt_srand( $seed );
		$offset = mt_rand( 0, DAY_IN_SECONDS );
		mt_srand(); // Reset the random seed.

		return $offset;
	}

	/**
	 * Fetch a single feed by its link ID.
	 *
	 * @param int $link_id The feed link ID.
	 * @return void
	 */
	public static function fetch_single_feed( int $link_id ): void {
		$feed = Links::get_feed_by_id( $link_id );

		if ( ! $feed ) {
			self::log_error(
				sprintf(
					/* translators: %d: feed link ID */
					__( 'Feed with ID %d not found for scheduled fetch.', 'ai-feed-digest' ),
					$link_id
				)
			);
			return;
		}

		$fetcher = new Feed\Fetcher();
		$result  = $fetcher->fetch( $feed );

		if ( is_wp_error( $result ) ) {
			self::log_error(
				sprintf(
					/* translators: 1: feed name, 2: error message */
					__( 'Failed to fetch feed "%1$s": %2$s', 'ai-feed-digest' ),
					$feed->link_name,
					$result->get_error_message()
				)
			);
		}
	}

	/**
	 * Process digests for feeds that are due.
	 *
	 * @return void
	 */
	public static function process_digests(): void {
		$feeds = Links::get_active_feeds();

		foreach ( $feeds as $feed ) {
			if ( ! self::is_digest_due( $feed ) ) {
				continue;
			}

			$generator = new AI\DigestGenerator( $feed );
			$digest    = $generator->generate();

			if ( is_wp_error( $digest ) ) {
				self::log_error(
					sprintf(
						/* translators: 1: feed name, 2: error message */
						__( 'Failed to generate digest for "%1$s": %2$s', 'ai-feed-digest' ),
						$feed->link_name,
						$digest->get_error_message()
					)
				);
				continue;
			}

			$newsletter = new Email\Newsletter();
			$sent       = $newsletter->send( $feed, $digest );

			if ( is_wp_error( $sent ) ) {
				self::log_error(
					sprintf(
						/* translators: 1: feed name, 2: error message */
						__( 'Failed to send digest email for "%1$s": %2$s', 'ai-feed-digest' ),
						$feed->link_name,
						$sent->get_error_message()
					)
				);
				continue;
			}

			update_metadata( 'link', $feed->link_id, '_afd_last_digest_sent', current_time( 'mysql' ) );
		}
	}

	/**
	 * Clean up old feed items.
	 *
	 * @return void
	 */
	public static function cleanup_old_items(): void {
		$settings         = get_option( 'afd_general_settings', array() );
		$cleanup_days     = isset( $settings['cleanup_after_days'] ) ? absint( $settings['cleanup_after_days'] ) : 90;
		$cleanup_date     = gmdate( 'Y-m-d H:i:s', strtotime( "-{$cleanup_days} days" ) );

		$old_items = get_posts(
			array(
				'post_type'      => 'afd_feed_item',
				'posts_per_page' => 100, // Process in batches.
				'post_status'    => 'any',
				'date_query'     => array(
					array(
						'before' => $cleanup_date,
					),
				),
				'meta_query'     => array(
					array(
						'key'   => '_afd_included_in_digest',
						'value' => '1',
					),
				),
				'fields'         => 'ids',
			)
		);

		foreach ( $old_items as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Check if a digest is due for a feed.
	 *
	 * @param object $feed The feed link object.
	 * @return bool True if digest is due, false otherwise.
	 */
	private static function is_digest_due( object $feed ): bool {
		$frequency = Links::get_feed_meta( $feed->link_id, '_afd_digest_frequency', 'weekly' );
		$last_sent = Links::get_feed_meta( $feed->link_id, '_afd_last_digest_sent' );

		if ( ! $last_sent ) {
			return true; // Never sent, so it's due.
		}

		$last_sent_time = strtotime( $last_sent );
		$interval       = ( 'monthly' === $frequency ) ? MONTH_IN_SECONDS : WEEK_IN_SECONDS;

		return ( time() - $last_sent_time ) >= $interval;
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message The error message.
	 * @return void
	 */
	private static function log_error( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[AI Feed Digest] ' . $message );
		}
	}
}
