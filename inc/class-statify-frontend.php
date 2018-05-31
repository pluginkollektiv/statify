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
	 * @version  1.4.2
	 *
	 * @return   bool
	 */
	public static function track_visit() {

		/* Init vars */
		$use_snippet = self::$_options['snippet'];
		$is_snippet  = $use_snippet && get_query_var( 'statify_target' );

		/* Set target & referrer */
		if ( $is_snippet ) {
			$target   = urldecode( get_query_var( 'statify_target' ) );
			$referrer = urldecode( get_query_var( 'statify_referrer' ) );
		} elseif ( ! $use_snippet ) {
			$target   = filter_var(
				( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' ),
				FILTER_SANITIZE_URL
			);
			if ( is_null( $target ) || false === $target ) {
				$target = '/';
			} else {
				$target = wp_unslash( $target );
			}

			$referrer = filter_var(
				( isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '' ),
				FILTER_SANITIZE_URL
			);
			if ( is_null( $referrer ) || false === $referrer ) {
				$referrer = '';
			}
		} else {
			return false;
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
		$data['created'] = strftime( '%Y-%m-%d', current_time( 'timestamp' ) );

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
		$user_agent = filter_var(
			( isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '' ),
			FILTER_SANITIZE_STRING
		);
		if ( is_null( $user_agent )
			|| false === $user_agent
			|| ! preg_match( '/(?:Windows|Macintosh|Linux|iPhone|iPad)/', $user_agent ) ) {
			return true;
		}

		// Skip tracking via Referrer check and Conditional_Tags.
		return ( self::check_referrer() || is_trackback() || is_robots() || is_user_logged_in()
			|| self::_is_internal()
		);
	}

	/**
	 * Rules to detect internal calls to skip tracking and not print code snippet.
	 *
	 * @since    1.6.1
	 *
	 * @return   boolean  $skip_hook  TRUE if NO tracking is desired
	 */
	private static function _is_internal() {
		return is_feed() || is_preview() || is_404() || is_search();
	}

	/**
	 * Compare the referrer url to the blacklist data.
	 * De/activate this feature via settings in the Dashboard widget.
	 *
	 * @since   1.5.0
	 * @version 1.6.3
	 *
	 * @return  boolean TRUE of referrer matches blacklist entry and should thus be excluded.
	 */
	private static function check_referrer() {

		// Return false if the blacklist filter is inactive.
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

		// Finally compare referrer against the blacklist.
		$blacklist = self::get_blacklist_keys();
		foreach ( $blacklist as $item ) {
			if ( strpos( $referrer, $item ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a array from the blacklist option of 'Settings' - 'Discussion' - 'Comment Blacklist'.
	 *
	 * @since  2016-12-21
	 *
	 * @return array
	 */
	private static function get_blacklist_keys() {

		$blacklist = trim( get_option( 'blacklist_keys' ) );

		if ( empty( $blacklist ) ) {
			return array();
		}

		return (array) explode( "\n", $blacklist );
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
			header( 'Content-type: text/javascript', true, 204 );
			exit;
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

		/* Skip by option */
		if ( ! self::$_options['snippet'] ) {
			return;
		}

		/* Skip by internal rules (#84) */
		if ( self::_is_internal() ) {
			return;
		}

		/* Load template */
		load_template(
			wp_normalize_path(
				sprintf(
					'%s/views/js-snippet.php',
					STATIFY_DIR
				)
			)
		);
	}
}
