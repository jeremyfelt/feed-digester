<?php
/**
 * Custom columns for the links list table.
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest\Admin;

use AIFeedDigest\Core\Links;
use AIFeedDigest\Feed\Repository;

/**
 * LinkColumns class.
 */
class LinkColumns {

	/**
	 * Initialize the link columns.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'manage_link-manager_columns', array( __CLASS__, 'add_columns' ) );
		add_action( 'manage_link_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );

		// Add bulk actions.
		add_filter( 'bulk_actions-link-manager', array( __CLASS__, 'add_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-link-manager', array( __CLASS__, 'handle_bulk_actions' ), 10, 3 );

		// Add admin notices for bulk actions.
		add_action( 'admin_notices', array( __CLASS__, 'display_bulk_action_notices' ) );
	}

	/**
	 * Add custom columns to the links list table.
	 *
	 * @param array $columns The existing columns.
	 * @return array The modified columns.
	 */
	public static function add_columns( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			// Insert our columns after the name column.
			if ( 'name' === $key ) {
				$new_columns['afd_status']     = __( 'Status', 'ai-feed-digest' );
				$new_columns['afd_items']      = __( 'Items', 'ai-feed-digest' );
				$new_columns['afd_last_fetch'] = __( 'Last Fetch', 'ai-feed-digest' );
				$new_columns['afd_next_digest'] = __( 'Next Digest', 'ai-feed-digest' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column_name The column name.
	 * @param int    $link_id     The link ID.
	 * @return void
	 */
	public static function render_column( string $column_name, int $link_id ): void {
		switch ( $column_name ) {
			case 'afd_status':
				self::render_status_column( $link_id );
				break;

			case 'afd_items':
				self::render_items_column( $link_id );
				break;

			case 'afd_last_fetch':
				self::render_last_fetch_column( $link_id );
				break;

			case 'afd_next_digest':
				self::render_next_digest_column( $link_id );
				break;
		}
	}

	/**
	 * Render the status column.
	 *
	 * @param int $link_id The link ID.
	 * @return void
	 */
	private static function render_status_column( int $link_id ): void {
		$is_active = LinkMetaBox::get_is_active( $link_id );

		if ( $is_active ) {
			echo '<span class="afd-status afd-status-active">' . esc_html__( 'Active', 'ai-feed-digest' ) . '</span>';
		} else {
			echo '<span class="afd-status afd-status-inactive">' . esc_html__( 'Inactive', 'ai-feed-digest' ) . '</span>';
		}
	}

	/**
	 * Render the items column.
	 *
	 * @param int $link_id The link ID.
	 * @return void
	 */
	private static function render_items_column( int $link_id ): void {
		$total     = Repository::get_item_count( $link_id );
		$undigested = Repository::get_item_count( $link_id, true );

		printf(
			'<span class="afd-items">%d</span> <span class="afd-items-undigested">(%d %s)</span>',
			esc_html( $total ),
			esc_html( $undigested ),
			esc_html__( 'pending', 'ai-feed-digest' )
		);
	}

	/**
	 * Render the last fetch column.
	 *
	 * @param int $link_id The link ID.
	 * @return void
	 */
	private static function render_last_fetch_column( int $link_id ): void {
		$last_fetched = Links::get_feed_meta( $link_id, '_afd_last_fetched' );

		if ( $last_fetched ) {
			$timestamp = strtotime( $last_fetched );
			$time_diff = human_time_diff( $timestamp );

			printf(
				'<span title="%s">%s %s</span>',
				esc_attr( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) ),
				esc_html( $time_diff ),
				esc_html__( 'ago', 'ai-feed-digest' )
			);
		} else {
			echo '<span class="afd-never">' . esc_html__( 'Never', 'ai-feed-digest' ) . '</span>';
		}
	}

	/**
	 * Render the next digest column.
	 *
	 * @param int $link_id The link ID.
	 * @return void
	 */
	private static function render_next_digest_column( int $link_id ): void {
		if ( ! LinkMetaBox::get_is_active( $link_id ) ) {
			echo '<span class="afd-na">&mdash;</span>';
			return;
		}

		$frequency  = LinkMetaBox::get_frequency( $link_id );
		$last_digest = Links::get_feed_meta( $link_id, '_afd_last_digest_sent' );

		if ( ! $last_digest ) {
			echo '<span class="afd-due">' . esc_html__( 'Due now', 'ai-feed-digest' ) . '</span>';
			return;
		}

		$last_time = strtotime( $last_digest );
		$interval  = ( 'monthly' === $frequency ) ? MONTH_IN_SECONDS : WEEK_IN_SECONDS;
		$next_time = $last_time + $interval;

		if ( $next_time <= time() ) {
			echo '<span class="afd-due">' . esc_html__( 'Due now', 'ai-feed-digest' ) . '</span>';
		} else {
			$time_diff = human_time_diff( time(), $next_time );
			printf(
				'<span title="%s">%s %s</span>',
				esc_attr( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_time ) ),
				esc_html__( 'in', 'ai-feed-digest' ),
				esc_html( $time_diff )
			);
		}
	}

	/**
	 * Add bulk actions to the links list table.
	 *
	 * @param array $actions The existing bulk actions.
	 * @return array The modified bulk actions.
	 */
	public static function add_bulk_actions( array $actions ): array {
		$actions['afd_activate']   = __( 'Activate for Digest', 'ai-feed-digest' );
		$actions['afd_deactivate'] = __( 'Deactivate from Digest', 'ai-feed-digest' );
		$actions['afd_fetch_now']  = __( 'Fetch Now', 'ai-feed-digest' );

		return $actions;
	}

	/**
	 * Handle bulk actions.
	 *
	 * @param string $redirect_to The redirect URL.
	 * @param string $action      The action being performed.
	 * @param array  $link_ids    The link IDs being acted upon.
	 * @return string The redirect URL.
	 */
	public static function handle_bulk_actions( string $redirect_to, string $action, array $link_ids ): string {
		if ( ! in_array( $action, array( 'afd_activate', 'afd_deactivate', 'afd_fetch_now' ), true ) ) {
			return $redirect_to;
		}

		$count = 0;

		foreach ( $link_ids as $link_id ) {
			$link_id = absint( $link_id );

			switch ( $action ) {
				case 'afd_activate':
					Links::update_feed_meta( $link_id, '_afd_is_active', true );
					++$count;
					break;

				case 'afd_deactivate':
					Links::update_feed_meta( $link_id, '_afd_is_active', false );
					++$count;
					break;

				case 'afd_fetch_now':
					$link = get_bookmark( $link_id );
					if ( $link ) {
						$fetcher = new \AIFeedDigest\Feed\Fetcher();
						$result  = $fetcher->fetch( $link );
						if ( ! is_wp_error( $result ) ) {
							++$count;
						}
					}
					break;
			}
		}

		$redirect_to = add_query_arg(
			array(
				'afd_bulk_action' => $action,
				'afd_bulk_count'  => $count,
			),
			$redirect_to
		);

		return $redirect_to;
	}

	/**
	 * Display bulk action notices.
	 *
	 * @return void
	 */
	public static function display_bulk_action_notices(): void {
		if ( ! isset( $_GET['afd_bulk_action'], $_GET['afd_bulk_count'] ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['afd_bulk_action'] ) );
		$count  = absint( $_GET['afd_bulk_count'] );

		$message = '';

		switch ( $action ) {
			case 'afd_activate':
				$message = sprintf(
					/* translators: %d: number of feeds */
					_n(
						'%d feed activated.',
						'%d feeds activated.',
						$count,
						'ai-feed-digest'
					),
					$count
				);
				break;

			case 'afd_deactivate':
				$message = sprintf(
					/* translators: %d: number of feeds */
					_n(
						'%d feed deactivated.',
						'%d feeds deactivated.',
						$count,
						'ai-feed-digest'
					),
					$count
				);
				break;

			case 'afd_fetch_now':
				$message = sprintf(
					/* translators: %d: number of feeds */
					_n(
						'%d feed fetched.',
						'%d feeds fetched.',
						$count,
						'ai-feed-digest'
					),
					$count
				);
				break;
		}

		if ( $message ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( $message )
			);
		}
	}
}
