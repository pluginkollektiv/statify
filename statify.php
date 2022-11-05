<?php
/**
 * Plugin Name: Statify
 * Description: Compact, easy-to-use and privacy-compliant stats plugin for WordPress.
 * Text Domain: statify
 * Author:      pluginkollektiv
 * Author URI:  https://pluginkollektiv.org/
 * Plugin URI:  https://statify.pluginkollektiv.org/
 * License:     GPLv3 or later
 * Version:     1.8.4
 *
 * @package WordPress
 */

/* Quit */
defined( 'ABSPATH' ) || exit;


/*  Constants */
define( 'STATIFY_FILE', __FILE__ );
define( 'STATIFY_DIR', dirname( __FILE__ ) );
define( 'STATIFY_BASE', plugin_basename( __FILE__ ) );
define( 'STATIFY_VERSION', '1.8.4' );


/* Hooks */
add_action(
	'plugins_loaded',
	array(
		'Statify',
		'init',
	)
);
register_activation_hook(
	STATIFY_FILE,
	array(
		'Statify_Install',
		'init',
	)
);
register_deactivation_hook(
	STATIFY_FILE,
	array(
		'Statify_Deactivate',
		'init',
	)
);
register_uninstall_hook(
	STATIFY_FILE,
	array(
		'Statify_Uninstall',
		'init',
	)
);


/* Autoload */
spl_autoload_register( 'statify_autoload' );

/**
 * Include classes via autoload.
 *
 * @param string $class Name of an class-file name, without file extension.
 */
function statify_autoload( $class ) {

	$plugin_classes = array(
		'Statify',
		'Statify_Backend',
		'Statify_Frontend',
		'Statify_Dashboard',
		'Statify_Install',
		'Statify_Uninstall',
		'Statify_Deactivate',
		'Statify_Settings',
		'Statify_Table',
		'Statify_XMLRPC',
		'Statify_Cron',
	);

	if ( in_array( $class, $plugin_classes, true ) ) {
		require_once sprintf(
			'%s/inc/class-%s.php',
			STATIFY_DIR,
			strtolower( str_replace( '_', '-', $class ) )
		);
	}
}
