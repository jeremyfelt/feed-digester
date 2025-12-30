<?php
/**
 * Uninstall script for AI Feed Digest.
 *
 * This file is executed when the plugin is deleted through the WordPress admin.
 * It removes all plugin data including posts, post meta, link meta, and options.
 *
 * @package AIFeedDigest
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete all feed items.
$feed_items = get_posts(
	array(
		'post_type'      => 'afd_feed_item',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	)
);

foreach ( $feed_items as $post_id ) {
	wp_delete_post( $post_id, true );
}

// Delete all digests.
$digests = get_posts(
	array(
		'post_type'      => 'afd_digest',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	)
);

foreach ( $digests as $post_id ) {
	wp_delete_post( $post_id, true );
}

// Delete link meta for all links.
global $wpdb;

// Check if linkmeta table exists (it should in standard WP installations).
$table_name = $wpdb->prefix . 'linkmeta';
$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

if ( $table_exists ) {
	$wpdb->query( "DELETE FROM {$wpdb->prefix}linkmeta WHERE meta_key LIKE '_afd_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

// Delete options.
delete_option( 'afd_gemini_settings' );
delete_option( 'afd_email_settings' );
delete_option( 'afd_general_settings' );
delete_option( 'afd_prompt_template' );

// Clear scheduled events.
wp_clear_scheduled_hook( 'afd_fetch_feeds' );
wp_clear_scheduled_hook( 'afd_generate_digests' );
wp_clear_scheduled_hook( 'afd_cleanup_old_items' );

// Clear any transients.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_afd_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_afd_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// Flush rewrite rules.
flush_rewrite_rules();
