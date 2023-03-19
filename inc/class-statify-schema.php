<?php
/**
 * Statify: Statify_Schema class
 *
 * This file contains class for the plugin's DB scheme handling.
 *
 * @package   Statify
 * @since     2.0.0
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * Statify DB Schema
 *
 * @since 2.0.0
 */
class Statify_Schema {
	/**
	 * Database tables
	 *
	 * @var string[]
	 */
	public static $tables = array(
		'statify',
		'statifymeta',
	);

	/**
	 * Needed statify db version for current plugin version
	 *
	 * @var string
	 */
	public static $db_version = '2.0.0';

	/**
	 * Definition of the custom tables.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	public static function init() {
		// Global.
		global $wpdb;

		foreach ( static::$tables as $table ) {
			$wpdb->tables[] = $table;
			$wpdb->$table = $wpdb->get_blog_prefix() . $table;
		}

		self::maybe_create_tables();
	}

	/**
	 * Create the tables.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	public static function maybe_create_tables() {
		$current_db_version = get_option( 'statify_db_version', '1.8.4' );
		if ( $current_db_version === self::$db_version ) {
			return;
		}

		// Global.
		global $wpdb, $charset_collate;

		/**
		 * Use same index length like the WordPress core
		 *
		 * @see wp_get_db_schema()
		 */
		$max_index_length = 191;

		// Include.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Create statify table.
		dbDelta(
			"CREATE TABLE {$wpdb->statify} (
				id bigint(20) unsigned NOT NULL auto_increment,
				created date NOT NULL default '0000-00-00',
				referrer varchar(255) NOT NULL default '',
				target varchar(255) NOT NULL default '',
				PRIMARY KEY (id),
				KEY referrer (referrer),
				KEY target (target),
				KEY created (created)
			) {$charset_collate};"
		);

		// Create statifymeta table.
		dbDelta(
			"CREATE TABLE {$wpdb->statifymeta} (
				meta_id bigint(20) unsigned NOT NULL auto_increment,
				statify_id bigint(20) unsigned NOT NULL default 0,
				meta_key varchar(255) default NULL,
				meta_value longtext,
				PRIMARY KEY (meta_id),
				KEY statify_id (statify_id),
				KEY meta_key (meta_key({$max_index_length}))
			) {$charset_collate};"
		);

		update_option( 'statify_db_version', self::$db_version );
	}

	/**
	 * Remove the custom tables.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	public static function drop_tables() {
		global $wpdb;

		// Remove.
		foreach ( static::$tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		delete_option( 'statify_db_version' );
	}
}
