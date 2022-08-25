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
		// Set target & referrer.
		$target   = null;
		$referrer = null;
		if ( self::is_javascript_tracking_enabled() ) {
			if ( ! $is_snippet ) {
				return false;
			}

			if ( isset( $_REQUEST['statify_target'] ) ) {
				$target = filter_var( wp_unslash( $_REQUEST['statify_target'] ), FILTER_SANITIZE_URL );
			}
			if ( isset( $_REQUEST['statify_referrer'] ) ) {
				$referrer = filter_var( wp_unslash( $_REQUEST['statify_referrer'] ), FILTER_SANITIZE_URL );
			}
		} else {
			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				$target = filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_URL );
			}
			if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				$referrer = filter_var( wp_unslash( $_SERVER['HTTP_REFERER'] ), FILTER_SANITIZE_URL );
			}
		}

		// Fallbacks for uninitialized or omitted target and referrer values.
		if ( is_null( $target ) || false === $target ) {
			$target = '/';
		}
		if ( is_null( $referrer ) || false === $referrer ) {
			$referrer = '';
		}

		/* Invalid target? */
		if ( empty( $target ) || ! wp_validate_redirect( $target, false ) ) {
			return self::_jump_out( $is_snippet );
		}

		/* Check whether tracking should be skipped for this view. */
		if ( self::_skip_tracking() ) {
			return self::_jump_out( $is_snippet );
		}

		/* Global vars */
		global $wpdb, $wp_rewrite;

		/* Init rows */
		$data = array(
			'created'  => '',
			'referrer' => '',
			'target'   => '',
		);

		// Set request timestamp.
		$data['created'] = current_time( 'Y-m-d' );

		$needles = array( home_url(), network_admin_url() );

		// Sanitize referrer url.
		if ( ! empty( $referrer ) && self::strposa( $referrer, $needles ) === false ) {
			$data['referrer'] = esc_url_raw( $referrer, array( 'http', 'https' ) );
		}

		/* Relative target url */
		$data['target'] = user_trailingslashit( str_replace( home_url( '/', 'relative' ), '/', $target ) );

		// Trim target url.
		if ( $wp_rewrite->permalink_structure ) {
			$data['target'] = wp_parse_url( $data['target'], PHP_URL_PATH );
		}

		// Sanitize target url.
		$data['target'] = esc_url_raw( $data['target'] );

		// Insert.
		$wpdb->insert( $wpdb->statify, $data );

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
			|| self::is_bot( $user_agent ) ) {
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
	 * @param  string $user_agent Server user agent string.
	 *
	 * @return boolean $is_bot     TRUE if user agent is a bot, FALSE if not.
	 */
	private static function is_bot( $user_agent ) {
		$user_agent = strtolower( $user_agent );

		$identifiers = array(
			'bot',
			'slurp',
			'crawler',
			'spider',
			'curl',
			'facebook',
			'fetch',
			'python',
			'wget',
			'monitor',
		);

		foreach ( $identifiers as $identifier ) {
			if ( strpos( $user_agent, $identifier ) !== false ) {
				return true;
			}
		}

		return false;
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

		// Add endpoint to script.
		wp_localize_script(
			'statify-js',
			'statify_ajax',
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'statify_track' ),
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
