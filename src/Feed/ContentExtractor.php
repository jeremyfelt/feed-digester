<?php
/**
 * Extract full content from article URLs.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Feed;

use DOMDocument;
use DOMXPath;
use WP_Error;

/**
 * ContentExtractor class.
 */
class ContentExtractor {

	/**
	 * Content selectors to try, in order of preference.
	 *
	 * @var array
	 */
	private array $content_selectors = array(
		'article',
		'.post-content',
		'.entry-content',
		'.article-content',
		'.article-body',
		'.content-body',
		'.post-body',
		'#content',
		'.content',
		'main',
	);

	/**
	 * Elements to remove from extracted content.
	 *
	 * @var array
	 */
	private array $remove_selectors = array(
		'script',
		'style',
		'nav',
		'header',
		'footer',
		'aside',
		'.sidebar',
		'.navigation',
		'.nav',
		'.menu',
		'.ads',
		'.advertisement',
		'.social-share',
		'.comments',
		'.related-posts',
		'.author-bio',
		'form',
		'iframe',
	);

	/**
	 * Cache expiration in seconds (1 hour).
	 *
	 * @var int
	 */
	private int $cache_expiration = HOUR_IN_SECONDS;

	/**
	 * Extract content from a URL.
	 *
	 * @param string $url The URL to extract content from.
	 * @return string|WP_Error The extracted content or WP_Error on failure.
	 */
	public function extract( string $url ): string|WP_Error {
		// Check cache first.
		$cache_key = 'afd_content_' . md5( $url );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		// Validate URL.
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', __( 'Invalid URL.', 'ai-feed-digest' ) );
		}

		// Fetch the page.
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 30,
				'user-agent' => 'AI Feed Digest Bot/1.0 (WordPress Plugin)',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status_code ) {
			return new WP_Error(
				'http_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'HTTP error: %d', 'ai-feed-digest' ),
					$status_code
				)
			);
		}

		$html = wp_remote_retrieve_body( $response );

		if ( empty( $html ) ) {
			return new WP_Error( 'empty_response', __( 'Empty response from URL.', 'ai-feed-digest' ) );
		}

		$content = $this->parse_content( $html );

		if ( empty( $content ) ) {
			return new WP_Error( 'no_content', __( 'Could not extract content from the page.', 'ai-feed-digest' ) );
		}

		// Cache the result.
		set_transient( $cache_key, $content, $this->cache_expiration );

		return $content;
	}

	/**
	 * Parse HTML and extract the main content.
	 *
	 * @param string $html The HTML to parse.
	 * @return string The extracted content.
	 */
	private function parse_content( string $html ): string {
		// Suppress libxml errors.
		$use_errors = libxml_use_internal_errors( true );

		$dom = new DOMDocument();
		$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_NOWARNING | LIBXML_NOERROR );

		libxml_clear_errors();
		libxml_use_internal_errors( $use_errors );

		$xpath = new DOMXPath( $dom );

		// Remove unwanted elements first.
		$this->remove_elements( $xpath );

		// Try to find content using selectors.
		foreach ( $this->content_selectors as $selector ) {
			$content = $this->query_selector( $xpath, $selector );

			if ( ! empty( $content ) ) {
				return $this->clean_content( $content );
			}
		}

		// Fall back to body content.
		$body = $dom->getElementsByTagName( 'body' )->item( 0 );

		if ( $body ) {
			return $this->clean_content( $dom->saveHTML( $body ) );
		}

		return '';
	}

	/**
	 * Remove unwanted elements from the DOM.
	 *
	 * @param DOMXPath $xpath The XPath object.
	 * @return void
	 */
	private function remove_elements( DOMXPath $xpath ): void {
		foreach ( $this->remove_selectors as $selector ) {
			$query = $this->css_to_xpath( $selector );
			$nodes = $xpath->query( $query );

			if ( $nodes ) {
				foreach ( $nodes as $node ) {
					if ( $node->parentNode ) {
						$node->parentNode->removeChild( $node );
					}
				}
			}
		}
	}

	/**
	 * Query the DOM using a CSS-like selector.
	 *
	 * @param DOMXPath $xpath    The XPath object.
	 * @param string   $selector The CSS selector.
	 * @return string The HTML content of matched elements.
	 */
	private function query_selector( DOMXPath $xpath, string $selector ): string {
		$query = $this->css_to_xpath( $selector );
		$nodes = $xpath->query( $query );

		if ( ! $nodes || 0 === $nodes->length ) {
			return '';
		}

		$content = '';

		foreach ( $nodes as $node ) {
			$content .= $node->ownerDocument->saveHTML( $node );
		}

		return $content;
	}

	/**
	 * Convert a simple CSS selector to XPath.
	 *
	 * @param string $selector The CSS selector.
	 * @return string The XPath query.
	 */
	private function css_to_xpath( string $selector ): string {
		$selector = trim( $selector );

		// Class selector.
		if ( str_starts_with( $selector, '.' ) ) {
			$class = substr( $selector, 1 );
			return "//*[contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')]";
		}

		// ID selector.
		if ( str_starts_with( $selector, '#' ) ) {
			$id = substr( $selector, 1 );
			return "//*[@id='{$id}']";
		}

		// Tag selector.
		return "//{$selector}";
	}

	/**
	 * Clean extracted content.
	 *
	 * @param string $content The content to clean.
	 * @return string The cleaned content.
	 */
	private function clean_content( string $content ): string {
		// Remove excessive whitespace.
		$content = preg_replace( '/\s+/', ' ', $content );

		// Strip remaining script and style tags.
		$content = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $content );
		$content = preg_replace( '/<style\b[^>]*>(.*?)<\/style>/is', '', $content );

		// Remove inline styles.
		$content = preg_replace( '/\sstyle="[^"]*"/i', '', $content );

		// Remove inline scripts (onclick, etc.).
		$content = preg_replace( '/\son\w+="[^"]*"/i', '', $content );

		// Allow only safe HTML.
		$content = wp_kses_post( $content );

		return trim( $content );
	}

	/**
	 * Add a custom content selector.
	 *
	 * @param string $selector The CSS selector to add.
	 * @return void
	 */
	public function add_content_selector( string $selector ): void {
		array_unshift( $this->content_selectors, $selector );
	}

	/**
	 * Add a selector to remove.
	 *
	 * @param string $selector The CSS selector to remove.
	 * @return void
	 */
	public function add_remove_selector( string $selector ): void {
		$this->remove_selectors[] = $selector;
	}
}
