<?php
/**
 * HTML email newsletter template.
 *
 * Variables available:
 * - $content: The digest content (HTML)
 * - $feed: The feed link object
 *
 * @package AIFeedDigest
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name  = get_bloginfo( 'name' );
$site_url   = home_url();
$admin_url  = admin_url( 'admin.php?page=ai-feed-digest' );
$feed_name  = $feed->link_name ?? __( 'Feed Digest', 'ai-feed-digest' );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title><?php echo esc_html( $feed_name ); ?></title>
	<!--[if mso]>
	<style type="text/css">
		table { border-collapse: collapse; }
		.fallback-font { font-family: Arial, sans-serif; }
	</style>
	<![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
	<!-- Preheader text (hidden) -->
	<div style="display: none; max-height: 0; overflow: hidden;">
		<?php
		printf(
			/* translators: %s: feed name */
			esc_html__( 'Your digest from %s is ready.', 'ai-feed-digest' ),
			esc_html( $feed_name )
		);
		?>
	</div>

	<!-- Email wrapper -->
	<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f5f5f5;">
		<tr>
			<td align="center" style="padding: 20px 10px;">
				<!-- Email container -->
				<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">

					<!-- Header -->
					<tr>
						<td style="background-color: #0073aa; padding: 30px 40px; text-align: center;">
							<h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600; line-height: 1.3;">
								<?php echo esc_html( $feed_name ); ?>
							</h1>
							<p style="margin: 10px 0 0; color: rgba(255,255,255,0.8); font-size: 14px;">
								<?php echo esc_html( wp_date( get_option( 'date_format' ) ) ); ?>
							</p>
						</td>
					</tr>

					<!-- Content -->
					<tr>
						<td style="padding: 40px;">
							<div style="color: #333333; font-size: 16px; line-height: 1.6;">
								<?php
								// Process content to ensure links are styled.
								$styled_content = preg_replace(
									'/<a\s+href=/i',
									'<a style="color: #0073aa; text-decoration: underline;" href=',
									$content
								);
								// Style headings.
								$styled_content = preg_replace(
									'/<h2([^>]*)>/i',
									'<h2$1 style="color: #1e1e1e; font-size: 20px; font-weight: 600; margin: 30px 0 15px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">',
									$styled_content
								);
								$styled_content = preg_replace(
									'/<h3([^>]*)>/i',
									'<h3$1 style="color: #333333; font-size: 18px; font-weight: 600; margin: 25px 0 10px;">',
									$styled_content
								);
								// Style paragraphs.
								$styled_content = preg_replace(
									'/<p([^>]*)>/i',
									'<p$1 style="margin: 0 0 15px;">',
									$styled_content
								);
								// Style lists.
								$styled_content = preg_replace(
									'/<ul([^>]*)>/i',
									'<ul$1 style="margin: 0 0 20px; padding-left: 20px;">',
									$styled_content
								);
								$styled_content = preg_replace(
									'/<li([^>]*)>/i',
									'<li$1 style="margin: 0 0 10px;">',
									$styled_content
								);
								// Style horizontal rules.
								$styled_content = preg_replace(
									'/<hr([^>]*)>/i',
									'<hr$1 style="border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;">',
									$styled_content
								);
								// Style blockquotes.
								$styled_content = preg_replace(
									'/<blockquote([^>]*)>/i',
									'<blockquote$1 style="margin: 20px 0; padding: 15px 20px; border-left: 4px solid #0073aa; background-color: #f8f9fa; font-style: italic;">',
									$styled_content
								);
								echo wp_kses_post( $styled_content );
								?>
							</div>
						</td>
					</tr>

					<!-- Footer -->
					<tr>
						<td style="background-color: #f8f9fa; padding: 25px 40px; border-top: 1px solid #e9ecef;">
							<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
								<tr>
									<td style="text-align: center;">
										<p style="margin: 0 0 10px; color: #666666; font-size: 14px;">
											<?php
											printf(
												/* translators: %s: site name with link */
												esc_html__( 'Sent from %s', 'ai-feed-digest' ),
												'<a href="' . esc_url( $site_url ) . '" style="color: #0073aa; text-decoration: none;">' . esc_html( $site_name ) . '</a>'
											);
											?>
										</p>
										<p style="margin: 0; color: #999999; font-size: 12px;">
											<?php esc_html_e( 'This digest was generated by AI Feed Digest.', 'ai-feed-digest' ); ?>
										</p>
										<p style="margin: 15px 0 0; font-size: 12px;">
											<a href="<?php echo esc_url( $admin_url ); ?>" style="color: #666666; text-decoration: underline;">
												<?php esc_html_e( 'Manage digest settings', 'ai-feed-digest' ); ?>
											</a>
										</p>
									</td>
								</tr>
							</table>
						</td>
					</tr>

				</table>
			</td>
		</tr>
	</table>
</body>
</html>
