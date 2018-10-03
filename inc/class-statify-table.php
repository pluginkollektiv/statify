<?php
/**
 * Statify: Statify_Table class
 *
 * This file contains class for the plugin's DB table handling.
 *
 * @package   Statify
 * @since     0.6
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * Statify Table
 *
 * @since 0.6
 */
class Statify_Table {

	/**
	 * Definition of the custom table.
	 *
	 * @since   0.6.0
	 * @version 1.2.4
	 */
	public static function init() {

		// Global.
		global $wpdb;

		// Name.
		$table = 'statify';

		// As array.
		$wpdb->tables[] = $table;

		// With prefix.
		$wpdb->$table = $wpdb->get_blog_prefix() . $table;
	}


	/**
	 * Create the table.
	 *
	 * @since   0.6.0
	 * @version 1.2.4
	 */
	public static function create() {
		global $wpdb;

		// If existent.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->statify'" ) === $wpdb->statify ) {
			return;
		}

		// Include.
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Create.
		dbDelta(
			"CREATE TABLE `$wpdb->statify` (
			`id` bigint(20) unsigned NOT NULL auto_increment,
			  `created` date NOT NULL default '0000-00-00',
			  `referrer` varchar(255) NOT NULL default '',
			  `target` varchar(255) NOT NULL default '',
			  PRIMARY KEY  (`id`),
			  KEY `created` (`created`)" .
			( self::can_create_index() ? ',
			  KEY `referrer` (`referrer`),
			  KEY `target` (`target`)' : '' ) . '
			);'
		);
	}


	/**
	 * Remove the custom table.
	 *
	 * @since   0.6.0
	 * @version 1.2.4
	 */
	public static function drop() {

		global $wpdb;

		// Remove.
		$wpdb->query( "DROP TABLE IF EXISTS `$wpdb->statify`" );
	}

	/**
	 * Check if indices on VARCHAR(255) columns can be created.
	 *
	 * @since 1.7.0
	 *
	 * @return boolean
	 */
	private static function can_create_index() {
		global $wpdb;

		// Check the storage engine. Only InnoDB is affected.
		$row = $wpdb->get_row( "SHOW VARIABLES LIKE 'storage_engine'" );
		if ( ! empty( $row ) && 'innodb' === strtolower( (string) $row->Value ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			// Check for default character set. Problem only occurs on utf8mb4 (4 Byte per character).
			$default_charset = $wpdb->get_row( "SHOW VARIABLES LIKE 'character_set_database'" );
			if ( ! empty( $default_charset ) && 'innodb' === strtolower( (string) $row->Value ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				// Check for presence of the "large_prefix" extension.
				$row = $wpdb->get_row( "SHOW VARIABLES LIKE 'innodb_large_prefix'" );
				if ( ! empty( $row ) && in_array( strtolower( (string) $row->Value ), array( '1', 'on' ) ) ) {  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
					// "large_prefix" extension active, so we can abort here.
					return true;
				}

				/*
				 * If not enabled, we can check for DB version, because the row/storage format has
				 *  changed in MySQL 5.7.7 and MariaDB 10.2, so the problem does not occur any more.
				 */
				$row = $wpdb->get_row( "SHOW VARIABLES LIKE 'version'" );
				if ( empty( $row ) ) {
					// Should not happen, but if we cannot determine the version, abort hiere.
					return false;
				}
				$version_split = explode( '-', $row->Value, 2 );    // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				$version       = $version_split[0];
				if ( false !== strpos( strtolower( $row->Value ), 'mariadb' ) ) {   // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
					if ( version_compare( $version, '10.2', '>=' ) ) {
						return true;
					}
				} elseif ( version_compare( $version, '5.7.7', '>=' ) ) {
					return true;
				}

				// On older versions it will NOT work, if file format is Barracuda.
				$row = $wpdb->get_row( "SHOW VARIABLES LIKE 'innodb_file_format'" );
				if ( 'barracuda' !== strtolower( $row->Value ) ) {  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
					return false;
				}

				// Also "innodb_file_per_table" is required then.
				$row = $wpdb->get_row( "SHOW VARIABLES LIKE 'innodb_file_per_table'" );
				if ( ! in_array( strtolower( $row->Value ), array( '1', 'on' ) ) ) {    // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
					return false;
				}
			}
		}

		// No reason found against creating indices, so return TRUE here.
		return true;
	}
}
