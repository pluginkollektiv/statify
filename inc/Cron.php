<?php
/**
 * Statify: Cron class
 *
 * This file contains the derived class for the plugin's cron features.
 *
 * @package   Statify
 * @since     1.4.0
 */

namespace Statify;

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * Statify Cron
 *
 * @since 1.4.0
 * @since 2.0.0 renamed to Statify\Cron
 */
class Cron extends Statify {

	/**
	 * Cleanup obsolete DB values
	 *
	 * @since    0.3.0
	 * @version  1.4.0
	 */
	public static function cleanup_data() {

		// Global.
		global $wpdb;

		// Remove items.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `$wpdb->statify` WHERE created <= SUBDATE(%s, %d)",
				current_time( 'Y-m-d' ),
				(int) self::$_options['days']
			)
		);

		// Optimize DB.
		$wpdb->query(
			"OPTIMIZE TABLE `$wpdb->statify`"
		);
	}
}
