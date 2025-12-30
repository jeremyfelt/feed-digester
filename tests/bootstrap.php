<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package AIFeedDigest
 */

// Define constants for testing.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'AFD_PLUGIN_DIR' ) ) {
	define( 'AFD_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'AFD_VERSION' ) ) {
	define( 'AFD_VERSION', '1.0.0' );
}

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load Yoast PHPUnit Polyfills.
if ( class_exists( '\Yoast\PHPUnitPolyfills\Autoload' ) ) {
	require_once dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
}

/**
 * Mock WordPress functions for unit testing.
 */

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Mock sanitize_text_field.
	 *
	 * @param string $str String to sanitize.
	 * @return string Sanitized string.
	 */
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Mock wp_kses_post.
	 *
	 * @param string $data Content to filter.
	 * @return string Filtered content.
	 */
	function wp_kses_post( $data ) {
		return $data;
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * Mock esc_url_raw.
	 *
	 * @param string $url URL to sanitize.
	 * @return string Sanitized URL.
	 */
	function esc_url_raw( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Mock __ translation function.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string Original text.
	 */
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Mock esc_html__ translation function.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string Escaped text.
	 */
	function esc_html__( $text, $domain = 'default' ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Mock wp_json_encode.
	 *
	 * @param mixed $data    Data to encode.
	 * @param int   $options JSON options.
	 * @param int   $depth   Maximum depth.
	 * @return string|false JSON string or false on failure.
	 */
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Mock wp_strip_all_tags.
	 *
	 * @param string $string          String to strip.
	 * @param bool   $remove_breaks   Whether to remove breaks.
	 * @return string Stripped string.
	 */
	function wp_strip_all_tags( $string, $remove_breaks = false ) {
		$string = strip_tags( $string );
		if ( $remove_breaks ) {
			$string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
		}
		return trim( $string );
	}
}

if ( ! function_exists( 'wp_trim_words' ) ) {
	/**
	 * Mock wp_trim_words.
	 *
	 * @param string $text      Text to trim.
	 * @param int    $num_words Number of words.
	 * @param string $more      More text.
	 * @return string Trimmed text.
	 */
	function wp_trim_words( $text, $num_words = 55, $more = '...' ) {
		$words = explode( ' ', $text );
		if ( count( $words ) > $num_words ) {
			return implode( ' ', array_slice( $words, 0, $num_words ) ) . $more;
		}
		return $text;
	}
}
