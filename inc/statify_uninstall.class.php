<?php


/* Quit */
defined('ABSPATH') OR exit;


/**
* Statify_Uninstall
*
* @since 0.1
*/

class Statify_Uninstall
{


	/**
	* Uninstallation auch für MU-Blog
	*
	* @since   0.1.0
	* @change  0.1.0
	*
	* @param   integer  ID des Blogs
	*/

	public static function init($id)
	{
		/* Global */
		global $wpdb;

		/* Neuer MU-Blog */
		if ( ! empty($id) ) {
			/* Im Netzwerk? */
			if ( ! is_plugin_active_for_network(STATIFY_BASE) ) {
				return;
			}

			/* Wechsel */
			switch_to_blog( (int)$id );

			/* Installieren */
			self::_apply();

			/* Wechsel zurück */
			restore_current_blog();

			/* Raus */
			return;
		}

		/* Multisite & Network */
		if ( is_multisite() && ! empty($_GET['networkwide']) ) {
			/* Alter Blog */
			$old = $wpdb->blogid;

			/* Blog-IDs */
			$ids = $wpdb->get_col("SELECT blog_id FROM `$wpdb->blogs`");

			/* Loopen */
			foreach ($ids as $id) {
				switch_to_blog($id);
				self::_apply();
			}

			/* Wechsel zurück */
			switch_to_blog($old);

			/* Raus */
			return;
		}

		/* Single-Blog */
		self::_apply();
	}


	/**
	* Löschung der Daten
	*
	* @since   0.1.0
	* @change  1.4.0
	*/

	private static function _apply()
	{
		/* Delete options */
		delete_option('statify');

		/* Init table */
		Statify_Table::init();

		/* Delete table */
		Statify_Table::drop();
	}
}