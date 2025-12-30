<?php
/**
 * Settings page template.
 *
 * @package AIFeedDigest
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap afd-settings-wrap">
	<h1><?php esc_html_e( 'AI Feed Digest Settings', 'ai-feed-digest' ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'afd_gemini_settings' );
		settings_fields( 'afd_email_settings' );
		settings_fields( 'afd_general_settings' );
		?>

		<div class="afd-settings-sections">
			<div class="afd-settings-section">
				<h2><?php esc_html_e( 'Gemini API Settings', 'ai-feed-digest' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Configure your Google Gemini API settings for AI-powered digest generation.', 'ai-feed-digest' ); ?></p>
				<table class="form-table" role="presentation">
					<?php do_settings_fields( 'ai-feed-digest', 'afd_gemini_section' ); ?>
				</table>
			</div>

			<div class="afd-settings-section">
				<h2><?php esc_html_e( 'Email Settings', 'ai-feed-digest' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Configure how digest emails are sent.', 'ai-feed-digest' ); ?></p>
				<table class="form-table" role="presentation">
					<?php do_settings_fields( 'ai-feed-digest', 'afd_email_section' ); ?>
				</table>
			</div>

			<div class="afd-settings-section">
				<h2><?php esc_html_e( 'General Settings', 'ai-feed-digest' ); ?></h2>
				<p class="description"><?php esc_html_e( 'General plugin settings.', 'ai-feed-digest' ); ?></p>
				<table class="form-table" role="presentation">
					<?php do_settings_fields( 'ai-feed-digest', 'afd_general_section' ); ?>
				</table>
			</div>
		</div>

		<?php submit_button(); ?>
	</form>

	<div class="afd-settings-sidebar">
		<div class="afd-sidebar-section">
			<h3><?php esc_html_e( 'Quick Links', 'ai-feed-digest' ); ?></h3>
			<ul>
				<li>
					<a href="<?php echo esc_url( admin_url( 'link-manager.php' ) ); ?>">
						<?php esc_html_e( 'Manage Feeds', 'ai-feed-digest' ); ?>
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=afd-prompt-editor' ) ); ?>">
						<?php esc_html_e( 'Edit Prompt Template', 'ai-feed-digest' ); ?>
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=afd-digest-preview' ) ); ?>">
						<?php esc_html_e( 'View Digests', 'ai-feed-digest' ); ?>
					</a>
				</li>
			</ul>
		</div>

		<div class="afd-sidebar-section">
			<h3><?php esc_html_e( 'Cron Status', 'ai-feed-digest' ); ?></h3>
			<?php
			$fetch_next  = wp_next_scheduled( 'afd_fetch_feeds' );
			$digest_next = wp_next_scheduled( 'afd_generate_digests' );
			?>
			<p>
				<strong><?php esc_html_e( 'Next feed fetch:', 'ai-feed-digest' ); ?></strong><br />
				<?php echo $fetch_next ? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $fetch_next ) ) : esc_html__( 'Not scheduled', 'ai-feed-digest' ); ?>
			</p>
			<p>
				<strong><?php esc_html_e( 'Next digest check:', 'ai-feed-digest' ); ?></strong><br />
				<?php echo $digest_next ? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $digest_next ) ) : esc_html__( 'Not scheduled', 'ai-feed-digest' ); ?>
			</p>
		</div>

		<div class="afd-sidebar-section">
			<h3><?php esc_html_e( 'WP-Cron Note', 'ai-feed-digest' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'WP-Cron relies on site traffic. For reliable scheduling, consider setting up a real cron job on your server.', 'ai-feed-digest' ); ?>
			</p>
		</div>
	</div>
</div>
