<?php

/* Quit */
defined( 'ABSPATH' ) OR exit;

/**
 * Statify_Install
 *
 * @since 0.1
 */
class Statify_Install {
	/**
	 * Plugin activaton handler.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $network_wide Whether the plugin was activated network-wide or not.
	 */
	public static function init( $network_wide = false ) {
		global $wpdb;

		// Create tables for each site in a network.
		if ( is_multisite() && $network_wide ) {
			// Todo: Use get_sites() in WordPress 4.6+
			$ids = $wpdb->get_col( "SELECT blog_id FROM `$wpdb->blogs`" );

			foreach ( $ids as $site_id ) {
				switch_to_blog( $site_id );
				self::_apply();
			}

			restore_current_blog();
		} else {
			self::_apply();
		}
	}

	/**
	 * Sets up the plugin for a newly created site on Multisite.
	 *
	 * @since 1.4.4
	 *
	 * @param int $site_id Site ID.
	 */
	public function init_site( $site_id ) {
		switch_to_blog( (int) $site_id );

		self::_apply();

		restore_current_blog();
	}

	/**
	 * Creates the database tables needed for the plugin.
	 *
	 * @since  0.1.0
	 * @change 1.4.0
	 */
	private static function _apply() {
		// Todo: Remove. Use sane defaults instead.
		add_option(
			'statify',
			array()
		);

		// Cleanup any leftover transients
		delete_transient( 'statify_data' );

		// Set up the cron event.
		if ( ! wp_next_scheduled( 'statify_cleanup' ) ) {
			wp_schedule_event(
				time(),
				'daily',
				'statify_cleanup'
			);
		}

		// Create the actual tables.
		Statify_Table::init();
		Statify_Table::create();
	}
}