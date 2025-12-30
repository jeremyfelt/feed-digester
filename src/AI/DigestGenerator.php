<?php
/**
 * Generate digests from feed content.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\AI;

use AIFeedDigest\Core\Links;
use AIFeedDigest\Feed\Repository;
use WP_Error;

/**
 * DigestGenerator class.
 */
class DigestGenerator {

	/**
	 * The feed link object.
	 *
	 * @var object
	 */
	private object $feed;

	/**
	 * The Gemini client.
	 *
	 * @var GeminiClient
	 */
	private GeminiClient $client;

	/**
	 * Constructor.
	 *
	 * @param object $feed The feed link object.
	 */
	public function __construct( object $feed ) {
		$this->feed   = $feed;
		$this->client = new GeminiClient();
	}

	/**
	 * Generate a digest for the feed.
	 *
	 * @return int|WP_Error The digest post ID on success, WP_Error on failure.
	 */
	public function generate(): int|WP_Error {
		// Check API configuration.
		if ( ! $this->client->is_configured() ) {
			return new WP_Error( 'no_api_key', __( 'Gemini API key not configured.', 'ai-feed-digest' ) );
		}

		// Get undigested items.
		$settings  = get_option( 'afd_general_settings', array() );
		$max_items = isset( $settings['items_per_digest'] ) ? absint( $settings['items_per_digest'] ) : 20;

		$items = Repository::get_undigested_items( $this->feed->link_id, $max_items );

		// Check if there are items to digest.
		$email_settings = get_option( 'afd_email_settings', array() );
		$send_empty     = isset( $email_settings['send_empty'] ) && $email_settings['send_empty'];

		if ( empty( $items ) && ! $send_empty ) {
			return new WP_Error(
				'no_items',
				__( 'No new items to include in the digest.', 'ai-feed-digest' )
			);
		}

		// Build the prompt.
		$prompt_builder = new PromptBuilder( $this->feed, $items );
		$prompt         = $prompt_builder->build();

		// Check estimated token count.
		$estimated_tokens = PromptBuilder::estimate_tokens( $prompt );
		if ( $estimated_tokens > 100000 ) {
			// Reduce number of items if prompt is too large.
			$items          = array_slice( $items, 0, 10 );
			$prompt_builder = new PromptBuilder( $this->feed, $items );
			$prompt         = $prompt_builder->build();
		}

		// Generate content with Gemini.
		$content = $this->client->generate_content( $prompt );

		if ( is_wp_error( $content ) ) {
			return $content;
		}

		// Clean up the generated content.
		$content = $this->clean_generated_content( $content );

		// Create the digest post.
		$digest_id = $this->create_digest_post( $content, $items );

		if ( is_wp_error( $digest_id ) ) {
			return $digest_id;
		}

		// Mark items as digested.
		$item_ids = wp_list_pluck( $items, 'ID' );
		Repository::mark_items_digested( $item_ids, $digest_id );

		return $digest_id;
	}

	/**
	 * Generate a preview digest without saving.
	 *
	 * @param int $max_items Maximum items to include.
	 * @return string|WP_Error The generated content or WP_Error.
	 */
	public function generate_preview( int $max_items = 5 ): string|WP_Error {
		if ( ! $this->client->is_configured() ) {
			return new WP_Error( 'no_api_key', __( 'Gemini API key not configured.', 'ai-feed-digest' ) );
		}

		$items = Repository::get_items_by_feed( $this->feed->link_id, $max_items );

		if ( empty( $items ) ) {
			return new WP_Error( 'no_items', __( 'No items available for preview.', 'ai-feed-digest' ) );
		}

		$prompt_builder = new PromptBuilder( $this->feed, $items );
		$prompt         = $prompt_builder->build();

		$content = $this->client->generate_content( $prompt );

		if ( is_wp_error( $content ) ) {
			return $content;
		}

		return $this->clean_generated_content( $content );
	}

	/**
	 * Create a digest post.
	 *
	 * @param string $content The digest content.
	 * @param array  $items   The items included in the digest.
	 * @return int|WP_Error The post ID or WP_Error.
	 */
	private function create_digest_post( string $content, array $items ): int|WP_Error {
		$frequency = Links::get_feed_meta( $this->feed->link_id, '_afd_digest_frequency', 'weekly' );
		$period    = ( 'monthly' === $frequency ) ? __( 'Monthly', 'ai-feed-digest' ) : __( 'Weekly', 'ai-feed-digest' );

		$title = sprintf(
			/* translators: 1: period (Weekly/Monthly), 2: feed name, 3: date */
			__( '%1$s Digest: %2$s - %3$s', 'ai-feed-digest' ),
			$period,
			$this->feed->link_name,
			wp_date( get_option( 'date_format' ) )
		);

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'afd_digest',
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_afd_feed_id', $this->feed->link_id );
		update_post_meta( $post_id, '_afd_items_count', count( $items ) );
		update_post_meta( $post_id, '_afd_sent_at', null ); // Will be updated when email is sent.

		return $post_id;
	}

	/**
	 * Clean up generated content.
	 *
	 * @param string $content The generated content.
	 * @return string The cleaned content.
	 */
	private function clean_generated_content( string $content ): string {
		// Remove markdown code blocks if present.
		$content = preg_replace( '/^```html?\s*/i', '', $content );
		$content = preg_replace( '/\s*```$/i', '', $content );

		// Remove leading/trailing whitespace.
		$content = trim( $content );

		// Ensure proper HTML structure.
		if ( ! preg_match( '/<[^>]+>/', $content ) ) {
			// Plain text - wrap in paragraphs.
			$paragraphs = explode( "\n\n", $content );
			$content    = '<p>' . implode( '</p><p>', array_filter( $paragraphs ) ) . '</p>';
		}

		// Allow only safe HTML.
		$content = wp_kses(
			$content,
			array(
				'h1'     => array(),
				'h2'     => array(),
				'h3'     => array(),
				'h4'     => array(),
				'p'      => array(),
				'br'     => array(),
				'strong' => array(),
				'b'      => array(),
				'em'     => array(),
				'i'      => array(),
				'ul'     => array(),
				'ol'     => array(),
				'li'     => array(),
				'a'      => array(
					'href'   => array(),
					'title'  => array(),
					'target' => array(),
				),
				'blockquote' => array(),
				'hr'     => array(),
				'div'    => array(
					'class' => array(),
				),
				'span'   => array(
					'class' => array(),
				),
			)
		);

		return $content;
	}
}
