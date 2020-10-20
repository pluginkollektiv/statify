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
	const TRACKING_METHOD_DEFAULT = 0;
	const TRACKING_METHOD_JAVASCRIPT_WITH_NONCE_CHECK = 1;
	const TRACKING_METHOD_JAVASCRIPT_WITHOUT_NONCE_CHECK = 2;

	/**
	 * Plugin options.
	 *
	 * @since  1.4.0
	 * @var    array $_options
	 */
	public static $_options;

	/**
	 * Plugin initialization.
	 *
	 * @since    1.7 Replaces previously used instance() and __construct().
	 */
	public static function init() {
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
				),
			)
		);

		// Cron.
		add_action( 'statify_cleanup', array( 'Statify_Cron', 'cleanup_data' ) );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			add_action( 'wp_ajax_nopriv_statify_track', array( 'Statify_Frontend', 'track_visit_ajax' ) );
			add_action( 'wp_ajax_statify_track', array( 'Statify_Frontend', 'track_visit_ajax' ) );
		} elseif ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {  // XMLRPC.
			add_filter( 'xmlrpc_methods', array( 'Statify_XMLRPC', 'xmlrpc_methods' ) );
		} elseif ( is_admin() ) {   // Backend.
			add_action( 'wpmu_new_blog', array( 'Statify_Install', 'init_site' ) );
			add_action( 'delete_blog', array( 'Statify_Uninstall', 'init_site' ) );
			add_action( 'wp_dashboard_setup', array( 'Statify_Dashboard', 'init' ) );
			add_filter( 'plugin_row_meta', array( 'Statify_Backend', 'add_meta_link' ), 10, 2 );
			add_filter( 'plugin_action_links_' . STATIFY_BASE, array( 'Statify_Backend', 'add_action_link' ) );
			add_action( 'admin_init', array( 'Statify_Settings', 'register_settings' ) );
			add_action( 'admin_menu', array( 'Statify_Settings', 'add_admin_menu' ) );
			add_action( 'update_option_statify', array( 'Statify_Settings', 'action_update_options' ), 10, 2 );
		} else {    // Frontend.
			add_action( 'template_redirect', array( 'Statify_Frontend', 'track_visit' ) );
			add_filter( 'query_vars', array( 'Statify_Frontend', 'query_vars' ) );
			add_action( 'wp_footer', array( 'Statify_Frontend', 'wp_footer' ) );
			if ( function_exists( 'amp_is_request' ) || function_exists( 'is_amp_endpoint' ) ) {
				// Automattic AMP plugin present.
				add_filter( 'amp_analytics_entries', array( 'Statify_Frontend', 'amp_analytics_entries' ) );
				add_filter( 'amp_post_template_analytics', array( 'Statify_Frontend', 'amp_post_template_analytics' ) );
			}
		}
	}

	/**
	 * Get a readable date from YYYY-MM-DD database date.
	 *
	 * This function is designed as a wrapper around date_i18n() or wp_date(), if the latter is available (#166).
	 *
	 * @param string $date Raw date in "YYYY-MM-DD" format.
	 *
	 * @return string Parsed date in WP default format.
	 *
	 * @since 1.7.3
	 */
	public static function parse_date( $date ) {
		if ( function_exists( 'wp_date' ) ) { // Exists since WP 5.3.
			return wp_date( get_option( 'date_format' ), strtotime( $date ) );
		}

		return date_i18n( get_option( 'date_format' ), strtotime( $date ) );
	}

	/**
	 * Check JavaScript tracking.
	 *
	 * @return bool true if and only if one of the JavaScript tracking options is enabled.
	 */
	public static function is_javascript_tracking_enabled() {
		return in_array(
			self::$_options['snippet'],
			array(
				self::TRACKING_METHOD_JAVASCRIPT_WITH_NONCE_CHECK,
				self::TRACKING_METHOD_JAVASCRIPT_WITHOUT_NONCE_CHECK,
			),
			true
		);
	}
}
