<?php


/* Quit */
defined('ABSPATH') OR exit;


/**
* Statify_Deactivate
*
* @since 1.3.1
*/

class Statify_Deactivate
{


	/**
	* Plugin deactivation actions
	*
	* @since   1.3.1
	* @change  1.3.1
	*/

	public static function init()
	{
		/* Delete transients */
		delete_transient('statify_data');

		/* Clear cron event */
		wp_clear_scheduled_hook('statify_cleanup');
	}
}