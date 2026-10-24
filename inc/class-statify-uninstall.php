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
	public static function init(): void {
		if ( is_multisite() ) {
			$sites = get_sites( array( 'fields' => 'ids' ) );

			foreach ( $sites as $site ) {
				self::apply_for_site( $site );
			}
		}

		self::apply();
	}

	/**
	 * Cleans things up for a deleted site on Multisite.
	 *
	 * @since 1.4.4
	 *
	 * @param WP_Site $old_site Site object.
	 */
	public static function init_site( WP_Site $old_site ): void {
		self::apply_for_site( (int) $old_site->blog_id );
	}

	/**
	 * Deletes all plugin data.
	 *
	 * @since   0.1.0
	 * @version 1.4.0
	 */
	private static function apply(): void {

		// Delete options.
		delete_option( 'statify' );

		// Init table.
		Statify_Table::init();

		// Delete table.
		Statify_Table::drop();
	}

	/**
	 * Apply the uninstallation routine on a specific site.
	 *
	 * @param int $site_id Site ID.
	 */
	private static function apply_for_site( int $site_id ): void {
		switch_to_blog( $site_id );
		try {
			self::apply();
		} finally {
			restore_current_blog();
		}
	}
}
