<?php


/* Quit */
defined('ABSPATH') OR exit;


/**
* Statify_Frontend
*
* @since 1.4.0
*/

class Statify_Frontend extends Statify
{


	/**
	* Track the page view
	*
	* @since   0.1.0
	* @change  1.4.1
	*/

	public static function track_visit()
	{
		/* Init vars */
		$use_snippet = self::$_options['snippet'];
		$is_snippet = $use_snippet && get_query_var('statify_target');

		/* Skip tracking */
		if ( self::_skip_tracking() ) {
			return self::_jump_out($is_snippet);
		}

		/* Set target & referrer */
		if ( $is_snippet ) {
			$target = urldecode( get_query_var('statify_target') );
			$referrer = urldecode( get_query_var('statify_referrer') );
		} else if ( ! $use_snippet) {
			$target = ( filter_has_var(INPUT_SERVER, 'REQUEST_URI') ? wp_unslash($_SERVER['REQUEST_URI']) : '/' );
			$referrer = ( filter_has_var(INPUT_SERVER, 'HTTP_REFERER') ? wp_unslash($_SERVER['HTTP_REFERER']) : '' );
		} else {
			return;
		}

		/* Invalid target? */
		if ( empty($target) OR ! wp_validate_redirect($target, false) ) {
			return self::_jump_out($is_snippet);
		}

		/* Global vars */
		global $wpdb, $wp_rewrite;

		/* Init rows */
		$data = array(
			'created'  => '',
			'referrer' => '',
			'target'   => ''
		);

		/* Set request timestamp */
		$data['created'] = strftime(
			'%Y-%m-%d',
			current_time('timestamp')
		);

		/* Sanitize referrer url */
		if ( ! empty($referrer) && strpos( $referrer, home_url() ) === false ) {
			$data['referrer'] = esc_url_raw( $referrer, array('http', 'https') );
		}

		/* Relative target url */
		$data['target'] = home_url($target, 'relative');

		/* Trim target url */
		if ( $wp_rewrite->permalink_structure ) {
			$data['target'] = parse_url($data['target'], PHP_URL_PATH);
		}

		/* Sanitize target url */
		$data['target'] = esc_url_raw($data['target']);

		/* Insert */
		$wpdb->insert(
			$wpdb->statify,
			$data
		);

		/* Jump! */
		return self::_jump_out($is_snippet);
	}


	/**
	* Rules to skip the tracking
	*
	* @since   1.2.6
	* @change  1.4.1
	*
	* @hook    boolean  statify_skip_tracking (https://gist.github.com/sergejmueller/7612368)
	*
	* @return  boolean  $skip_hook  TRUE if NO tracking is desired
	*/

	private static function _skip_tracking() {
        /* Skip tracking via Hook */
		if ( ( $skip_hook = apply_filters('statify_skip_tracking', NULL) ) !== NULL ) {
			return $skip_hook;
		}

        /* Skip tracking via User Agent */
		if ( ! filter_has_var(INPUT_SERVER, 'HTTP_USER_AGENT') OR ! preg_match('/(?:Windows|Macintosh|Linux|iPhone|iPad)/', $_SERVER['HTTP_USER_AGENT']) ) {
			return true;
		}

        /* Skip tracking via Conditional_Tags */
		return ( is_feed() OR is_trackback() OR is_robots() OR is_preview() OR is_user_logged_in() OR is_404() OR is_search() );
	}


	/**
	* Send JavaScript headers or return false
	*
	* @since   1.1.0
	* @change  1.3.1
	*
	* @param   boolean  $is_snippet  Snippet type
	* @return  mixed                 Exit or return depending on snippet type
	*/

	private static function _jump_out($is_snippet) {
		if ( $is_snippet ) {
			nocache_headers();
			header('Content-type: text/javascript', true, 204);
			exit;
		}

		return false;
	}


	/**
	* Declare GET variables for further use
	*
	* @since   1.1.0
	* @change  1.3.1
	*
	* @param   array  $vars  Input with existing variables
	* @return  array  $vars  Output with plugin variables
	*/

	public static function query_vars($vars) {
		$vars[] = 'statify_referrer';
		$vars[] = 'statify_target';

		return $vars;
	}


	/**
	* Print JavaScript snippet
	*
	* @since   1.1.0
	* @change  1.4.1
	*/

	public static function wp_footer()
	{
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