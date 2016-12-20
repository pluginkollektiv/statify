<?php
/** Quit */
defined( 'ABSPATH' ) || exit;

/**
 * Statify_Uninstall
 *
 * @since 0.1
 */
class Statify_Uninstall {

	/**
	 * Plugin uninstall handler.
	 *
	 * @since  0.1.0
	 * @change 0.1.0
	 */
	public static function init() {

		if ( is_multisite() ) {
			$old = get_current_blog_id();
			// @ToDo: leave only get_sites, after decision which versions of WP will we supporting.
			// @ToDo Redundant with Statify_Install class. We should reduce the maintenance.
			if ( function_exists( 'get_sites' ) ) {
				$sites = get_sites();
			} elseif ( function_exists( 'wp_get_sites' ) ) {
				$sites = wp_get_sites();
			} else {
				return;
			}

			foreach ( $sites as $site ) {
				// Convert object to array.
				$site = (array) $site;

				switch_to_blog( $site['blog_id'] );
				self::_apply();
			}

			switch_to_blog( $old );
		}

		self::_apply();
	}

	/**
	 * Cleans things up for a deleted site on Multisite.
	 *
	 * @since 1.4.4
	 *
	 * @param int $site_id Site ID.
	 */
	public function init_site( $site_id ) {

		switch_to_blog( $site_id );

		self::_apply();

		restore_current_blog();
	}

	/**
	 * Deletes all plugin data.
	 *
	 * @since  0.1.0
	 * @change 1.4.0
	 */
	private static function _apply() {

		/** Delete options */
		delete_option( 'statify' );

		/** Init table */
		Statify_Table::init();

		/** Delete table */
		Statify_Table::drop();
	}
}
