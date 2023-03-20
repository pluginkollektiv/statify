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

    public static function get_tracking_data() {
        return self::$tracking_data;
    }

    public static function get_tracking_meta() {
        return self::$tracking_meta;
    }

	/**
	 * Track the page view
	 *
	 * @since    0.1.0
	 * @since    1.7.0 $is_snippet parameter added.
	 * @version  1.7.0
	 *
	 * @param boolean $is_snippet [deprecated] Is tracking triggered via JS (default: false).
	 *
	 * @return boolean
	 */
	public static function track_visit( $is_snippet = false ) {
		if ( self::is_javascript_tracking_enabled() ) {
			return false;
		}

		Statify::track( self::$tracking_data, self::$tracking_meta );
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
		if ( self::is_internal() ) {
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
		$script_data = array(
			'url' => esc_url_raw( rest_url( Statify_Api::REST_NAMESPACE . '/' . Statify_Api::REST_ROUTE_TRACK ) ),
            'tracking_data' => self::$tracking_data,
            'tracking_meta' => wp_list_pluck( self::$tracking_meta, 'meta_value', 'meta_key' ),
		);
		if ( Statify::TRACKING_METHOD_JAVASCRIPT_WITH_NONCE_CHECK === self::$_options['snippet'] ) {
			$script_data['nonce'] = wp_create_nonce( 'statify_track' );
		}
		wp_localize_script( 'statify-js', 'statifyAjax', $script_data );
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
		$cfg = array(
			'requests'       => array(
				'pageview' => rest_url( Statify_Api::REST_NAMESPACE . '/' . Statify_Api::REST_ROUTE_TRACK ),
			),
			'extraUrlParams' => array(
				'referrer' => '${documentReferrer}',
				'target'   => '${canonicalPath}amp/',
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

		if ( Statify::TRACKING_METHOD_JAVASCRIPT_WITH_NONCE_CHECK === self::$_options['snippet'] ) {
			$cfg['extraUrlParams']['nonce'] = wp_create_nonce( 'statify_track' );
		}

		return $cfg;
	}
}
