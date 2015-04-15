<?php


/* Quit */
defined('ABSPATH') OR exit;


/**
* Statify_Backend
*
* @since 1.3.1
*/

class Statify_Backend
{


	/**
	* Hinzufügen der Meta-Links
	*
	* @since   0.1.0
	* @change  1.3.1
	*
	* @param   array   $input  Array mit Links
	* @param   string  $file   Name des Plugins
	* @return  array           Array mit erweitertem Link
	*/

	public static function add_meta_link($input, $file)
	{
		/* Restliche Plugins? */
		if ( $file !== STATIFY_BASE ) {
			return $input;
		}

		return array_merge(
			$input,
			array(
				'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=ZAQUT9RLPW8QN" target="_blank">PayPal</a>',
				'<a href="https://flattr.com/t/1733733" target="_blank">Flattr</a>'
			)
		);
	}


	/**
	* Hinzufügen des Action-Links
	*
	* @since   0.1.0
	* @change  1.3.1
	*/

	public static function add_action_link($input)
	{
		/* Rechte? */
		if ( ! current_user_can('edit_dashboard') ) {
			return $input;
		}

		/* Zusammenführen */
		return array_merge(
			$input,
			array(
				sprintf(
					'<a href="%s">%s</a>',
					add_query_arg(
						array(
							'edit' => 'statify_dashboard#statify_dashboard'
						),
						admin_url('/')
					),
					__('Settings')
				)
			)
		);
	}
}