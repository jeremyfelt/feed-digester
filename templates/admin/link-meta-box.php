<?php
/**
 * Link meta box template.
 *
 * @package AIFeedDigest
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIFeedDigest\Admin\LinkMetaBox;
use AIFeedDigest\Core\Links;

$frequency         = LinkMetaBox::get_frequency( $link_id );
$fetch_full_content = LinkMetaBox::get_fetch_full_content( $link_id );
$is_active         = LinkMetaBox::get_is_active( $link_id );
$custom_prompt     = Links::get_feed_meta( $link_id, '_afd_custom_prompt' );
?>

<div class="afd-link-meta-box">
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="afd_is_active"><?php esc_html_e( 'Enable Digest', 'ai-feed-digest' ); ?></label>
			</th>
			<td>
				<label>
					<input type="checkbox"
						id="afd_is_active"
						name="afd_is_active"
						value="1"
						<?php checked( $is_active ); ?> />
					<?php esc_html_e( 'Include this feed in digest generation', 'ai-feed-digest' ); ?>
				</label>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="afd_digest_frequency"><?php esc_html_e( 'Digest Frequency', 'ai-feed-digest' ); ?></label>
			</th>
			<td>
				<select id="afd_digest_frequency" name="afd_digest_frequency">
					<option value="weekly" <?php selected( $frequency, 'weekly' ); ?>>
						<?php esc_html_e( 'Weekly', 'ai-feed-digest' ); ?>
					</option>
					<option value="monthly" <?php selected( $frequency, 'monthly' ); ?>>
						<?php esc_html_e( 'Monthly', 'ai-feed-digest' ); ?>
					</option>
				</select>
				<p class="description">
					<?php esc_html_e( 'How often to generate a digest for this feed.', 'ai-feed-digest' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="afd_fetch_full_content"><?php esc_html_e( 'Full Content', 'ai-feed-digest' ); ?></label>
			</th>
			<td>
				<label>
					<input type="checkbox"
						id="afd_fetch_full_content"
						name="afd_fetch_full_content"
						value="1"
						<?php checked( $fetch_full_content ); ?> />
					<?php esc_html_e( 'Fetch full article content from source URLs', 'ai-feed-digest' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'When enabled, the plugin will attempt to extract the full article content from each link. This provides more context for AI summaries but may increase processing time.', 'ai-feed-digest' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="afd_custom_prompt"><?php esc_html_e( 'Custom Prompt', 'ai-feed-digest' ); ?></label>
			</th>
			<td>
				<textarea
					id="afd_custom_prompt"
					name="afd_custom_prompt"
					rows="6"
					class="large-text"
					placeholder="<?php esc_attr_e( 'Leave empty to use the global prompt template...', 'ai-feed-digest' ); ?>"
				><?php echo esc_textarea( $custom_prompt ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Override the global prompt template for this specific feed. Use the same variables as the global template.', 'ai-feed-digest' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=afd-prompt-editor' ) ); ?>">
						<?php esc_html_e( 'View available variables', 'ai-feed-digest' ); ?>
					</a>
				</p>
			</td>
		</tr>
	</table>

	<p class="afd-meta-box-tip">
		<strong><?php esc_html_e( 'Tip:', 'ai-feed-digest' ); ?></strong>
		<?php esc_html_e( 'Use the "RSS Address" field above to specify the feed URL. The plugin will automatically fetch and parse the RSS/Atom feed.', 'ai-feed-digest' ); ?>
	</p>
</div>
