<?php
/**
 * Statify: Statify_Counter_Column class
 *
 * This file contains the derived class for the counter column in the post table.
 *
 * @package Statify
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * Statify_Counter_Column
 *
 * This class manages the counter column in the post table to display the number of views for each post.
 */
class Statify_Counter_Column extends Statify {

	public const COLUMN_NAME = 'counter-column';
	public const SUPPORTED_POST_TYPE = array(
		'post',
		'page',
	);

	/**
	 * Initializes the counter column in the post table.
	 *
	 * This method adds the necessary actions to register the counter column and manage its display.
	 *
	 * @return void
	 */
	public static function init(): void {

		// Filter user_can_see_stats.
		if ( ! self::user_can_see_stats() ) {
			return;
		}

		/**
		 * Filters the post types that will display the Statify counter column.
		 *
		 * @param array $post_types Array of post types.
		 */
		$post_types = apply_filters( 'statify_counter_post_types', self::SUPPORTED_POST_TYPE );

		foreach ( $post_types as $post_type ) {
			add_action( "manage_edit-{$post_type}_columns", array( __CLASS__, 'register_counter_column' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( __CLASS__, 'manage_counter_column' ), 10, 2 );
		}
	}

	/**
	 * Registers the counter column in the post columns.
	 *
	 * This method adds the counter column to the post edit screen.
	 *
	 * @param array $columns An array of existing columns.
	 *
	 * @return array Updated array of columns with the counter column added.
	 */
	public static function register_counter_column( array $columns ): array {

		$columns[ self::COLUMN_NAME ] = __( 'Views', 'statify' );

		return $columns;
	}

	/**
	 * Manages the display of the counter column for a given post.
	 *
	 * This method retrieves and displays the view count for the specified post in the counter column.
	 *
	 * @param string $column_name The name of the column being managed.
	 * @param int    $post_id    The ID of the post for which the column is being displayed.
	 *
	 * @return void
	 */
	public static function manage_counter_column( string $column_name, int $post_id ): void {

		if ( self::COLUMN_NAME !== $column_name ) {
			return;
		}

		$permalink = (string) get_permalink( $post_id );

		if ( ! $permalink ) {
			return;
		}

		$target = wp_parse_url( $permalink, PHP_URL_PATH );

		echo esc_html( self::post_counter_value( $target ) );
	}

	/**
	 * Retrieves the view count for a specific target.
	 *
	 * This method queries the database to count the number of views for the given target URL.
	 *
	 * @param string $target The target URL path for which the view count is to be retrieved.
	 *
	 * @return int The number of views for the specified target.
	 */
	private static function post_counter_value( string $target ): int {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM `$wpdb->statify` WHERE `target` = %s",
			$target
		);

		$result = $wpdb->get_var( $sql );

		return (int) $result;
	}
}
