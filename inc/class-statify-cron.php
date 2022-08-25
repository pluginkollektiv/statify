<?php
/**
 * Statify: Statify_Cron class
 *
 * This file contains the derived class for the plugin's cron features.
 *
 * @package   Statify
 * @since     1.4.0
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * Statify_Cron
 *
 * @since 1.4.0
 */
class Statify_Cron extends Statify {

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

		// Aggregate.
		self::aggregate_data();

		// Optimize DB.
		$wpdb->query(
			"OPTIMIZE TABLE `$wpdb->statify`"
		);
	}

	/**
	 * Aggregate data in database.
	 *
	 * @since 1.9
	 */
	public static function aggregate_data() {
		global $wpdb;

		// Get date of last aggregation.
		if ( isset( self::$_options['last_aggregation'] ) ) {
			// Value saved, use it.
			$start = self::$_options['last_aggregation'];
		} else {
			// No? We need to clean up all data. Let's determine the oldest data in the database.
			$start = $wpdb->get_col( "SELECT MIN(`created`) FROM `$wpdb->statify`" );
			$start = $start[0];
		}

		if ( is_null( $start ) ) {
			// No data available, i.e not cleaned up yet and no data in database.
			return;
		}

		$now  = new DateTime();
		$date = new DateTime( $start );

		// Iterate over every day from start (inclusive) til now.
		while ( $date < $now ) {
			$agg = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT `created`, `referrer`, `target`, SUM(`hits`) as `hits` FROM `$wpdb->statify` WHERE `created` = %s GROUP BY `created`, `referrer`, `target`",
					$date->format( 'Y-m-d' )
				),
				ARRAY_A
			);

			// Remove non-aggregated data and insert aggregates within one transaction.
			$wpdb->query( 'START TRANSACTION' );
			$res = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM `$wpdb->statify` WHERE `created` = %s",
					$date->format( 'Y-m-d' )
				)
			);
			if ( false !== $res ) {
				foreach ( $agg as $a ) {
					if ( false === $wpdb->insert( $wpdb->statify, $a ) ) {
						$wpdb->query( 'ROLLBACK' );
						break;
					}
				}
			}
			$wpdb->query( 'COMMIT' );

			// Continue with next day.
			$date->modify( '+1 day' );
		}

		// Remember last aggregation date.
		self::$_options['last_aggregation'] = $now->format( 'Y-m-d' );
		update_option( 'statify', self::$_options );
	}
}
