<?php
/**
 * Plugin Name: Statify
 * Description: Compact, easy-to-use and privacy-compliant stats plugin for WordPress.
 * Text Domain: statify
 * Author:      pluginkollektiv
 * Author URI:  https://pluginkollektiv.org/
 * Plugin URI:  https://statify.pluginkollektiv.org/
 * License:     GPLv3 or later
 * Version:     2.0.0
 *
 * @package WordPress
 */

/* Quit */
defined( 'ABSPATH' ) || exit;


/*  Constants */
define( 'STATIFY_FILE', __FILE__ );
define( 'STATIFY_DIR', dirname( __FILE__ ) );
define( 'STATIFY_BASE', plugin_basename( __FILE__ ) );
define( 'STATIFY_VERSION', '2.0.0' );


/* Hooks */
add_action( 'plugins_loaded', array( 'Pluginkollektiv\Statify\Statify', 'init' ) );
register_activation_hook( STATIFY_FILE, array( 'Pluginkollektiv\Statify\Install', 'init' ) );
register_deactivation_hook( STATIFY_FILE, array( 'Pluginkollektiv\\Statify\Deactivate', 'init' ) );
register_uninstall_hook( STATIFY_FILE, array( 'Pluginkollektiv\\Statify\Uninstall', 'init' ) );

/* Composer Autoload */
require __DIR__ . '/vendor/autoload.php';
