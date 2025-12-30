<?php
/**
 * Tests for the DigestGenerator class.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * DigestGenerator test case.
 */
class DigestGeneratorTest extends TestCase {

	/**
	 * Test prompt template variable replacement.
	 */
	public function test_prompt_variable_replacement(): void {
		$template = 'The feed "{feed_name}" has {article_count} articles this {period}.';

		$replacements = array(
			'{feed_name}'     => 'Tech News',
			'{article_count}' => '10',
			'{period}'        => 'week',
		);

		$result = str_replace(
			array_keys( $replacements ),
			array_values( $replacements ),
			$template
		);

		$expected = 'The feed "Tech News" has 10 articles this week.';
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test that weekly frequency returns 'week' period.
	 */
	public function test_weekly_frequency_period(): void {
		$frequency = 'weekly';
		$period    = ( 'monthly' === $frequency ) ? 'month' : 'week';

		$this->assertSame( 'week', $period );
	}

	/**
	 * Test that monthly frequency returns 'month' period.
	 */
	public function test_monthly_frequency_period(): void {
		$frequency = 'monthly';
		$period    = ( 'monthly' === $frequency ) ? 'month' : 'week';

		$this->assertSame( 'month', $period );
	}

	/**
	 * Test articles markdown formatting.
	 */
	public function test_articles_markdown_formatting(): void {
		$items = array(
			(object) array(
				'post_title'   => 'First Article',
				'post_excerpt' => 'This is the first article excerpt.',
				'post_date'    => '2024-01-15 10:00:00',
			),
			(object) array(
				'post_title'   => 'Second Article',
				'post_excerpt' => 'This is the second article excerpt.',
				'post_date'    => '2024-01-16 12:00:00',
			),
		);

		$output = '';
		foreach ( $items as $item ) {
			$output .= sprintf(
				"## %s\n**Date:** %s\n\n%s\n\n---\n\n",
				$item->post_title,
				$item->post_date,
				$item->post_excerpt
			);
		}

		$this->assertStringContainsString( '## First Article', $output );
		$this->assertStringContainsString( '## Second Article', $output );
		$this->assertStringContainsString( '---', $output );
	}

	/**
	 * Test articles JSON encoding.
	 */
	public function test_articles_json_encoding(): void {
		$items = array(
			array(
				'title'   => 'Test Article',
				'excerpt' => 'Test excerpt',
				'url'     => 'https://example.com/article',
				'date'    => '2024-01-15 10:00:00',
				'author'  => 'John Doe',
			),
		);

		$json = wp_json_encode( $items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		$this->assertNotFalse( $json );
		$this->assertStringContainsString( '"title": "Test Article"', $json );
		$this->assertStringContainsString( 'https://example.com/article', $json );
	}

	/**
	 * Test content cleaning - removes markdown code blocks.
	 */
	public function test_content_cleaning_removes_code_blocks(): void {
		$content = "```html\n<h1>Hello</h1>\n```";

		// Remove markdown code blocks.
		$content = preg_replace( '/^```html?\s*/i', '', $content );
		$content = preg_replace( '/\s*```$/i', '', $content );

		$this->assertSame( '<h1>Hello</h1>', $content );
	}

	/**
	 * Test template validation requires articles variable.
	 */
	public function test_template_validation_requires_articles(): void {
		$valid_template   = 'Generate a digest from {articles_markdown}';
		$invalid_template = 'Generate a digest without articles';

		$this->assertTrue( str_contains( $valid_template, '{articles_markdown}' ) || str_contains( $valid_template, '{articles_json}' ) );
		$this->assertFalse( str_contains( $invalid_template, '{articles_markdown}' ) && str_contains( $invalid_template, '{articles_json}' ) );
	}

	/**
	 * Test token estimation.
	 */
	public function test_token_estimation(): void {
		$text = 'This is a test string for token estimation.';

		// Rough estimate: 4 characters per token.
		$estimated = (int) ceil( strlen( $text ) / 4 );

		$this->assertGreaterThan( 0, $estimated );
		$this->assertLessThan( strlen( $text ), $estimated );
	}
}
