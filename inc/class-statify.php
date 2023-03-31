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

	const SKIP_USERS_NONE = 0;
	const SKIP_USERS_ALL = 1;
	const SKIP_USERS_ADMIN = 2;

	/**
	 * Plugin options.
	 *
	 * @since  1.4.0
	 * @var    array $_options
	 */
	public static $_options;

	/**
	 * Plugin version.
	 *
	 * @var string|null $plugin_version
	 */
	private static $plugin_version;

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
				'days'              => 14,
				'days_show'         => 14,
				'limit'             => 3,
				'today'             => 0,
				'snippet'           => 0,
				'blacklist'         => 0,
				'show_totals'       => 0,
				'show_widget_roles' => null, // Just for documentation, the default is calculated later.
				'skip'              => array(
					'logged_in' => self::SKIP_USERS_ALL,
				),
			)
		);

		// Cron.
		add_action( 'statify_cleanup', array( 'Statify_Cron', 'cleanup_data' ) );

		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {  // XMLRPC.
			add_filter( 'xmlrpc_methods', array( 'Statify_XMLRPC', 'xmlrpc_methods' ) );
		} elseif ( is_admin() ) {   // Backend.
			add_action( 'wp_initialize_site', array( 'Statify_Install', 'init_site' ) );
			add_action( 'wp_uninitialize_site', array( 'Statify_Uninstall', 'init_site' ) );
			add_action( 'wp_dashboard_setup', array( 'Statify_Dashboard', 'init' ) );
			add_filter( 'plugin_row_meta', array( 'Statify_Backend', 'add_meta_link' ), 10, 2 );
			add_filter( 'plugin_action_links_' . STATIFY_BASE, array( 'Statify_Backend', 'add_action_link' ) );
			add_action( 'admin_init', array( 'Statify_Settings', 'register_settings' ) );
			add_action( 'admin_menu', array( 'Statify_Settings', 'add_admin_menu' ) );
			add_action( 'admin_init', array( 'Statify_Evaluation', 'add_capability' ) );
			add_action( 'admin_menu', array( 'Statify_Evaluation', 'add_menu' ) );
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
			// Initialize REST API.
			add_filter( 'rest_api_init', array( 'Statify_Api', 'init' ) );
		}
	}

	/**
	 * Track the page view.
	 *
	 * @param string|null $referrer Referrer URL.
	 * @param string|null $target   Target URL.
	 *
	 * @return void
	 *
	 * @since  0.1.0
	 * @since  1.7.0 $is_snippet parameter added.
	 * @since  2.0.0 Migration from Statify_Frontend::track_visit to Statify::track with multiple parameters.
	 */
	protected static function track( $referrer, $target ) {
		// Fallbacks for uninitialized or omitted target and referrer values.
		if ( is_null( $target ) ) {
			$target = '/';
		}
		if ( is_null( $referrer ) ) {
			$referrer = '';
		}

		// Invalid target?
		if ( empty( $target ) || ! wp_validate_redirect( $target, false ) ) {
			return;
		}

		// Check whether tracking should be skipped for this view.
		if ( self::skip_tracking() ) {
			return;
		}

		// Sanitize referrer url.
		if ( ! empty( $referrer ) && false === self::strposa( $referrer, array( home_url(), network_admin_url() ) ) ) {
			$referrer = esc_url_raw( $referrer, array( 'http', 'https' ) );
		} else {
			$referrer = '';
		}

		// Relative target URL.
		$target = user_trailingslashit( str_replace( home_url( '/', 'relative' ), '/', $target ) );

		/* Global vars */
		global $wp_rewrite;

		// Trim target URL.
		if ( $wp_rewrite->permalink_structure ) {
			$target = wp_parse_url( $target, PHP_URL_PATH );
		}

		// Init rows.
		$data = array(
			'created'  => current_time( 'Y-m-d' ),
			'referrer' => $referrer,
			'target'   => $target,
		);

		// Insert.
		global $wpdb;
		$wpdb->insert( $wpdb->statify, $data );

		/**
		 * Fires after a visit was stored in the database
		 *
		 * @param array $data
		 * @param int   $wpdb- >insert_id
		 *
		 * @since 1.5.5
		 */
		do_action( 'statify__visit_saved', $data, $wpdb->insert_id );
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

	/**
	 * Check whether the current user can see stats.
	 *
	 * @return boolean TRUE, if the user can see stats. FALSE otherwise.
	 *
	 * @since 2.0.0 extracted into method for reusability
	 *
	 * @hook  boolean  statify__user_can_see_stats
	 * @see   https://wordpress.org/plugins/statify/
	 */
	public static function user_can_see_stats() {
		if ( isset( self::$_options['show_widget_roles'] ) ) {
			$statify_roles = self::$_options['show_widget_roles'];
			$current_user = wp_get_current_user();
			$user_roles = $current_user->roles;

			// Filter user_can_see_stats.
			$allowed_roles = array_intersect( $statify_roles, $user_roles );
			$can_see = ! empty( $allowed_roles );
		} else {
			// Backwards compatibility for older statify versions without this option.
			$can_see = current_user_can( 'edit_dashboard' );
		}

		// Filter user_can_see_stats.
		return apply_filters( 'statify__user_can_see_stats', $can_see );
	}

	/**
	 * Print JavaScript.
	 *
	 * @since 2.0.0 Moved to Statify class
	 */
	public static function add_js() {
		// Register JS.
		wp_register_script(
			'chartist_js',
			plugins_url( 'js/chartist.min.js', STATIFY_FILE ),
			array(),
			self::get_version(),
			true
		);
		wp_register_script(
			'chartist_tooltip_js',
			plugins_url( 'js/chartist-plugin-tooltip.min.js', STATIFY_FILE ),
			array( 'chartist_js' ),
			self::get_version(),
			true
		);
		wp_register_script(
			'statify_chart_js',
			plugins_url( 'js/dashboard.js', STATIFY_FILE ),
			array( 'wp-api-fetch', 'chartist_tooltip_js' ),
			self::get_version(),
			true
		);

		// Localize strings.
		wp_localize_script(
			'statify_chart_js',
			'statifyDashboard',
			array(
				'i18n'  => array(
					'error'        => esc_html__( 'Error loading data.', 'statify' ),
					'nodata'       => esc_html__( 'No data available.', 'statify' ),
					'pageview'     => esc_html__( 'Pageview', 'statify' ),
					'pageviews'    => esc_html__( 'Pageviews', 'statify' ),
					'since'        => esc_html__( 'since', 'statify' ),
					'today'        => esc_html__( 'today', 'statify' ),
					'months'       => array_map(
						function ( $m ) {
							return date_i18n( 'M', strtotime( '0000-' . $m . '-01' ) );
						},
						range( 1, 12 )
					),
				),
			)
		);
	}

	/**
	 * Print CSS
	 *
	 * @since 0.1.0
	 * @since 2.0.0 Moved to Statify class
	 */
	public static function add_style() {

		// Register CSS.
		wp_register_style(
			'chartist_css',
			plugins_url( '/css/chartist.min.css', STATIFY_FILE ),
			array(),
			self::get_version()
		);
		wp_register_style(
			'chartist_tooltip_css',
			plugins_url( '/css/chartist-plugin-tooltip.min.css', STATIFY_FILE ),
			array(),
			self::get_version()
		);
		wp_register_style(
			'statify',
			plugins_url( '/css/dashboard.min.css', STATIFY_FILE ),
			array(),
			self::get_version()
		);

		// Load CSS.
		wp_enqueue_style( 'chartist_css' );
		wp_enqueue_style( 'chartist_tooltip_css' );
		wp_enqueue_style( 'statify' );
	}

	/**
	 * Set plugin version from plugin meta data
	 *
	 * @since 1.4.0
	 * @since 2.0.0 Moved up to Statify class.
	 */
	protected static function get_version() {
		if ( ! isset( self::$plugin_version ) ) {
			$meta = get_plugin_data( STATIFY_FILE );
			self::$plugin_version = $meta['Version'];
		}

		return self::$plugin_version;
	}

	/**
	 * Rules to skip the tracking.
	 *
	 * @hook   boolean  statify__skip_tracking
	 * @see    https://wordpress.org/plugins/statify/
	 *
	 * @return boolean $skip_hook TRUE if NO tracking is desired.
	 *
	 * @since  1.2.6
	 * @since  2.0.0 Migration from Statify_Frontend to Statify class.
	 */
	private static function skip_tracking() {
		if ( function_exists( 'apply_filters_deprecated' ) ) {
			apply_filters_deprecated( 'statify_skip_tracking', array( '' ), '1.5.0', 'statify__skip_tracking' );
		}
		// Skip tracking via Hook.
		$skip_hook = apply_filters( 'statify__skip_tracking', null );
		if ( null !== $skip_hook ) {
			return $skip_hook;
		}

		// Skip tracking via User Agent.
		$user_agent = sanitize_text_field( isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '' );
		if ( is_null( $user_agent )
			|| false === $user_agent
			|| self::is_bot() ) {
			return true;
		}

		// Skip tracking via Referrer check.
		if ( self::check_referrer() ) {
			return true;
		}

		// Skip for trackbacks and robots.
		if ( is_trackback() || is_robots() ) {
			return true;
		}

		// Skip logged in users, if enabled.
		if ( self::SKIP_USERS_ALL === self::$_options['skip']['logged_in'] && is_user_logged_in() ||
			// Only skip administrators.
			self::SKIP_USERS_ADMIN === self::$_options['skip']['logged_in'] && current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Skip for "internal" requests.
		return self::is_internal();
	}

	/**
	 * Checks if user agent is a bot.
	 *
	 * @return boolean $is_bot TRUE if user agent is a bot, FALSE if not.
	 *
	 * @since 1.7.0
	 * @since 2.0.0 Migration from Statify_Frontend to Statify class, removed $user_agent parameter.
	 */
	private static function is_bot() {
		$crawler_detect = new \Jaybizzle\CrawlerDetect\CrawlerDetect();

		// Check the user agent of the current 'visitor' via the user agent and http_from header.
		return $crawler_detect->isCrawler();
	}

	/**
	 * Rules to detect internal calls to skip tracking and not print code snippet.
	 *
	 * @return boolean  $skip_hook TRUE if NO tracking is desired
	 *
	 * @since 1.6.1
	 * @since 1.9.0 Migration from Statify_Frontend to Statify class.
	 */
	protected static function is_internal() {
		// Skip for preview, 404 calls, feed, search, favicon and sitemap access.
		return is_preview() || is_404() || is_feed() || is_search()
			|| ( function_exists( 'is_favicon' ) && is_favicon() )
			|| '' !== get_query_var( 'sitemap' ) || '' !== get_query_var( 'sitemap-stylesheet' );
	}

	/**
	 * Compare the referrer URL to the disallowed keys list.
	 * De/activate this feature via settings in the Dashboard widget.
	 *
	 * @return  boolean TRUE of referrer matches disallowed keys entry and should thus be excluded.
	 *
	 * @since 1.5.0
	 * @since 1.9.0 Migration from Statify_Frontend to Statify class.
	 */
	private static function check_referrer() {
		// Return false if the disallowed-keys filter (formerly blacklist) is inactive.
		if ( ! self::$_options['blacklist'] ) {
			return false;
		}

		$referrer = filter_var(
			( isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '' ),
			FILTER_SANITIZE_URL
		);
		if ( ! is_null( $referrer ) && false !== $referrer ) {
			$referrer = wp_parse_url( $referrer, PHP_URL_HOST );
		}

		// Fallback for wp_parse_url() returning array instead of host only.
		if ( is_array( $referrer ) && isset( $referrer['host'] ) ) {
			$referrer = $referrer['host'];
		}

		// Return false if there still is no referrer to checj.
		if ( ! is_string( $referrer ) ) {
			return false;
		}

		// Finally compare referrer against the disallowed keys.
		$disallowed_keys = self::get_disallowed_keys();
		foreach ( $disallowed_keys as $item ) {
			if ( strpos( $referrer, $item ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a array from the disallowed_keys option of 'Settings' - 'Discussion' - 'Disallowed Comment Keys'.
	 *
	 * @return array
	 *
	 * @since 1.7.3 Renamed to "get_disallowed_keys" to match WP 5.5. wording.
	 * @since 1.9.0 Migration from Statify_Frontend to Statify class.
	 */
	private static function get_disallowed_keys() {
		$disallowed_keys = get_option( 'disallowed_keys' );

		if ( false === $disallowed_keys ) {
			// WordPress < 5.5 uses the old key.
			$disallowed_keys = get_option( 'blacklist_keys' );
		}

		$disallowed_keys = trim( $disallowed_keys );

		if ( empty( $disallowed_keys ) ) {
			return array();
		}

		return (array) explode( "\n", $disallowed_keys );
	}

	/**
	 * Find the position of the first occurrence of a substring in a string about a array.
	 *
	 * @param string  $haystack The string to search in.
	 * @param array   $needle   The string to search for.
	 * @param integer $offset   Search will start this number of characters counted from the beginning of the string.
	 *
	 * @return boolean
	 */
	private static function strposa( $haystack, array $needle, $offset = 0 ) {

		foreach ( $needle as $query ) {
			if ( strpos( $haystack, $query, $offset ) !== false ) {
				return true;
			} // Stop on first true result.
		}

		return false;
	}
}
