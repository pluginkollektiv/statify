<?php
/**
 * Statify: Statify class
 *
 * This file contains the plugin's base class.
 *
 * @package   Statify
 * @since     0.1.0
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * Statify.
 *
 * @since 0.1.0
 */
class Statify {

	/**
	 * Plugin options.
	 *
	 * @since  1.4.0
	 * @var    array $_options
	 */
	public static $_options;


	/**
	 * Class self initialize.
	 *
	 * @since    0.1.0
	 * @version  0.1.0
	 */
	public static function instance() {
		new self();
	}

	/**
	 * Class constructor
	 *
	 * @since    0.1.0
	 * @version  2017-01-10
	 */
	public function __construct() {
		// Nothing to do on autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Table init.
		Statify_Table::init();

		// Plugin options.
		self::$_options = wp_parse_args(
			get_option( 'statify' ),
			array(
				'days'        => 14,
				'days_show'   => 14,
				'limit'       => 3,
				'today'       => 0,
				'snippet'     => 0,
				'blacklist'   => 0,
				'show_totals' => 0,
				'skip'        => array(
					'logged_in' => 1,
					'feed'      => 1,
					'search'    => 1,
				),
			)
		);

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			add_action( 'wp_ajax_nopriv_statify_track', array( 'Statify_Frontend', 'track_visit_ajax' ) );
		} elseif ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {  // XMLRPC.
			add_filter( 'xmlrpc_methods', array( 'Statify_XMLRPC', 'xmlrpc_methods' ) );
		} elseif ( defined( 'DOING_CRON' ) && DOING_CRON ) {    // Cron.
			add_action( 'statify_cleanup', array( 'Statify_Cron', 'cleanup_data' ) );
		} elseif ( is_admin() ) {   // Backend.
			add_action( 'wpmu_new_blog', array( 'Statify_Install', 'init_site' ) );
			add_action( 'delete_blog', array( 'Statify_Uninstall', 'init_site' ) );
			add_action( 'wp_dashboard_setup', array( 'Statify_Dashboard', 'init' ) );
			add_filter( 'plugin_row_meta', array( 'Statify_Backend', 'add_meta_link' ), 10, 2 );
			add_filter( 'plugin_action_links_' . STATIFY_BASE, array( 'Statify_Backend', 'add_action_link' ) );
			add_action( 'admin_init', array( 'Statify_Settings', 'register_settings' ) );
			add_action( 'admin_menu', array( 'Statify_Settings', 'add_admin_menu' ) );
		} else {    // Frontend.
			add_action( 'template_redirect', array( 'Statify_Frontend', 'track_visit' ) );
			add_filter( 'query_vars', array( 'Statify_Frontend', 'query_vars' ) );
			add_action( 'wp_footer', array( 'Statify_Frontend', 'wp_footer' ) );
		}
	}
}
