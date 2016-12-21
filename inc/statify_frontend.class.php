<?php
/** Quit */
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

		/* Skip tracking */
		if ( self::_skip_tracking() ) {
			return self::_jump_out( $is_snippet );
		}

		/* Set target & referrer */
		if ( $is_snippet ) {
			$target   = urldecode( get_query_var( 'statify_target' ) );
			$referrer = urldecode( get_query_var( 'statify_referrer' ) );
		} elseif ( ! $use_snippet ) {
			// @codingStandardsIgnoreStart The globals are checked.
			$target   = ( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );
			$referrer = ( isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '' );
			// @codingStandardsIgnoreEnd
		} else {
			return false;
		}

		/* Invalid target? */
		if ( empty( $target ) || ! wp_validate_redirect( $target, false ) ) {
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

		/* Set request timestamp */
		$data['created'] = strftime( '%Y-%m-%d', current_time( 'timestamp' ) );

		$needles = array( home_url(), network_admin_url() );

		/* Sanitize referrer url */
		if ( ! empty( $referrer ) && self::strposa( $referrer, $needles ) === false ) {
			$data['referrer'] = esc_url_raw( $referrer, array( 'http', 'https' ) );
		}

		/* Relative target url */
		$data['target'] = user_trailingslashit( str_replace( home_url( '/', 'relative' ), '/', $target ) );

		/* Trim target url */
		if ( $wp_rewrite->permalink_structure ) {
			$data['target'] = wp_parse_url( $data['target'], PHP_URL_PATH );
		}

		/* Sanitize target url */
		$data['target'] = esc_url_raw( $data['target'] );

		/* Insert */
		$wpdb->insert( $wpdb->statify, $data );

		/* Jump! */
		return self::_jump_out( $is_snippet );
	}

	/**
	 * Find the position of the first occurrence of a substring in a string about a array.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The string to search for.
	 * @param int    $offset   Search will start this number of characters counted from the beginning of the string.
	 *
	 * @return bool
	 */
	private static function strposa( $haystack, $needle, $offset = 0 ) {

		if ( ! is_array( $needle ) ) {
			$needle = array( $needle );
		}

		foreach ( (array) $needle as $query ) {
			if ( strpos( $haystack, $query, $offset ) !== false ) {
				return true;
			} // stop on first true result
		}

		return false;
	}

	/**
	 * Rules to skip the tracking
	 *
	 * @since    1.2.6
	 * @version  2016-12-21
	 *
	 * @hook     boolean  statify__skip_tracking
	 * @see      https://github.com/pluginkollektiv/statify/wiki/Hooks#statify__skip_tracking
	 *
	 * @return   boolean  $skip_hook  TRUE if NO tracking is desired
	 */
	private static function _skip_tracking() {

		apply_filters_deprecated( 'statify_skip_tracking', array( '' ), '1.5.0', 'statify__skip_tracking' );
		/* Skip tracking via Hook */
		if ( ( $skip_hook = apply_filters( 'statify__skip_tracking', null ) ) !== null ) {
			return $skip_hook;
		}


		// Skip tracking via User Agent
		// @codingStandardsIgnoreStart The globals are checked.
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) 
		     || ! preg_match( '/(?:Windows|Macintosh|Linux|iPhone|iPad)/', $_SERVER['HTTP_USER_AGENT'] ) ) {
		// @codingStandardsIgnoreEnd
			return true;
		}

		/** Skip tracking via Referrer check and Conditional_Tags. */
		return ( self::check_referrer() || is_feed() || is_trackback() || is_robots()
		         || is_preview() || is_user_logged_in() || is_404() || is_search()
		);
	}

	/**
	 * Compare the referrer url to the blacklist data.
	 *
	 * @since  2016-12-21
	 *
	 * @return bool
	 */
	private static function check_referrer() {

		// @codingStandardsIgnoreStart The globals are checked.
		$referrer  = ( isset( $_SERVER['HTTP_REFERER'] ) ? wp_parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST ) : '' );
		// @codingStandardsIgnoreEnd

		$blacklist = self::get_blacklist_keys();
		if ( array_key_exists( $referrer, $blacklist ) ) {
			return true;
		}

		if ( in_array( $referrer, $blacklist, true ) ) {
			return true;
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

		return array_flip( explode( "\n", $blacklist ) );
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

		/* Skip by rules */
		if ( self::_skip_tracking() ) {
			return;
		}

		/* Load template */
		load_template(
			wp_normalize_path(
				sprintf(
					'%s/views/js_snippet.view.php',
					STATIFY_DIR
				)
			)
		);
	}
}
