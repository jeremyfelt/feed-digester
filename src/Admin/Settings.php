<?php
/**
 * Settings page and options management.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Admin;

use AIFeedDigest\AI\GeminiClient;

/**
 * Settings class.
 */
class Settings {

	/**
	 * Initialize the settings page.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_pages' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_notices', array( __CLASS__, 'display_notices' ) );
		add_action( 'wp_ajax_afd_test_api', array( __CLASS__, 'ajax_test_api' ) );
	}

	/**
	 * Add menu pages.
	 *
	 * @return void
	 */
	public static function add_menu_pages(): void {
		add_menu_page(
			__( 'AI Feed Digest', 'ai-feed-digest' ),
			__( 'AI Feed Digest', 'ai-feed-digest' ),
			'manage_options',
			'ai-feed-digest',
			array( __CLASS__, 'render_settings_page' ),
			'dashicons-rss',
			30
		);

		add_submenu_page(
			'ai-feed-digest',
			__( 'Settings', 'ai-feed-digest' ),
			__( 'Settings', 'ai-feed-digest' ),
			'manage_options',
			'ai-feed-digest',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public static function register_settings(): void {
		// Gemini settings.
		register_setting(
			'afd_settings',
			'afd_gemini_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_gemini_settings' ),
				'default'           => array(
					'api_key'     => '',
					'model'       => 'gemini-1.5-flash',
					'temperature' => 0.7,
					'max_tokens'  => 8192,
				),
			)
		);

		add_settings_section(
			'afd_gemini_section',
			__( 'Gemini API Settings', 'ai-feed-digest' ),
			array( __CLASS__, 'render_gemini_section' ),
			'ai-feed-digest'
		);

		add_settings_field(
			'api_key',
			__( 'API Key', 'ai-feed-digest' ),
			array( __CLASS__, 'render_api_key_field' ),
			'ai-feed-digest',
			'afd_gemini_section'
		);

		add_settings_field(
			'model',
			__( 'Model', 'ai-feed-digest' ),
			array( __CLASS__, 'render_model_field' ),
			'ai-feed-digest',
			'afd_gemini_section'
		);

		add_settings_field(
			'temperature',
			__( 'Temperature', 'ai-feed-digest' ),
			array( __CLASS__, 'render_temperature_field' ),
			'ai-feed-digest',
			'afd_gemini_section'
		);

		add_settings_field(
			'max_tokens',
			__( 'Max Output Tokens', 'ai-feed-digest' ),
			array( __CLASS__, 'render_max_tokens_field' ),
			'ai-feed-digest',
			'afd_gemini_section'
		);

		// Email settings.
		register_setting(
			'afd_settings',
			'afd_email_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_email_settings' ),
				'default'           => array(
					'recipient_email' => get_option( 'admin_email' ),
					'from_name'       => get_bloginfo( 'name' ),
					'subject_prefix'  => '[Feed Digest]',
					'send_empty'      => false,
				),
			)
		);

		add_settings_section(
			'afd_email_section',
			__( 'Email Settings', 'ai-feed-digest' ),
			array( __CLASS__, 'render_email_section' ),
			'ai-feed-digest'
		);

		add_settings_field(
			'recipient_email',
			__( 'Recipient Email', 'ai-feed-digest' ),
			array( __CLASS__, 'render_recipient_email_field' ),
			'ai-feed-digest',
			'afd_email_section'
		);

		add_settings_field(
			'from_name',
			__( 'From Name', 'ai-feed-digest' ),
			array( __CLASS__, 'render_from_name_field' ),
			'ai-feed-digest',
			'afd_email_section'
		);

		add_settings_field(
			'subject_prefix',
			__( 'Subject Prefix', 'ai-feed-digest' ),
			array( __CLASS__, 'render_subject_prefix_field' ),
			'ai-feed-digest',
			'afd_email_section'
		);

		add_settings_field(
			'send_empty',
			__( 'Send Empty Digests', 'ai-feed-digest' ),
			array( __CLASS__, 'render_send_empty_field' ),
			'ai-feed-digest',
			'afd_email_section'
		);

		// General settings.
		register_setting(
			'afd_settings',
			'afd_general_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_general_settings' ),
				'default'           => array(
					'default_frequency'   => 'weekly',
					'fetch_full_content'  => false,
					'cleanup_after_days'  => 90,
					'items_per_digest'    => 20,
				),
			)
		);

		add_settings_section(
			'afd_general_section',
			__( 'General Settings', 'ai-feed-digest' ),
			array( __CLASS__, 'render_general_section' ),
			'ai-feed-digest'
		);

		add_settings_field(
			'default_frequency',
			__( 'Default Digest Frequency', 'ai-feed-digest' ),
			array( __CLASS__, 'render_default_frequency_field' ),
			'ai-feed-digest',
			'afd_general_section'
		);

		add_settings_field(
			'fetch_full_content',
			__( 'Fetch Full Content', 'ai-feed-digest' ),
			array( __CLASS__, 'render_fetch_full_content_field' ),
			'ai-feed-digest',
			'afd_general_section'
		);

		add_settings_field(
			'cleanup_after_days',
			__( 'Cleanup After Days', 'ai-feed-digest' ),
			array( __CLASS__, 'render_cleanup_days_field' ),
			'ai-feed-digest',
			'afd_general_section'
		);

		add_settings_field(
			'items_per_digest',
			__( 'Max Items Per Digest', 'ai-feed-digest' ),
			array( __CLASS__, 'render_items_per_digest_field' ),
			'ai-feed-digest',
			'afd_general_section'
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include AFD_PLUGIN_DIR . 'templates/admin/settings-page.php';
	}

	/**
	 * Render Gemini section description.
	 *
	 * @return void
	 */
	public static function render_gemini_section(): void {
		echo '<p>' . esc_html__( 'Configure your Google Gemini API settings for AI-powered digest generation.', 'ai-feed-digest' ) . '</p>';
	}

	/**
	 * Render email section description.
	 *
	 * @return void
	 */
	public static function render_email_section(): void {
		echo '<p>' . esc_html__( 'Configure how digest emails are sent.', 'ai-feed-digest' ) . '</p>';
	}

	/**
	 * Render general section description.
	 *
	 * @return void
	 */
	public static function render_general_section(): void {
		echo '<p>' . esc_html__( 'General plugin settings.', 'ai-feed-digest' ) . '</p>';
	}

	/**
	 * Render API key field.
	 *
	 * @return void
	 */
	public static function render_api_key_field(): void {
		$settings = get_option( 'afd_gemini_settings', array() );
		$api_key  = $settings['api_key'] ?? '';
		$masked   = ! empty( $api_key ) ? str_repeat( '*', 20 ) . substr( $api_key, -4 ) : '';
		?>
		<input type="password"
			id="afd_api_key"
			name="afd_gemini_settings[api_key]"
			value="<?php echo esc_attr( $api_key ); ?>"
			class="regular-text"
			autocomplete="off" />
		<p class="description">
			<?php esc_html_e( 'Enter your Google Gemini API key.', 'ai-feed-digest' ); ?>
			<a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Get an API key', 'ai-feed-digest' ); ?>
			</a>
		</p>
		<?php if ( ! empty( $api_key ) ) : ?>
			<p class="description">
				<button type="button" class="button afd-test-api" data-nonce="<?php echo esc_attr( wp_create_nonce( 'afd_test_api' ) ); ?>">
					<?php esc_html_e( 'Test Connection', 'ai-feed-digest' ); ?>
				</button>
				<span class="afd-test-result"></span>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render model field.
	 *
	 * @return void
	 */
	public static function render_model_field(): void {
		$settings = get_option( 'afd_gemini_settings', array() );
		$model    = $settings['model'] ?? 'gemini-1.5-flash';
		$models   = GeminiClient::get_available_models();
		?>
		<select id="afd_model" name="afd_gemini_settings[model]">
			<?php foreach ( $models as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $model, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select the Gemini model to use for generating digests.', 'ai-feed-digest' ); ?>
		</p>
		<?php
	}

	/**
	 * Render temperature field.
	 *
	 * @return void
	 */
	public static function render_temperature_field(): void {
		$settings    = get_option( 'afd_gemini_settings', array() );
		$temperature = $settings['temperature'] ?? 0.7;
		?>
		<input type="range"
			id="afd_temperature"
			name="afd_gemini_settings[temperature]"
			value="<?php echo esc_attr( $temperature ); ?>"
			min="0"
			max="1"
			step="0.1" />
		<span class="afd-range-value"><?php echo esc_html( $temperature ); ?></span>
		<p class="description">
			<?php esc_html_e( 'Controls creativity. Lower values are more focused, higher values more creative.', 'ai-feed-digest' ); ?>
		</p>
		<?php
	}

	/**
	 * Render max tokens field.
	 *
	 * @return void
	 */
	public static function render_max_tokens_field(): void {
		$settings   = get_option( 'afd_gemini_settings', array() );
		$max_tokens = $settings['max_tokens'] ?? 8192;
		?>
		<input type="number"
			id="afd_max_tokens"
			name="afd_gemini_settings[max_tokens]"
			value="<?php echo esc_attr( $max_tokens ); ?>"
			min="256"
			max="8192"
			step="256"
			class="small-text" />
		<p class="description">
			<?php esc_html_e( 'Maximum number of tokens in the generated response.', 'ai-feed-digest' ); ?>
		</p>
		<?php
	}

	/**
	 * Render recipient email field.
	 *
	 * @return void
	 */
	public static function render_recipient_email_field(): void {
		$settings = get_option( 'afd_email_settings', array() );
		$email    = $settings['recipient_email'] ?? get_option( 'admin_email' );
		?>
		<input type="email"
			id="afd_recipient_email"
			name="afd_email_settings[recipient_email]"
			value="<?php echo esc_attr( $email ); ?>"
			class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'Email address to receive digest newsletters.', 'ai-feed-digest' ); ?>
		</p>
		<?php
	}

	/**
	 * Render from name field.
	 *
	 * @return void
	 */
	public static function render_from_name_field(): void {
		$settings  = get_option( 'afd_email_settings', array() );
		$from_name = $settings['from_name'] ?? get_bloginfo( 'name' );
		?>
		<input type="text"
			id="afd_from_name"
			name="afd_email_settings[from_name]"
			value="<?php echo esc_attr( $from_name ); ?>"
			class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'The name shown as the email sender.', 'ai-feed-digest' ); ?>
		</p>
		<?php
	}

	/**
	 * Render subject prefix field.
	 *
	 * @return void
	 */
	public static function render_subject_prefix_field(): void {
		$settings = get_option( 'afd_email_settings', array() );
		$prefix   = $settings['subject_prefix'] ?? '[Feed Digest]';
		?>
		<input type="text"
			id="afd_subject_prefix"
			name="afd_email_settings[subject_prefix]"
			value="<?php echo esc_attr( $prefix ); ?>"
			class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'Prefix added to digest email subject lines.', 'ai-feed-digest' ); ?>
		</p>
		<?php
	}

	/**
	 * Render send empty field.
	 *
	 * @return void
	 */
	public static function render_send_empty_field(): void {
		$settings   = get_option( 'afd_email_settings', array() );
		$send_empty = $settings['send_empty'] ?? false;
		?>
		<label>
			<input type="checkbox"
				id="afd_send_empty"
				name="afd_email_settings[send_empty]"
				value="1"
				<?php checked( $send_empty ); ?> />
			<?php esc_html_e( 'Send digest even if no new items', 'ai-feed-digest' ); ?>
		</label>
		<?php
	}

	/**
	 * Render default frequency field.
	 *
	 * @return void
	 */
	public static function render_default_frequency_field(): void {
		$settings  = get_option( 'afd_general_settings', array() );
		$frequency = $settings['default_frequency'] ?? 'weekly';
		?>
		<select id="afd_default_frequency" name="afd_general_settings[default_frequency]">
			<option value="weekly" <?php selected( $frequency, 'weekly' ); ?>>
				<?php esc_html_e( 'Weekly', 'ai-feed-digest' ); ?>
			</option>
			<option value="monthly" <?php selected( $frequency, 'monthly' ); ?>>
				<?php esc_html_e( 'Monthly', 'ai-feed-digest' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Default digest frequency for new feeds.', 'ai-feed-digest' ); ?>
		</p>
		<?php
	}

	/**
	 * Render fetch full content field.
	 *
	 * @return void
	 */
	public static function render_fetch_full_content_field(): void {
		$settings         = get_option( 'afd_general_settings', array() );
		$fetch_full_content = $settings['fetch_full_content'] ?? false;
		?>
		<label>
			<input type="checkbox"
				id="afd_fetch_full_content"
				name="afd_general_settings[fetch_full_content]"
				value="1"
				<?php checked( $fetch_full_content ); ?> />
			<?php esc_html_e( 'Fetch full article content (default for new feeds)', 'ai-feed-digest' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When enabled, the plugin will fetch the full article content from the source URL.', 'ai-feed-digest' ); ?>
		</p>
		<?php
	}

	/**
	 * Render cleanup days field.
	 *
	 * @return void
	 */
	public static function render_cleanup_days_field(): void {
		$settings     = get_option( 'afd_general_settings', array() );
		$cleanup_days = $settings['cleanup_after_days'] ?? 90;
		?>
		<input type="number"
			id="afd_cleanup_after_days"
			name="afd_general_settings[cleanup_after_days]"
			value="<?php echo esc_attr( $cleanup_days ); ?>"
			min="7"
			max="365"
			class="small-text" />
		<p class="description">
			<?php esc_html_e( 'Automatically delete feed items older than this many days (after they have been included in a digest).', 'ai-feed-digest' ); ?>
		</p>
		<?php
	}

	/**
	 * Render items per digest field.
	 *
	 * @return void
	 */
	public static function render_items_per_digest_field(): void {
		$settings         = get_option( 'afd_general_settings', array() );
		$items_per_digest = $settings['items_per_digest'] ?? 20;
		?>
		<input type="number"
			id="afd_items_per_digest"
			name="afd_general_settings[items_per_digest]"
			value="<?php echo esc_attr( $items_per_digest ); ?>"
			min="5"
			max="100"
			class="small-text" />
		<p class="description">
			<?php esc_html_e( 'Maximum number of items to include in a single digest.', 'ai-feed-digest' ); ?>
		</p>
		<?php
	}

	/**
	 * Sanitize Gemini settings.
	 *
	 * @param array $input The input array.
	 * @return array The sanitized array.
	 */
	public static function sanitize_gemini_settings( array $input ): array {
		$sanitized        = array();
		$existing_settings = get_option( 'afd_gemini_settings', array() );

		// Preserve existing API key if no new value is submitted (password fields don't retain values).
		$new_api_key              = sanitize_text_field( $input['api_key'] ?? '' );
		$sanitized['api_key']     = ! empty( $new_api_key ) ? $new_api_key : ( $existing_settings['api_key'] ?? '' );
		$sanitized['model']       = sanitize_text_field( $input['model'] ?? 'gemini-1.5-flash' );
		$sanitized['temperature'] = min( 1.0, max( 0.0, floatval( $input['temperature'] ?? 0.7 ) ) );
		$sanitized['max_tokens']  = min( 8192, max( 256, absint( $input['max_tokens'] ?? 8192 ) ) );

		return $sanitized;
	}

	/**
	 * Sanitize email settings.
	 *
	 * @param array $input The input array.
	 * @return array The sanitized array.
	 */
	public static function sanitize_email_settings( array $input ): array {
		$sanitized = array();

		$sanitized['recipient_email'] = sanitize_email( $input['recipient_email'] ?? '' );
		$sanitized['from_name']       = sanitize_text_field( $input['from_name'] ?? '' );
		$sanitized['subject_prefix']  = sanitize_text_field( $input['subject_prefix'] ?? '' );
		$sanitized['send_empty']      = ! empty( $input['send_empty'] );

		return $sanitized;
	}

	/**
	 * Sanitize general settings.
	 *
	 * @param array $input The input array.
	 * @return array The sanitized array.
	 */
	public static function sanitize_general_settings( array $input ): array {
		$sanitized = array();

		$sanitized['default_frequency']   = in_array( $input['default_frequency'] ?? 'weekly', array( 'weekly', 'monthly' ), true ) ? $input['default_frequency'] : 'weekly';
		$sanitized['fetch_full_content']  = ! empty( $input['fetch_full_content'] );
		$sanitized['cleanup_after_days']  = min( 365, max( 7, absint( $input['cleanup_after_days'] ?? 90 ) ) );
		$sanitized['items_per_digest']    = min( 100, max( 5, absint( $input['items_per_digest'] ?? 20 ) ) );

		return $sanitized;
	}

	/**
	 * Display admin notices.
	 *
	 * @return void
	 */
	public static function display_notices(): void {
		$screen = get_current_screen();

		if ( ! $screen || strpos( $screen->id, 'ai-feed-digest' ) === false ) {
			return;
		}

		$settings = get_option( 'afd_gemini_settings', array() );

		if ( empty( $settings['api_key'] ) ) {
			?>
			<div class="notice notice-warning">
				<p>
					<?php
					printf(
						/* translators: %s: link to settings page */
						esc_html__( 'AI Feed Digest requires a Gemini API key to generate digests. %s', 'ai-feed-digest' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=ai-feed-digest' ) ) . '">' . esc_html__( 'Configure now', 'ai-feed-digest' ) . '</a>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Handle AJAX request to test API connection.
	 *
	 * @return void
	 */
	public static function ajax_test_api(): void {
		check_ajax_referer( 'afd_test_api', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-feed-digest' ) ) );
		}

		$client = new GeminiClient();

		if ( ! $client->is_configured() ) {
			wp_send_json_error( array( 'message' => __( 'API key not configured.', 'ai-feed-digest' ) ) );
		}

		$result = $client->test_connection();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Connection successful!', 'ai-feed-digest' ) ) );
	}
}
