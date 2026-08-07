<?php
/**
 * Plugin Name:       Statify
 * Plugin URI:        https://statify.pluginkollektiv.org/
 * Description:       Compact, easy-to-use and privacy-compliant stats plugin for WordPress.
 * Version:           2.0.2
 * Requires at least: 5.1
 * Requires PHP:      7.4
 * Author:            pluginkollektiv
 * Author URI:        https://pluginkollektiv.org/
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       statify
 *
 * @package WordPress
 */

/* Quit */
defined( 'ABSPATH' ) || exit;

/*  Constants */
define( 'STATIFY_FILE', __FILE__ );
define( 'STATIFY_DIR', __DIR__ );
define( 'STATIFY_BASE', plugin_basename( __FILE__ ) );
define( 'STATIFY_VERSION', '2.0.2' );

/* Hooks */
add_action( 'plugins_loaded', array( 'Statify', 'init' ) );
register_activation_hook( STATIFY_FILE, array( 'Statify_Install', 'init' ) );
register_deactivation_hook( STATIFY_FILE, array( 'Statify_Deactivate', 'init' ) );
register_uninstall_hook( STATIFY_FILE, array( 'Statify_Uninstall', 'init' ) );

/* Autoload */
spl_autoload_register( 'statify_autoload' );

/**
 * Include classes via autoload.
 *
 * @param string $class_name Name of an class-file name, without file extension.
 */
function statify_autoload( string $class_name ): void {

	$plugin_classes = array(
		'Statify',
		'Statify_Api',
		'Statify_Backend',
		'Statify_Evaluation',
		'Statify_Frontend',
		'Statify_Dashboard',
		'Statify_Install',
		'Statify_Uninstall',
		'Statify_Deactivate',
		'Statify_Settings',
		'Statify_Table',
		'Statify_Cron',
	);

	if ( in_array( $class_name, $plugin_classes, true ) ) {
		require_once sprintf(
			'%s/inc/class-%s.php',
			STATIFY_DIR,
			strtolower( str_replace( '_', '-', $class_name ) )
		);
	} elseif ( 0 === strncmp( $class_name, 'Jaybizzle\\CrawlerDetect\\', 24 ) && ! class_exists( $class_name, false ) ) {
		require_once sprintf(
			'%s/lib/%s.php',
			STATIFY_DIR,
			str_replace( '\\', DIRECTORY_SEPARATOR, $class_name )
		);
	}
}
