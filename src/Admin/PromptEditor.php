<?php
/**
 * Prompt template editor admin page.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Admin;

use AIFeedDigest\AI\PromptBuilder;
use AIFeedDigest\AI\DigestGenerator;
use AIFeedDigest\Core\Plugin;

/**
 * PromptEditor class.
 */
class PromptEditor {

	/**
	 * Initialize the prompt editor.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'wp_ajax_afd_test_prompt', array( __CLASS__, 'ajax_test_prompt' ) );
	}

	/**
	 * Add the menu page.
	 *
	 * @return void
	 */
	public static function add_menu_page(): void {
		add_submenu_page(
			'ai-feed-digest',
			__( 'Prompt Template', 'ai-feed-digest' ),
			__( 'Prompt Template', 'ai-feed-digest' ),
			'manage_options',
			'afd-prompt-editor',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public static function register_settings(): void {
		register_setting(
			'afd_prompt_template',
			'afd_prompt_template',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_template' ),
				'default'           => Plugin::get_default_prompt_template(),
			)
		);
	}

	/**
	 * Render the prompt editor page.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include AFD_PLUGIN_DIR . 'templates/admin/prompt-editor.php';
	}

	/**
	 * Sanitize the prompt template.
	 *
	 * @param string $template The template to sanitize.
	 * @return string The sanitized template.
	 */
	public static function sanitize_template( string $template ): string {
		// Allow most text content but strip dangerous tags.
		$template = wp_kses(
			$template,
			array(
				'br' => array(),
			)
		);

		return $template;
	}

	/**
	 * AJAX handler for testing a prompt.
	 *
	 * @return void
	 */
	public static function ajax_test_prompt(): void {
		check_ajax_referer( 'afd_test_prompt', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-feed-digest' ) ) );
		}

		$feed_id = isset( $_POST['feed_id'] ) ? absint( $_POST['feed_id'] ) : 0;

		if ( ! $feed_id ) {
			wp_send_json_error( array( 'message' => __( 'Please select a feed.', 'ai-feed-digest' ) ) );
		}

		$feed = get_bookmark( $feed_id );

		if ( ! $feed ) {
			wp_send_json_error( array( 'message' => __( 'Feed not found.', 'ai-feed-digest' ) ) );
		}

		$generator = new DigestGenerator( $feed );
		$result    = $generator->generate_preview( 5 );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'content' => $result,
			)
		);
	}

	/**
	 * Get all feeds for the dropdown.
	 *
	 * @return array Array of feeds.
	 */
	public static function get_feeds_for_dropdown(): array {
		$feeds   = get_bookmarks( array( 'hide_invisible' => false ) );
		$options = array();

		foreach ( $feeds as $feed ) {
			$options[ $feed->link_id ] = $feed->link_name;
		}

		return $options;
	}

	/**
	 * Get the current template.
	 *
	 * @return string The current template.
	 */
	public static function get_current_template(): string {
		$template = get_option( 'afd_prompt_template' );

		if ( empty( $template ) ) {
			return Plugin::get_default_prompt_template();
		}

		return $template;
	}

	/**
	 * Reset to default template.
	 *
	 * @return void
	 */
	public static function reset_to_default(): void {
		update_option( 'afd_prompt_template', Plugin::get_default_prompt_template() );
	}
}
