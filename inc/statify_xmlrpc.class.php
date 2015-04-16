<?php


/* Quit */
defined('ABSPATH') OR exit;


/**
* Statify_XMLRPC
*
* @since 1.1
*/

class Statify_XMLRPC
{


	/**
	* Erweiterung der XMLRPC-Methode
	*
	* @since   1.1.0
	* @change  1.1.0
	*
	* @return  array  $methods  Array ohne Plugin-Callback
	* @return  array  $methods  Array mit Plugin-Callback
	*/

	public static function xmlrpc_methods($methods) {
		$methods['statify.getStats'] = array(
			__CLASS__,
			'xmlrpc_callback'
		);

		return $methods;
	}


	/**
	* Ausführung der XMLRPC-Anfrage
	*
	* @since   1.1.0
	* @change  1.2.5
	*
	* @param   array   $args  Array mit Parametern (Zugangsdaten)
	* @return  string         String mit Ergebnissen
	*/

	public static function xmlrpc_callback($args) {
		/* Keine Zugangsdaten? */
		if ( empty($args[0]) OR empty($args[1]) ) {
			return '{"error": "Empty login data"}';
		}

		/* Nutzer einloggen */
		$user = wp_authenticate($args[0], $args[1]);

		/* Falsche Zugangsdaten */
		if ( ! $user OR is_wp_error($user) ) {
			return '{"error": "Incorrect login"}';
		}

		/* Berechtigung prüfen */
		if ( ! user_can($user, 'edit_dashboard') ) {
			return '{"error": "User can check failed"}';
		}

		/* Leer? */
		if ( ! $data = Statify_Dashboard::get_stats() ) {
			return '{"error": "No data"}';
		}

		return json_encode($data['visits']);
	}
}