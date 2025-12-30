<?php
/**
 * Google Gemini API client.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\AI;

use WP_Error;

/**
 * GeminiClient class.
 */
class GeminiClient {

	/**
	 * API key.
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Model to use.
	 *
	 * @var string
	 */
	private string $model;

	/**
	 * API endpoint base URL.
	 *
	 * @var string
	 */
	private string $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/';

	/**
	 * Generation temperature.
	 *
	 * @var float
	 */
	private float $temperature;

	/**
	 * Maximum output tokens.
	 *
	 * @var int
	 */
	private int $max_tokens;

	/**
	 * Rate limiting: minimum seconds between requests.
	 *
	 * @var int
	 */
	private int $rate_limit_seconds = 2;

	/**
	 * Transient key for rate limiting.
	 *
	 * @var string
	 */
	private string $rate_limit_transient = 'afd_gemini_last_request';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$settings = get_option( 'afd_gemini_settings', array() );

		$this->api_key     = $settings['api_key'] ?? '';
		$this->model       = $settings['model'] ?? 'gemini-1.5-flash';
		$this->temperature = isset( $settings['temperature'] ) ? (float) $settings['temperature'] : 0.7;
		$this->max_tokens  = isset( $settings['max_tokens'] ) ? absint( $settings['max_tokens'] ) : 8192;
	}

	/**
	 * Generate content using the Gemini API.
	 *
	 * @param string $prompt The prompt to send.
	 * @return string|WP_Error The generated content or WP_Error on failure.
	 */
	public function generate_content( string $prompt ): string|WP_Error {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'no_api_key', __( 'Gemini API key not configured.', 'ai-feed-digest' ) );
		}

		// Rate limiting.
		$this->enforce_rate_limit();

		$url = $this->endpoint . $this->model . ':generateContent?key=' . $this->api_key;

		$body = array(
			'contents'         => array(
				array(
					'parts' => array(
						array( 'text' => $prompt ),
					),
				),
			),
			'generationConfig' => array(
				'temperature'     => $this->temperature,
				'topK'            => 40,
				'topP'            => 0.95,
				'maxOutputTokens' => $this->max_tokens,
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 120, // Gemini can take a while for large prompts.
			)
		);

		// Update rate limit timestamp.
		set_transient( $this->rate_limit_transient, time(), $this->rate_limit_seconds * 2 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status_code ) {
			$error_message = $body['error']['message'] ?? __( 'Unknown API error', 'ai-feed-digest' );

			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: error message */
					__( 'Gemini API error (%1$d): %2$s', 'ai-feed-digest' ),
					$status_code,
					$error_message
				)
			);
		}

		if ( isset( $body['error'] ) ) {
			return new WP_Error( 'api_error', $body['error']['message'] ?? __( 'Unknown API error', 'ai-feed-digest' ) );
		}

		$content = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

		if ( empty( $content ) ) {
			return new WP_Error( 'empty_response', __( 'Empty response from Gemini API.', 'ai-feed-digest' ) );
		}

		return $content;
	}

	/**
	 * Enforce rate limiting between API requests.
	 *
	 * @return void
	 */
	private function enforce_rate_limit(): void {
		$last_request = get_transient( $this->rate_limit_transient );

		if ( false !== $last_request ) {
			$elapsed = time() - (int) $last_request;

			if ( $elapsed < $this->rate_limit_seconds ) {
				sleep( $this->rate_limit_seconds - $elapsed );
			}
		}
	}

	/**
	 * Check if the API key is configured.
	 *
	 * @return bool True if configured, false otherwise.
	 */
	public function is_configured(): bool {
		return ! empty( $this->api_key );
	}

	/**
	 * Test the API connection.
	 *
	 * @return bool|WP_Error True if successful, WP_Error on failure.
	 */
	public function test_connection(): bool|WP_Error {
		$result = $this->generate_content( 'Say "Hello" in exactly one word.' );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Get the current model.
	 *
	 * @return string The model name.
	 */
	public function get_model(): string {
		return $this->model;
	}

	/**
	 * Get available models.
	 *
	 * @return array Array of model options.
	 */
	public static function get_available_models(): array {
		return array(
			'gemini-1.5-flash'   => __( 'Gemini 1.5 Flash (Fast, efficient)', 'ai-feed-digest' ),
			'gemini-1.5-pro'     => __( 'Gemini 1.5 Pro (More capable)', 'ai-feed-digest' ),
			'gemini-2.0-flash'   => __( 'Gemini 2.0 Flash (Latest)', 'ai-feed-digest' ),
		);
	}
}
