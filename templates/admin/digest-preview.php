<?php
/**
 * Digest preview template.
 *
 * @package AIFeedDigest
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIFeedDigest\Admin\DigestPreview;

$digests    = DigestPreview::get_recent_digests();
$selected   = isset( $_GET['digest_id'] ) ? absint( $_GET['digest_id'] ) : 0;
$details    = $selected ? DigestPreview::get_digest_details( $selected ) : null;
?>

<div class="wrap afd-digest-preview-wrap">
	<h1><?php esc_html_e( 'Digest Preview', 'ai-feed-digest' ); ?></h1>

	<div class="afd-digest-preview-layout">
		<div class="afd-digest-list">
			<h2><?php esc_html_e( 'Recent Digests', 'ai-feed-digest' ); ?></h2>

			<?php if ( empty( $digests ) ) : ?>
				<p class="afd-no-digests">
					<?php esc_html_e( 'No digests have been generated yet.', 'ai-feed-digest' ); ?>
				</p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Title', 'ai-feed-digest' ); ?></th>
							<th><?php esc_html_e( 'Date', 'ai-feed-digest' ); ?></th>
							<th><?php esc_html_e( 'Status', 'ai-feed-digest' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $digests as $digest ) : ?>
							<?php
							$sent_at = get_post_meta( $digest->ID, '_afd_sent_at', true );
							$is_selected = $selected === $digest->ID;
							?>
							<tr class="<?php echo $is_selected ? 'afd-selected' : ''; ?>">
								<td>
									<a href="<?php echo esc_url( add_query_arg( 'digest_id', $digest->ID ) ); ?>">
										<?php echo esc_html( $digest->post_title ); ?>
									</a>
								</td>
								<td>
									<?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $digest->post_date ) ) ); ?>
								</td>
								<td>
									<?php if ( $sent_at ) : ?>
										<span class="afd-status-sent"><?php esc_html_e( 'Sent', 'ai-feed-digest' ); ?></span>
									<?php else : ?>
										<span class="afd-status-pending"><?php esc_html_e( 'Not sent', 'ai-feed-digest' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<?php if ( $details ) : ?>
			<div class="afd-digest-detail">
				<div class="afd-digest-header">
					<h2><?php echo esc_html( $details['title'] ); ?></h2>
					<div class="afd-digest-meta">
						<span>
							<strong><?php esc_html_e( 'Feed:', 'ai-feed-digest' ); ?></strong>
							<?php echo esc_html( $details['feed_name'] ); ?>
						</span>
						<span>
							<strong><?php esc_html_e( 'Items:', 'ai-feed-digest' ); ?></strong>
							<?php echo esc_html( $details['items_count'] ); ?>
						</span>
						<span>
							<strong><?php esc_html_e( 'Generated:', 'ai-feed-digest' ); ?></strong>
							<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $details['date'] ) ) ); ?>
						</span>
						<?php if ( $details['sent_at'] ) : ?>
							<span>
								<strong><?php esc_html_e( 'Sent:', 'ai-feed-digest' ); ?></strong>
								<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $details['sent_at'] ) ) ); ?>
								<?php if ( $details['recipient'] ) : ?>
									<?php esc_html_e( 'to', 'ai-feed-digest' ); ?> <?php echo esc_html( $details['recipient'] ); ?>
								<?php endif; ?>
							</span>
						<?php endif; ?>
					</div>
				</div>

				<div class="afd-digest-actions">
					<button type="button"
						class="button button-primary afd-resend-digest"
						data-digest-id="<?php echo esc_attr( $details['id'] ); ?>"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'afd_resend_digest' ) ); ?>">
						<?php esc_html_e( 'Resend Email', 'ai-feed-digest' ); ?>
					</button>
					<a href="<?php echo esc_url( get_edit_post_link( $details['id'] ) ); ?>" class="button">
						<?php esc_html_e( 'Edit Digest', 'ai-feed-digest' ); ?>
					</a>
				</div>

				<div class="afd-digest-content">
					<h3><?php esc_html_e( 'Email Preview', 'ai-feed-digest' ); ?></h3>
					<div class="afd-email-preview">
						<?php echo wp_kses_post( $details['content'] ); ?>
					</div>
				</div>
			</div>
		<?php else : ?>
			<div class="afd-digest-detail afd-no-selection">
				<p><?php esc_html_e( 'Select a digest from the list to preview it.', 'ai-feed-digest' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>
