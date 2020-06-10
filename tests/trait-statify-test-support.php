<?php
/**
 * Statify test case trait.
 *
 * @package Statify
 */

/**
 * Trait Statify_Test_Support.
 *
 * Common methods to support testing Statify.
 */
trait Statify_Test_Support {
	/**
	 * Initialize Statify.
	 *
	 * @param bool $use_snippet     Configure tracking via JavaScript (default: false).
	 * @param bool $track_logged_in Configure tracking for logged-in users (default: false).
	 * @param bool $blacklist       Configure blacklist usage (default: false).
	 */
	protected function init_statify( $use_snippet = false, $track_logged_in = false, $blacklist = false ) {
		$options = get_option( 'statify' );

		$options['snippet']           = $use_snippet ? 1 : 0;
		$options['skip']['logged_in'] = $track_logged_in ? 0 : 1;
		$options['blacklist']         = $blacklist ? 1 : 0;

		update_option( 'statify', $options );

		Statify::init();
	}

	/**
	 * Initialize Statify with widget-relevant options.
	 *
	 * @param integer $days_store Number of days to store data.
	 * @param integer $days_show  Number of days to show data.
	 * @param integer $top_limit  Number of entries for top lists.
	 * @param boolean $today      Show top list only for today.
	 * @param boolean $totals     Show totals.
	 */
	protected function init_statify_2( $days_store, $days_show, $top_limit, $today, $totals ) {
		$options = get_option( 'statify' );

		if ( false === $options && isset( Statify::$_options ) ) {
			$options = Statify::$_options;
		}

		$options['days']        = $days_store;
		$options['days_show']   = $days_show;
		$options['limit']       = $top_limit;
		$options['today']       = $today ? 1 : 0;
		$options['show_totals'] = $totals ? 1 : 0;

		update_option( 'statify', $options );

		Statify::init();
	}

	/**
	 * Get current stats value.
	 * This method always gets fresh data, no cached transients.
	 *
	 * @return array|null Statify stats value.
	 */
	protected function get_stats() {
		delete_transient( 'statify_data' );

		return Statify_Dashboard::get_stats();
	}

	/**
	 * Insert datapoint(s) into database.
	 *
	 * @param string  $created  Date of creation ('YYYY-MM-DD').
	 * @param string  $referrer Referrer URL (default: empty).
	 * @param string  $target   Target path (default: empty).
	 * @param integer $count    Number of entries to create for given data (default: 1).
	 */
	protected function insert_test_data( $created, $referrer = '', $target = '', $count = 1 ) {
		global $wpdb;

		$data = array(
			'created'  => $created,
			'referrer' => $referrer,
			'target'   => $target,
		);

		for ( $i = 0; $i < $count; $i ++ ) {
			$wpdb->insert( $wpdb->statify, $data );
		}
	}
}
