<?php
/**
 * Statify: Statify_Deactivate class
 *
 * This file contains the derived class for the plugin's deactivation actions.
 *
 * @package   Statify
 * @since     1.4.0
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * Statify_Deactivate
 *
 * @since 1.4.0
 */
class Statify_Deactivate {

	/**
	 * Plugin deactivation actions
	 *
	 * @since    1.4.0
	 * @version  1.4.0
	 */
	public static function init() {

		// Delete transients.
		delete_transient( 'statify_data' );

		// Delete cron event.
		wp_clear_scheduled_hook( 'statify_cleanup' );
	}
}
