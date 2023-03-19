<?php
/**
 * Statify: Statify_Frontend class
 *
 * This file contains the derived class for the plugin's frontend features.
 *
 * @package   Statify
 * @since     1.4.0
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * Statify_Frontend
 *
 * @since 1.4.0
 */
class Statify_Frontend extends Statify {

	/**
	 * Statify meta fields for tracking
	 *
	 * @var array
	 */
	private static $tracking_meta = array();

	/**
	 * Default statify tracking data
	 *
	 * @var array
	 */
	private static $tracking_data = array();

	/**
	 * Initialization of tracking data
	 *
	 * @return void
	 */
	public static function init_tracking_data() {
		self::$tracking_data['target'] = isset( $_SERVER['REQUEST_URI'] )
			? filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_URL )
			: '/';

		self::$tracking_data['referrer'] = isset( $_SERVER['HTTP_REFERER'] )
			? filter_var( wp_unslash( $_SERVER['HTTP_REFERER'] ), FILTER_SANITIZE_URL )
			: '';

		self::$tracking_data = apply_filters( 'statify__tracking_data', self::$tracking_data );

		self::$tracking_meta = array(
			array(
				'meta_key' => 'title',
				'meta_value' => wp_get_document_title(),
				'type' => 'text',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
		self::$tracking_meta = apply_filters( 'statify__tracking_meta', self::$tracking_meta, self::$tracking_data );
	}

	/**
	 * Track the page view
	 *
	 * @since    0.1.0
	 * @since    1.7.0 $is_snippet parameter added.
	 * @version  1.7.0
	 *
	 * @param boolean $is_snippet Is tracking triggered via JS (default: false).
	 *
	 * @return   boolean
	 */
	public static function track_visit( $is_snippet = false ) {
		if ( empty( self::$tracking_data ) ) {
			self::init_tracking_data();
		}

		if ( self::is_javascript_tracking_enabled() ) {
			if ( ! $is_snippet ) {
				return false;
			}

			$json = file_get_contents( 'php://input' );
			$raw_data = json_decode( $json, true );
			if ( ! $raw_data || ! isset( $raw_data['statify_tracking_data'] ) ) {
				return false;
			}

			$tracking_data = array(
				'target' =>
					isset( $raw_data['statify_tracking_data']['target'] )
					? filter_var( wp_unslash( $raw_data['statify_tracking_data']['target'] ), FILTER_SANITIZE_URL )
					: '/',
				'referrer' =>
					isset( $raw_data['statify_tracking_data']['referrer'] )
					? filter_var( wp_unslash( $raw_data['statify_tracking_data']['referrer'] ), FILTER_SANITIZE_URL )
					: '',
			);

			$tracking_meta = array();
			if ( isset( $raw_data['statify_tracking_meta'] ) && is_array( $raw_data['statify_tracking_meta'] ) ) {
				$tracking_meta = $raw_data['statify_tracking_meta'];
			}
		} else {
			$tracking_data = self::$tracking_data;
			$tracking_meta = wp_list_pluck( self::$tracking_meta, 'meta_value', 'meta_key' );
		}

		// Invalid target.
		if ( ! wp_validate_redirect( $tracking_data['target'], false ) ) {
			return self::_jump_out( $is_snippet );
		}

		// Check whether tracking should be skipped for this view.
		if ( self::_skip_tracking() ) {
			return self::_jump_out( $is_snippet );
		}

		// Global vars.
		global $wpdb, $wp_rewrite;

		// Init rows.
		$data = array(
			'created'  => '',
			'referrer' => '',
			'target'   => '',
		);

		// Set request timestamp.
		$data['created'] = current_time( 'Y-m-d' );

		$needles = array( home_url(), network_admin_url() );

		// Sanitize referrer url.
		if ( self::strposa( $tracking_data['referrer'], $needles ) === false ) {
			$data['referrer'] = filter_var( $tracking_data['referrer'], FILTER_SANITIZE_URL );
			$data['referrer'] = esc_url_raw( $data['referrer'], array( 'http', 'https' ) );
		}

		// Relative target url.
		$data['target'] = filter_var( $tracking_data['target'], FILTER_SANITIZE_URL );
		$data['target'] = user_trailingslashit( str_replace( home_url( '/', 'relative' ), '/', $data['target'] ) );

		// Trim target url.
		if ( $wp_rewrite->permalink_structure ) {
			$data['target'] = wp_parse_url( $data['target'], PHP_URL_PATH );
		}

		// Escaping target url.
		$data['target'] = esc_url_raw( $data['target'] );

		// Insert.
		$wpdb->insert( $wpdb->statify, $data );

		$statify_id = $wpdb->insert_id;

		foreach ( self::$tracking_meta as $meta_field ) {
			if ( array_key_exists( $meta_field['meta_key'], $tracking_meta ) ) {
				$meta_value = $tracking_meta[ $meta_field['meta_key'] ];

				$sanitize_function = isset( $meta_field['sanitize_callback'] ) && is_callable( $meta_field['sanitize_callback'] )
					? $meta_field['sanitize_callback']
					: 'sanitize_text_field';

				$meta_value = call_user_func( $sanitize_function, $meta_value );

				// Init rows.
				$data = array(
					'statify_id' => $statify_id,
					'meta_key' => $meta_field['meta_key'],
					'meta_value' => $meta_value,
				);

				$wpdb->insert( $wpdb->statifymeta, $data );
			}
		}

		/**
		 * Fires after a visit was stored in the database
		 *
		 * @since 1.5.5
		 *
		 * @param array $data
		 * @param int $wpdb->insert_id
		 */
		do_action( 'statify__visit_saved', $data, $wpdb->insert_id );

		// Jump!
		return self::_jump_out( $is_snippet );
	}

	/**
	 * Track the page view via AJAX.
	 *
	 * @return void
	 */
	public static function track_visit_ajax() {
		// Only do something if snippet use is actually configured.
		if ( ! self::is_javascript_tracking_enabled() ) {
			return;
		}

		// Check AJAX referrer.
		if ( Statify::TRACKING_METHOD_JAVASCRIPT_WITH_NONCE_CHECK === self::$_options['snippet'] ) {
			check_ajax_referer( 'statify_track' );
		}

		self::track_visit( true );
	}

	/**
	 * Find the position of the first occurrence of a substring in a string about a array.
	 *
	 * @param string $haystack The string to search in.
	 * @param array  $needle   The string to search for.
	 * @param int    $offset   Search will start this number of characters counted from the beginning of the string.
	 *
	 * @return bool
	 */
	private static function strposa( $haystack, array $needle, $offset = 0 ) {

		foreach ( $needle as $query ) {
			if ( strpos( $haystack, $query, $offset ) !== false ) {
				return true;
			} // Stop on first true result.
		}

		return false;
	}

	/**
	 * Rules to skip the tracking
	 *
	 * @since    1.2.6
	 * @version  1.6.3
	 *
	 * @hook     boolean  statify__skip_tracking
	 * @see      https://wordpress.org/plugins/statify/
	 *
	 * @return   boolean  $skip_hook  TRUE if NO tracking is desired
	 */
	private static function _skip_tracking() {

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
		if ( self::$_options['skip']['logged_in'] && is_user_logged_in() ) {
			return true;
		}

		// Skip for "internal" requests.
		return self::_is_internal();
	}

	/**
	 * Checks if user agent is a bot.
	 *
	 * @since 1.7.0
	 *
	 * @return boolean $is_bot     true if user agent is a bot, false if not.
	 */
	private static function is_bot() {
		$crawler_detect = new \Jaybizzle\CrawlerDetect\CrawlerDetect();

		// Check the user agent of the current 'visitor' via the user agent and http_from header.
		return $crawler_detect->isCrawler();
	}

	/**
	 * Rules to detect internal calls to skip tracking and not print code snippet.
	 *
	 * @since    1.6.1
	 *
	 * @return   boolean  $skip_hook  TRUE if NO tracking is desired
	 */
	private static function _is_internal() {
		// Skip for preview, 404 calls, feed, search, favicon and sitemap access.
		return is_preview() || is_404() || is_feed() || is_search()
			|| ( function_exists( 'is_favicon' ) && is_favicon() )
			|| '' !== get_query_var( 'sitemap' ) || '' !== get_query_var( 'sitemap-stylesheet' );
	}

	/**
	 * Compare the referrer URL to the disallowed keys list.
	 * De/activate this feature via settings in the Dashboard widget.
	 *
	 * @since   1.5.0
	 * @version 1.6.3
	 *
	 * @return  boolean TRUE of referrer matches disallowed keys entry and should thus be excluded.
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
	 * @since  2016-12-21
	 * @since 1.7.3 Renamed to "get_disallowed_keys" to match WP 5.5. wording.
	 *
	 * @return array
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
	 * Send JavaScript headers or return false
	 *
	 * @since    1.1.0
	 * @version  1.3.1
	 *
	 * @param   boolean $is_snippet Snippet type.
	 *
	 * @return  mixed  Exit or return depending on snippet type.
	 */
	private static function _jump_out( $is_snippet ) {

		if ( $is_snippet ) {
			nocache_headers();
			wp_die( '', '', 204 );
		}

		return false;
	}


	/**
	 * Declare GET variables for further use
	 *
	 * @since    1.1.0
	 * @version  1.3.1
	 *
	 * @param   array $vars Input with existing variables.
	 *
	 * @return  array  $vars  Output with plugin variables
	 */
	public static function query_vars( $vars ) {

		$vars[] = 'statify_referrer';
		$vars[] = 'statify_target';

		return $vars;
	}


	/**
	 * Print JavaScript snippet
	 *
	 * @since    1.1.0
	 * @version  1.4.1
	 */
	public static function wp_footer() {
		// JS tracking disabled or AMP is used for the current request.
		if (
			! self::is_javascript_tracking_enabled() ||
			( function_exists( 'amp_is_request' ) && amp_is_request() ) ||
			( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() )
		) {
			return;
		}

		// Skip by internal rules (#84).
		if ( self::_is_internal() ) {
			return;
		}

		wp_enqueue_script(
			'statify-js',
			plugins_url( 'js/snippet.min.js', STATIFY_FILE ),
			array(),
			STATIFY_VERSION,
			true
		);

		// Add endpoint and tracking_information to script.
		wp_localize_script(
			'statify-js',
			'statify_ajax',
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'statify_track' ),
				'tracking_data' => self::$tracking_data,
				'tracking_meta' => wp_list_pluck( self::$tracking_meta, 'meta_value', 'meta_key' ),
			)
		);
	}

	/**
	 * Add amp-analytics for Standard and Transitional mode.
	 *
	 * @see https://amp-wp.org/documentation/playbooks/analytics/
	 *
	 * @param array $analytics_entries Analytics entries.
	 */
	public static function amp_analytics_entries( $analytics_entries ) {
		if ( ! is_array( $analytics_entries ) ) {
			$analytics_entries = array();
		}

		// Analytics script is only relevant, if "JS" tracking is enabled, to prevent double tracking.
		if ( self::is_javascript_tracking_enabled() ) {
			$analytics_entries['statify'] = array(
				'type'   => '',
				'config' => wp_json_encode( self::make_amp_config() ),
			);
		}

		return $analytics_entries;
	}

	/**
	 * Add AMP-analytics for Reader mode.
	 *
	 * @see https://amp-wp.org/documentation/playbooks/analytics/
	 *
	 * @param array $analytics Analytics.
	 */
	public static function amp_post_template_analytics( $analytics ) {
		if ( ! is_array( $analytics ) ) {
			$analytics = array();
		}

		// Analytics script is only relevant, if "JS" tracking is enabled, to prevent double tracking.
		if ( self::is_javascript_tracking_enabled() ) {
			$analytics['statify'] = array(
				'type'        => '',
				'attributes'  => array(),
				'config_data' => self::make_amp_config(),
			);
		}

		return $analytics;
	}

	/**
	 * Generate AMP-analytics configuration.
	 *
	 * @return array Configuration array.
	 */
	private static function make_amp_config() {
		return array(
			'requests'       => array(
				'pageview' => admin_url( 'admin-ajax.php' ),
			),
			'extraUrlParams' => array(
				'action'           => 'statify_track',
				'_ajax_nonce'      => wp_create_nonce( 'statify_track' ),
				'statify_referrer' => '${documentReferrer}',
				'statify_target'   => '${canonicalPath}amp/',
			),
			'triggers'       => array(
				'trackPageview' => array(
					'on'      => 'visible',
					'request' => 'pageview',
				),
			),
			'transport'      => array(
				'beacon'  => true,
				'xhrpost' => true,
				'image'   => false,
			),
		);
	}
}
