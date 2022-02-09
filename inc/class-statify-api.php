<?php
/**
 * Statify: Statify_API class
 *
 * This file contains methods for REST API integration.
 *
 * @package Statify
 * @since   1.9
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * Statify REST API integration.
 *
 * @since 1.9
 */
class Statify_Api extends Statify {
	/**
	 * REST endpoints
	 *
	 * @var    string
	 */
	const REST_NAMESPACE   = 'statify/v1';
	const REST_ROUTE_TRACK = 'track';

	/**
	 * Initialize REST API routes.
	 *
	 * @return void
	 */
	public static function init() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE_TRACK,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'accept_json'         => true,
				'callback'            => array( __CLASS__, 'track_visit' ),
				'permission_callback' => '__return_true',
			)
		);

		add_filter( 'rest_authentication_errors', array( __CLASS__, 'check_authentication' ), 5 );
	}

	/**
	 * Filters REST API authentication errors.
	 *
	 * Override the default authentication check for the tracking endpoint.
	 * Requests are unauthenticated by default, if no nonce is provided. Verification can be disabled in the plugin
	 * configuration, and we still need the information, if tue current user is logged in.
	 * We don't make any decision for other routes.
	 *
	 * @param WP_Error|null|true $errors WP_Error if authentication error, null if authentication
	 *                                   method wasn't used, true if authentication succeeded.
	 */
	public static function check_authentication( $errors ) {
		$route = untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] );

		if ( '/' . self::REST_NAMESPACE . '/' . self::REST_ROUTE_TRACK === $route ) {
			// We disable verification for the tracking route.
			return true;
		}

		// Don't change behavior for other routes.
		return $errors;
	}

	/**
	 * Track the page view via API.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response The response.
	 */
	public static function track_visit( $request ) {
		// Only do something if snippet use is actually configured.
		if ( Statify::is_javascript_tracking_enabled() ) {
			// Nonce verification, if necessary. We do not rely on the WP REST default mechanisms.
			if ( Statify::TRACKING_METHOD_JAVASCRIPT_WITH_NONCE_CHECK === self::$_options['snippet'] ) {
				$nonce = $request->get_param( 'nonce' );
				if ( empty( $nonce ) || false === wp_verify_nonce( $nonce, 'statify_track' ) ) {
					return new WP_REST_Response( null, 403 );
				}
			}

			$referrer = $request->get_param( 'referrer' );
			if ( null !== $referrer ) {
				$referrer = filter_var( $referrer, FILTER_SANITIZE_URL );
			}
			$target   = $request->get_param( 'target' );
			if ( null !== $target ) {
				$target = filter_var( $target, FILTER_SANITIZE_URL );
			}

			Statify::track( $referrer, $target );
		}

		return new WP_REST_Response( null, 204 );
	}
}
