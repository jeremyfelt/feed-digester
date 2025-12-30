<?php
/**
 * Tests for the Fetcher class.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Fetcher test case.
 */
class FetcherTest extends TestCase {

	/**
	 * Test that a valid URL is recognized.
	 */
	public function test_valid_url_format(): void {
		$valid_urls = array(
			'https://example.com/feed',
			'https://www.example.org/rss.xml',
			'http://blog.example.net/atom.xml',
			'https://example.com/feed?format=rss',
		);

		foreach ( $valid_urls as $url ) {
			$this->assertTrue(
				(bool) filter_var( $url, FILTER_VALIDATE_URL ),
				"URL should be valid: {$url}"
			);
		}
	}

	/**
	 * Test that an invalid URL is rejected.
	 */
	public function test_invalid_url_format(): void {
		$invalid_urls = array(
			'not-a-url',
			'ftp://example.com',
			'javascript:alert(1)',
			'',
			'   ',
		);

		foreach ( $invalid_urls as $url ) {
			$result = filter_var( $url, FILTER_VALIDATE_URL );
			$this->assertFalse(
				(bool) $result,
				"URL should be invalid: {$url}"
			);
		}
	}

	/**
	 * Test feed URL extraction from link object.
	 */
	public function test_get_feed_url_from_link_rss(): void {
		$link = (object) array(
			'link_id'  => 1,
			'link_url' => 'https://example.com',
			'link_rss' => 'https://example.com/feed.xml',
		);

		// link_rss should be preferred over link_url.
		$this->assertSame( 'https://example.com/feed.xml', $link->link_rss );
	}

	/**
	 * Test feed URL fallback to link_url.
	 */
	public function test_get_feed_url_fallback(): void {
		$link = (object) array(
			'link_id'  => 1,
			'link_url' => 'https://example.com/feed',
			'link_rss' => '',
		);

		$feed_url = ! empty( $link->link_rss ) ? $link->link_rss : $link->link_url;
		$this->assertSame( 'https://example.com/feed', $feed_url );
	}

	/**
	 * Test date formatting.
	 */
	public function test_date_formatting(): void {
		$test_dates = array(
			'2024-01-15 10:30:00' => '2024-01-15 10:30:00',
			'Mon, 15 Jan 2024 10:30:00 +0000' => '2024-01-15 10:30:00',
			'2024-01-15T10:30:00Z' => '2024-01-15 10:30:00',
		);

		foreach ( $test_dates as $input => $expected ) {
			$timestamp = strtotime( $input );
			$this->assertNotFalse( $timestamp, "Should parse date: {$input}" );

			$formatted = gmdate( 'Y-m-d H:i:s', $timestamp );
			$this->assertSame( $expected, $formatted, "Date should be formatted correctly: {$input}" );
		}
	}

	/**
	 * Test invalid date handling.
	 */
	public function test_invalid_date_returns_false(): void {
		$this->assertFalse( strtotime( 'not a date' ) );
		$this->assertFalse( strtotime( '' ) );
	}
}
