<?php


/* Quit */
defined('ABSPATH') OR exit;


/**
* Statify Table
*
* @since 0.6
*/

class Statify_Table
{


	/**
	* Definition der Tabelle
	*
	* @since   0.6.0
	* @change  1.2.4
	*/

	public static function init()
	{
		/* Global */
		global $wpdb;

		/* Name */
		$table = 'statify';

		/* Als Array */
		$wpdb->tables[] = $table;

		/* Mit Prefix */
		$wpdb->$table = $wpdb->get_blog_prefix() . $table;
	}


	/**
	* Anlegen der Tabelle
	*
	* @since   0.6.0
	* @change  1.2.4
	*/

	public static function create()
	{
		/* Global */
		global $wpdb;

		/* Existenz prüfen */
		if ( $wpdb->get_var("SHOW TABLES LIKE '$wpdb->statify'") == $wpdb->statify ) {
			return;
		}

		/* Einbinden */
		require_once(ABSPATH. 'wp-admin/includes/upgrade.php');

		/* Anlegen */
		dbDelta(
			"CREATE TABLE `$wpdb->statify` (
			`id` bigint(20) unsigned NOT NULL auto_increment,
			  `created` date NOT NULL default '0000-00-00',
			  `referrer` varchar(255) NOT NULL default '',
			  `target` varchar(255) NOT NULL default '',
			  PRIMARY KEY  (`id`),
			  KEY `referrer` (`referrer`),
			  KEY `target` (`target`),
			  KEY `created` (`created`)
			);"
		);
	}


	/**
	* Löschung der Tabelle
	*
	* @since   0.6.0
	* @change  1.2.4
	*/

	public static function drop()
	{
		/* Global */
		global $wpdb;

		/* Remove */
		$wpdb->query("DROP TABLE IF EXISTS `$wpdb->statify`");
	}
}