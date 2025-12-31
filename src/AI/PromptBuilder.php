<?php
/**
 * Build prompts from template and feed data.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\AI;

use AIFeedDigest\Core\Links;

/**
 * PromptBuilder class.
 */
class PromptBuilder {

	/**
	 * The feed link object.
	 *
	 * @var object
	 */
	private object $feed;

	/**
	 * Array of feed items (WP_Post objects).
	 *
	 * @var array
	 */
	private array $items;

	/**
	 * The prompt template.
	 *
	 * @var string
	 */
	private string $template;

	/**
	 * Constructor.
	 *
	 * @param object $feed  The feed link object.
	 * @param array  $items Array of feed items (WP_Post objects).
	 */
	public function __construct( object $feed, array $items ) {
		$this->feed  = $feed;
		$this->items = $items;

		// Check for per-feed custom prompt override (highest priority).
		$custom_prompt = Links::get_feed_meta( $feed->link_id, '_afd_custom_prompt' );

		if ( ! empty( $custom_prompt ) ) {
			$this->template = $custom_prompt;
		} else {
			// Get the feed type and use its specific template.
			$feed_type = Links::get_feed_meta( $feed->link_id, '_afd_feed_type' );

			if ( ! empty( $feed_type ) && \AIFeedDigest\Core\Plugin::FEED_TYPE_GENERAL !== $feed_type ) {
				// Use the feed type-specific template.
				$this->template = \AIFeedDigest\Core\Plugin::get_prompt_template_for_type( $feed_type );
			} else {
				// For general type, check global prompt first, then fall back to default.
				$global_prompt = get_option( 'afd_prompt_template', '' );
				$this->template = ! empty( $global_prompt )
					? $global_prompt
					: \AIFeedDigest\Core\Plugin::get_default_prompt_template();
			}
		}
	}

	/**
	 * Build the prompt with replaced variables.
	 *
	 * @return string The built prompt.
	 */
	public function build(): string {
		$frequency = Links::get_feed_meta( $this->feed->link_id, '_afd_digest_frequency', 'weekly' );

		$replacements = array(
			'{feed_name}'         => $this->feed->link_name ?? '',
			'{feed_description}'  => $this->feed->link_description ?? '',
			'{period}'            => ( 'monthly' === $frequency ) ? 'month' : 'week',
			'{article_count}'     => (string) count( $this->items ),
			'{articles_json}'     => wp_json_encode( $this->format_items_array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
			'{articles_markdown}' => $this->format_items_markdown(),
		);

		return str_replace(
			array_keys( $replacements ),
			array_values( $replacements ),
			$this->template
		);
	}

	/**
	 * Format items as an array for JSON encoding.
	 *
	 * @return array The formatted items.
	 */
	private function format_items_array(): array {
		$settings        = get_option( 'afd_general_settings', array() );
		$max_content_words = isset( $settings['items_per_digest'] ) ? absint( $settings['items_per_digest'] ) * 25 : 500;

		return array_map(
			function ( $item ) use ( $max_content_words ) {
				return array(
					'title'   => $item->post_title,
					'excerpt' => $item->post_excerpt,
					'content' => wp_trim_words( wp_strip_all_tags( $item->post_content ), $max_content_words ),
					'url'     => get_post_meta( $item->ID, '_afd_source_url', true ),
					'date'    => $item->post_date,
					'author'  => get_post_meta( $item->ID, '_afd_author', true ),
				);
			},
			$this->items
		);
	}

	/**
	 * Format items as markdown.
	 *
	 * @return string The markdown-formatted items.
	 */
	private function format_items_markdown(): string {
		$output = '';

		foreach ( $this->items as $item ) {
			$url     = get_post_meta( $item->ID, '_afd_source_url', true );
			$author  = get_post_meta( $item->ID, '_afd_author', true );
			$excerpt = ! empty( $item->post_excerpt )
				? $item->post_excerpt
				: wp_trim_words( wp_strip_all_tags( $item->post_content ), 200 );

			$output .= sprintf(
				"## %s\n**URL:** %s\n**Date:** %s\n**Author:** %s\n\n%s\n\n---\n\n",
				$item->post_title,
				$url,
				$item->post_date,
				$author ?: __( 'Unknown', 'ai-feed-digest' ),
				$excerpt
			);
		}

		return $output;
	}

	/**
	 * Get available template variables.
	 *
	 * @return array Array of variable => description pairs.
	 */
	public static function get_template_variables(): array {
		return array(
			'{feed_name}'         => __( 'Name of the feed', 'ai-feed-digest' ),
			'{feed_description}'  => __( 'Feed description if set', 'ai-feed-digest' ),
			'{period}'            => __( '"week" or "month" based on frequency', 'ai-feed-digest' ),
			'{article_count}'     => __( 'Number of articles being summarized', 'ai-feed-digest' ),
			'{articles_json}'     => __( 'JSON array of articles with title, excerpt, url, date, author', 'ai-feed-digest' ),
			'{articles_markdown}' => __( 'Articles formatted as markdown list', 'ai-feed-digest' ),
		);
	}

	/**
	 * Validate a prompt template.
	 *
	 * @param string $template The template to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_template( string $template ): bool {
		if ( empty( trim( $template ) ) ) {
			return false;
		}

		// Must contain at least the articles variable.
		if ( ! str_contains( $template, '{articles_json}' ) && ! str_contains( $template, '{articles_markdown}' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Estimate token count for a prompt.
	 *
	 * This is a rough estimate (4 characters per token).
	 *
	 * @param string $text The text to estimate.
	 * @return int Estimated token count.
	 */
	public static function estimate_tokens( string $text ): int {
		return (int) ceil( strlen( $text ) / 4 );
	}
}
