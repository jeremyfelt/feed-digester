<?php
/**
 * Main plugin bootstrap class.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Core;

use AIFeedDigest\Admin;

/**
 * Plugin class.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Load text domain.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Register post types.
		add_action( 'init', array( PostTypes::class, 'register' ) );

		// Enable and extend Link Manager.
		Links::init();

		// Register cron hooks.
		Cron::register_hooks();

		// Initialize admin.
		if ( is_admin() ) {
			Admin\Settings::init();
			Admin\PromptEditor::init();
			Admin\LinkMetaBox::init();
			Admin\LinkColumns::init();
			Admin\DigestPreview::init();
		}

		// Enqueue admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Load the plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'ai-feed-digest',
			false,
			dirname( AFD_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix The current admin page.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		// Only load on our plugin pages or link pages.
		$plugin_pages = array(
			'toplevel_page_ai-feed-digest',
			'ai-feed-digest_page_afd-prompt-editor',
			'ai-feed-digest_page_afd-digest-preview',
			'link.php',
			'link-add.php',
			'link-manager.php',
		);

		if ( ! in_array( $hook_suffix, $plugin_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'afd-admin',
			AFD_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			AFD_VERSION
		);

		wp_enqueue_script(
			'afd-admin',
			AFD_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			AFD_VERSION,
			true
		);

		wp_localize_script(
			'afd-admin',
			'afdAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'afd_admin_nonce' ),
				'i18n'    => array(
					'fetchingFeed'    => __( 'Fetching feed...', 'ai-feed-digest' ),
					'fetchSuccess'    => __( 'Feed fetched successfully!', 'ai-feed-digest' ),
					'fetchError'      => __( 'Error fetching feed.', 'ai-feed-digest' ),
					'generatingDigest' => __( 'Generating digest...', 'ai-feed-digest' ),
					'digestSuccess'   => __( 'Digest generated successfully!', 'ai-feed-digest' ),
					'digestError'     => __( 'Error generating digest.', 'ai-feed-digest' ),
					'testingPrompt'   => __( 'Testing prompt...', 'ai-feed-digest' ),
				),
			)
		);
	}

	/**
	 * Get the default prompt template.
	 *
	 * @return string
	 */
	public static function get_default_prompt_template(): string {
		return 'You are a helpful assistant creating a newsletter digest for a busy reader who wants to stay informed but doesn\'t have time to read every article.

The feed "{feed_name}" has published {article_count} articles in the last {period}.

Please create an engaging newsletter summary in HTML format suitable for email. Include:

1. **Overview** (2-3 sentences): What were the main themes or topics covered this {period}?

2. **Highlights** (3-5 articles): The most important or interesting articles, each with:
   - Linked title
   - 2-3 sentence summary in your own words
   - Why this matters or who would find it useful

3. **Quick Mentions**: Bullet points for other notable articles worth knowing about.

4. **Recommended Read**: Pick the single best article for someone who only has time to read one thing. Explain your choice.

Write in a warm, conversational tone. Be concise but informative. Use HTML formatting (h2, h3, p, ul, li, a, strong, em) but keep it simple for email compatibility.

Articles to summarize:
{articles_markdown}';
	}
}
