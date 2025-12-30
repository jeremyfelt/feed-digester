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
	}

	/**
	 * Register cron hook callbacks.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( self::FETCH_HOOK, array( __CLASS__, 'fetch_all_feeds' ) );
		add_action( self::DIGEST_HOOK, array( __CLASS__, 'process_digests' ) );
		add_action( self::CLEANUP_HOOK, array( __CLASS__, 'cleanup_old_items' ) );
	}

	/**
	 * Fetch all active feeds.
	 *
	 * @return void
	 */
	public static function fetch_all_feeds(): void {
		$feeds = Links::get_active_feeds();

		foreach ( $feeds as $feed ) {
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
