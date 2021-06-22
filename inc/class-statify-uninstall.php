<?php
/**
 * Statify: Statify_Uninstall class
 *
 * This file contains the derived class for the plugin's uninstallation features.
 *
 * @package   Statify
 * @since     0.1
 */

// Quit if accessed outside WP context.
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
	 * @since   0.1.0
	 * @version 0.1.0
	 */
	public static function init() {
		if ( is_multisite() ) {
			$old   = get_current_blog_id();
			$sites = get_sites();

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
	public static function init_site( $site_id ) {

		switch_to_blog( $site_id );

		self::_apply();

		restore_current_blog();
	}

	/**
	 * Deletes all plugin data.
	 *
	 * @since   0.1.0
	 * @version 1.4.0
	 */
	private static function _apply() {

		// Delete options.
		delete_option( 'statify' );

		// Init table.
		Statify_Table::init();

		// Delete table.
		Statify_Table::drop();
	}
}
