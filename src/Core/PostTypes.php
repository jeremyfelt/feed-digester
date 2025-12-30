<?php
/**
 * Custom post type registration.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Core;

/**
 * PostTypes class.
 */
class PostTypes {

	/**
	 * Register custom post types.
	 *
	 * @return void
	 */
	public static function register(): void {
		self::register_feed_item();
		self::register_digest();
	}

	/**
	 * Register the feed item post type.
	 *
	 * @return void
	 */
	private static function register_feed_item(): void {
		$labels = array(
			'name'                  => _x( 'Feed Items', 'Post type general name', 'ai-feed-digest' ),
			'singular_name'         => _x( 'Feed Item', 'Post type singular name', 'ai-feed-digest' ),
			'menu_name'             => _x( 'Feed Items', 'Admin Menu text', 'ai-feed-digest' ),
			'name_admin_bar'        => _x( 'Feed Item', 'Add New on Toolbar', 'ai-feed-digest' ),
			'add_new'               => __( 'Add New', 'ai-feed-digest' ),
			'add_new_item'          => __( 'Add New Feed Item', 'ai-feed-digest' ),
			'new_item'              => __( 'New Feed Item', 'ai-feed-digest' ),
			'edit_item'             => __( 'Edit Feed Item', 'ai-feed-digest' ),
			'view_item'             => __( 'View Feed Item', 'ai-feed-digest' ),
			'all_items'             => __( 'Feed Items', 'ai-feed-digest' ),
			'search_items'          => __( 'Search Feed Items', 'ai-feed-digest' ),
			'parent_item_colon'     => __( 'Parent Feed Items:', 'ai-feed-digest' ),
			'not_found'             => __( 'No feed items found.', 'ai-feed-digest' ),
			'not_found_in_trash'    => __( 'No feed items found in Trash.', 'ai-feed-digest' ),
			'archives'              => _x( 'Feed Item Archives', 'The post type archive label used in nav menus.', 'ai-feed-digest' ),
			'filter_items_list'     => _x( 'Filter feed items list', 'Screen reader text for the filter links heading.', 'ai-feed-digest' ),
			'items_list_navigation' => _x( 'Feed items list navigation', 'Screen reader text for the pagination heading.', 'ai-feed-digest' ),
			'items_list'            => _x( 'Feed items list', 'Screen reader text for the items list heading.', 'ai-feed-digest' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'ai-feed-digest',
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'excerpt', 'custom-fields' ),
			'show_in_rest'       => true,
		);

		register_post_type( 'afd_feed_item', $args );
	}

	/**
	 * Register the digest post type.
	 *
	 * @return void
	 */
	private static function register_digest(): void {
		$labels = array(
			'name'                  => _x( 'Digests', 'Post type general name', 'ai-feed-digest' ),
			'singular_name'         => _x( 'Digest', 'Post type singular name', 'ai-feed-digest' ),
			'menu_name'             => _x( 'Digests', 'Admin Menu text', 'ai-feed-digest' ),
			'name_admin_bar'        => _x( 'Digest', 'Add New on Toolbar', 'ai-feed-digest' ),
			'add_new'               => __( 'Add New', 'ai-feed-digest' ),
			'add_new_item'          => __( 'Add New Digest', 'ai-feed-digest' ),
			'new_item'              => __( 'New Digest', 'ai-feed-digest' ),
			'edit_item'             => __( 'Edit Digest', 'ai-feed-digest' ),
			'view_item'             => __( 'View Digest', 'ai-feed-digest' ),
			'all_items'             => __( 'Digests', 'ai-feed-digest' ),
			'search_items'          => __( 'Search Digests', 'ai-feed-digest' ),
			'parent_item_colon'     => __( 'Parent Digests:', 'ai-feed-digest' ),
			'not_found'             => __( 'No digests found.', 'ai-feed-digest' ),
			'not_found_in_trash'    => __( 'No digests found in Trash.', 'ai-feed-digest' ),
			'archives'              => _x( 'Digest Archives', 'The post type archive label used in nav menus.', 'ai-feed-digest' ),
			'filter_items_list'     => _x( 'Filter digests list', 'Screen reader text for the filter links heading.', 'ai-feed-digest' ),
			'items_list_navigation' => _x( 'Digests list navigation', 'Screen reader text for the pagination heading.', 'ai-feed-digest' ),
			'items_list'            => _x( 'Digests list', 'Screen reader text for the items list heading.', 'ai-feed-digest' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'ai-feed-digest',
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'custom-fields' ),
			'show_in_rest'       => true,
		);

		register_post_type( 'afd_digest', $args );
	}
}
