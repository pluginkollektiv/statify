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
	 * Get current stats value.
	 * This method always gets fresh data, no cached transients.
	 *
	 * @return array|null Statify stats value.
	 */
	protected function get_stats() {
		delete_transient( 'statify_data' );

		return Statify_Dashboard::get_stats();
	}
}
