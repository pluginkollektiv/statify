<?php
/**
 * Statify: Statify_XMLRPC class
 *
 * This file contains the derived class for the plugin's XMLRPC features.
 *
 * @package   Statify
 * @since     1.1
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * Statify_XMLRPC
 *
 * @since 1.1
 */
class Statify_XMLRPC {

	/**
	 * Enhancement from the XMLRPC-method.
	 *
	 * @since   1.1.0
	 * @version 1.1.0
	 *
	 * @param   array $methods Array without Plugin-Callback.
	 *
	 * @return  array $methods Array with Plugin-Callback.
	 */
	public static function xmlrpc_methods( $methods ) {

		$methods['statify.getStats'] = array(
			__CLASS__,
			'xmlrpc_callback',
		);

		return $methods;
	}


	/**
	 * Ausführung der XMLRPC-Anfrage
	 *
	 * @since   1.1.0
	 * @version 1.2.5
	 *
	 * @param   array $args Array with parameters (access data).
	 *
	 * @return  string         String with results.
	 */
	public static function xmlrpc_callback( $args ) {

		// No access data?
		if ( empty( $args[0] ) || empty( $args[1] ) ) {
			return '{"error": "Empty login data"}';
		}

		// Login.
		$user = wp_authenticate( $args[0], $args[1] );

		// Wrong access data.
		if ( ! $user || is_wp_error( $user ) ) {
			return '{"error": "Incorrect login"}';
		}

		// Check access rights.
		if ( ! user_can( $user, 'edit_dashboard' ) ) {
			return '{"error": "User can check failed"}';
		}

		// Empty?
		$data = Statify_Dashboard::get_stats();
		if ( ! $data ) {
			return '{"error": "No data"}';
		}

		return wp_json_encode( $data['visits'] );
	}
}
