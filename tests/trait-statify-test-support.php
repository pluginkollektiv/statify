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
	 * @param integer $method         Configure tracking method (default: 0).
	 * @param integer $skip_logged_in Configure tracking for logged-in users (default: 1).
	 * @param boolean $blacklist      Configure blacklist usage (default: false).
	 */
	protected function init_statify_tracking( $method = 0, $skip_logged_in = 1, $blacklist = false ) {
		$this->init_statify(
			array(
				'snippet'   => $method,
				'skip'      => array(
					'logged_in' => $skip_logged_in,
				),
				'blacklist' => $blacklist ? 1 : 0,
			)
		);
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
	protected function init_statify_widget( $days_store = 14, $days_show = 14, $top_limit = 3, $today = false, $totals = false ) {
		$this->init_statify(
			array(
				'days'        => $days_store,
				'days_show'   => $days_show,
				'limit'       => $top_limit,
				'today'       => $today ? 1 : 0,
				'show_totals' => $totals ? 1 : 0,
			)
		);
	}

	/**
	 * Initialize Statify with custom options.
	 *
	 * @param array $args Custom parameters (key => value).
	 */
	protected function init_statify( $args = array() ) {
		$options = get_option( 'statify' );

		if ( false === $options && isset( Statify::$_options ) ) {
			$options = Statify::$_options;
		}

		$options = wp_parse_args( $args, $options );

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

		for ( $i = 0; $i < $count; $i++ ) {
			$wpdb->insert( $wpdb->statify, $data );
		}
	}
}
