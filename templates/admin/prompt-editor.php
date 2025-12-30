<?php
/**
 * Prompt editor template.
 *
 * @package AIFeedDigest
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIFeedDigest\Admin\PromptEditor;
use AIFeedDigest\AI\PromptBuilder;
use AIFeedDigest\Core\Plugin;

$current_template = PromptEditor::get_current_template();
$variables        = PromptBuilder::get_template_variables();
$feeds            = PromptEditor::get_feeds_for_dropdown();
?>

<div class="wrap afd-prompt-editor-wrap">
	<h1><?php esc_html_e( 'Prompt Template Editor', 'ai-feed-digest' ); ?></h1>

	<div class="afd-prompt-editor-layout">
		<div class="afd-prompt-editor-main">
			<form method="post" action="options.php" id="afd-prompt-form">
				<?php settings_fields( 'afd_prompt_template' ); ?>

				<div class="afd-prompt-editor-container">
					<label for="afd_prompt_template" class="screen-reader-text">
						<?php esc_html_e( 'Prompt Template', 'ai-feed-digest' ); ?>
					</label>
					<textarea
						id="afd_prompt_template"
						name="afd_prompt_template"
						rows="25"
						class="large-text code"
					><?php echo esc_textarea( $current_template ); ?></textarea>
				</div>

				<div class="afd-prompt-actions">
					<?php submit_button( __( 'Save Template', 'ai-feed-digest' ), 'primary', 'submit', false ); ?>
					<button type="button" class="button afd-reset-prompt" data-default="<?php echo esc_attr( Plugin::get_default_prompt_template() ); ?>">
						<?php esc_html_e( 'Reset to Default', 'ai-feed-digest' ); ?>
					</button>
				</div>
			</form>

			<div class="afd-prompt-test">
				<h2><?php esc_html_e( 'Test Prompt', 'ai-feed-digest' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Select a feed and test the prompt to see how the AI generates a digest.', 'ai-feed-digest' ); ?>
				</p>

				<div class="afd-test-controls">
					<select id="afd-test-feed" class="afd-test-feed-select">
						<option value=""><?php esc_html_e( 'Select a feed...', 'ai-feed-digest' ); ?></option>
						<?php foreach ( $feeds as $id => $name ) : ?>
							<option value="<?php echo esc_attr( $id ); ?>">
								<?php echo esc_html( $name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button afd-test-prompt-btn" data-nonce="<?php echo esc_attr( wp_create_nonce( 'afd_test_prompt' ) ); ?>">
						<?php esc_html_e( 'Test Prompt', 'ai-feed-digest' ); ?>
					</button>
				</div>

				<div class="afd-test-result" style="display: none;">
					<h3><?php esc_html_e( 'Generated Preview', 'ai-feed-digest' ); ?></h3>
					<div class="afd-test-content"></div>
				</div>
			</div>
		</div>

		<div class="afd-prompt-editor-sidebar">
			<div class="afd-sidebar-section">
				<h3><?php esc_html_e( 'Available Variables', 'ai-feed-digest' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Click a variable to insert it at the cursor position.', 'ai-feed-digest' ); ?>
				</p>
				<ul class="afd-variable-list">
					<?php foreach ( $variables as $var => $desc ) : ?>
						<li>
							<button type="button" class="afd-insert-variable" data-variable="<?php echo esc_attr( $var ); ?>">
								<code><?php echo esc_html( $var ); ?></code>
							</button>
							<span class="description"><?php echo esc_html( $desc ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="afd-sidebar-section">
				<h3><?php esc_html_e( 'Tips', 'ai-feed-digest' ); ?></h3>
				<ul class="afd-tips-list">
					<li><?php esc_html_e( 'Be specific about the output format you want (HTML, Markdown, etc.).', 'ai-feed-digest' ); ?></li>
					<li><?php esc_html_e( 'Use {articles_markdown} for structured article data or {articles_json} for detailed data.', 'ai-feed-digest' ); ?></li>
					<li><?php esc_html_e( 'Specify the tone and style you want (formal, casual, etc.).', 'ai-feed-digest' ); ?></li>
					<li><?php esc_html_e( 'Include examples of the output format if needed.', 'ai-feed-digest' ); ?></li>
				</ul>
			</div>
		</div>
	</div>
</div>
