<?php


/* Quit */
defined('ABSPATH') OR exit;


/**
* Statify_Frontend
*
* @since 1.3.1
*/

class Statify_Frontend extends Statify
{


	/**
	* Speicherung des Aufrufes in der DB
	*
	* @since   0.1.0
	* @change  1.3.1
	*/

	public static function track_visit()
	{
		/* JS-Snippet? */
		$use_snippet = self::$_options['snippet'];
		$is_snippet = $use_snippet && get_query_var('statify_target');

		/* Snippet? */
		if ( $is_snippet ) {
			$target = urldecode( get_query_var('statify_target') );
			$referrer = urldecode( get_query_var('statify_referrer') );
		} else if ( ! $use_snippet) {
			$target = ( empty($_SERVER['REQUEST_URI']) ? '/' : $_SERVER['REQUEST_URI'] );
			$referrer = ( empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'] );
		} else {
			return;
		}

		/* Kein Ziel? */
		if ( empty($target) OR ! filter_var( home_url($target), FILTER_VALIDATE_URL ) ) {
			return self::_jump_out($is_snippet);
		}

		/* Bot? */
		if ( empty($_SERVER['HTTP_USER_AGENT']) OR ! preg_match('/(?:Windows|Macintosh|Linux|iPhone|iPad)/', $_SERVER['HTTP_USER_AGENT']) ) {
			return self::_jump_out($is_snippet);
		}

		/* Filter */
		if ( self::_skip_tracking() ) {
			return self::_jump_out($is_snippet);
		}

		/* Global */
		global $wpdb, $wp_rewrite;

		/* Init */
		$data = array(
			'created'  => '',
			'referrer' => '',
			'target'   => ''
		);

		/* Timestamp */
		$data['created'] = strftime(
			'%Y-%m-%d',
			current_time('timestamp')
		);

		/* Referrer */
		if ( ! empty($referrer) && strpos( $referrer, home_url() ) === false ) {
			$data['referrer'] = esc_url_raw( $referrer, array('http', 'https') );
		}

		/* Set request target */
		$data['target'] = home_url($target, 'relative');

		/* Get url path only */
		if ( $wp_rewrite->permalink_structure && ! is_search() ) {
			$data['target'] = parse_url($data['target'], PHP_URL_PATH);
		}

		/* Sanitize url */
		$data['target'] = filter_var($data['target'], FILTER_SANITIZE_URL);

		/* Insert */
		$wpdb->insert(
			$wpdb->statify,
			$data
		);

		/* Beenden */
		return self::_jump_out($is_snippet);
	}


	/**
	* Steuerung des Tracking-Mechanismus
	*
	* @since   1.2.6
	* @change  1.3.1
	*
	* @hook    boolean  statify_skip_tracking
	*
	* @return  boolean  $skip_hook  TRUE, wenn KEIN Tracking des Seitenaufrufes erfolgen soll
	*/

	private static function _skip_tracking() {
		if ( ( $skip_hook = apply_filters('statify_skip_tracking', NULL) ) !== NULL ) {
			return $skip_hook;
		}

		return ( is_feed() OR is_trackback() OR is_robots() OR is_preview() OR is_user_logged_in() OR is_404() );
	}


	/**
	* JavaScript-Header oder return
	*
	* @since   1.1.0
	* @change  1.3.1
	*
	* @param   boolean  $is_snippet  JavaScript-Snippte als Aufruf?
	* @return  mixed                 Exit oder return je nach Snippet
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
	* Deklariert GET-Variablen für die Weiternutzung
	*
	* @since   1.1.0
	* @change  1.3.1
	*
	* @param   array  $vars  Array mit existierenden Variablen
	* @return  array  $vars  Array mit Plugin-Variablen
	*/

	public static function query_vars($vars) {
		$vars[] = 'statify_referrer';
		$vars[] = 'statify_target';

		return $vars;
	}


	/**
	* Ausgabe des JS-Snippets
	*
	* @since   1.1.0
	* @change  1.3.1
	*/

	public static function wp_footer()
	{
		/* Skip by option */
		if ( ! self::$_options['snippet'] ) {
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