<?php
/**
 * Statify API tests.
 *
 * @package Statify
 */

/**
 * Class Test_Api_Tracking.
 * Tests for JavaScript based tracking using WP API.
 */
class Test_Api_Tracking extends WP_UnitTestCase {
	use Statify_Test_Support;

	/**
	 * Set up the test case.
	 */
	public function set_up() {
		parent::set_up();

		// "Install" Statify, i.e. create tables and options.
		Statify_Install::init();
	}

	/**
	 * Test route registration.
	 *
	 * @return void
	 */
	public function test_register_route() {
		$wp_rest_server = rest_get_server();

		// Initialize Statify with default configuration: no JS tracking.
		$this->init_statify_tracking();

		do_action( 'rest_api_init' );
		$this->assertArrayNotHasKey(
			'/' . Statify_Api::REST_NAMESPACE . '/' . Statify_Api::REST_ROUTE_TRACK,
			$wp_rest_server->get_routes(),
			'REST route should not have been registered with JS disabled'
		);

		// Enable JS tracking.
		$this->init_statify_tracking( Statify::TRACKING_METHOD_JAVASCRIPT_WITH_NONCE_CHECK );
		do_action( 'rest_api_init' );
		$this->assertArrayHasKey(
			'/' . Statify_Api::REST_NAMESPACE . '/' . Statify_Api::REST_ROUTE_TRACK,
			$wp_rest_server->get_routes(),
			'REST route should have been registered with JS enabled'
		);
	}

	/**
	 * Test case for API tracking.
	 */
	public function test_track_api() {
		$wp_rest_server = rest_get_server();

		// Initialize a valid Statify tracking request.
		$request = new WP_REST_Request(
			'POST',
			'/' . Statify_Api::REST_NAMESPACE . '/' . Statify_Api::REST_ROUTE_TRACK
		);
		$request->set_header( 'Content-Type', 'appliction/json;charset=utf-8' );
		$request->set_param( 'target', '/' );
		$request->set_param( 'referrer', 'https://statify.pluginkollektiv.org/' );
		$request->set_param( 'nonce', wp_create_nonce( 'statify_track' ) );

		// Initialize Statify with JS tracking enabled.
		$this->init_statify_tracking( Statify::TRACKING_METHOD_JAVASCRIPT_WITH_NONCE_CHECK );
		do_action( 'rest_api_init' );

		$response = $wp_rest_server->dispatch( $request );

		$this->assertEquals( 204, $response->get_status(), 'API request should return 204 with valid request' );

		// Get the stats and assert emptiness.
		$stats = $this->get_stats();
		$this->assertNotNull( $stats, 'Stats should not be empty' );
		$this->assertEquals( 1, count( $stats['referrer'] ), 'Unexpected number of referrers' );
		$this->assertEquals( 'https://statify.pluginkollektiv.org/', $stats['referrer'][0]['url'], 'Unexpected referrer URL' );
		$this->assertEquals( 'statify.pluginkollektiv.org', $stats['referrer'][0]['host'], 'Unexpected referrer hostname' );
		$this->assertEquals( 1, $stats['referrer'][0]['count'], 'Unexpected referrer count' );
		$this->assertEquals( 1, count( $stats['visits'] ), 'Unexpected number of days with visits' );
		$this->assertEquals( ( new DateTime() )->format( 'Y-m-d' ), $stats['visits'][0]['date'], 'Unexpected date of tracking' );
		$this->assertEquals( 1, $stats['visits'][0]['count'], 'Unexpected visit count' );
		$this->assertEquals( 1, count( $stats['target'] ), 'Unexpected number of targets' );
		$this->assertEquals( '', $stats['target'][0]['url'], 'Unexpected target URL' );
		$this->assertEquals( 1, $stats['target'][0]['count'], 'Unexpected target count' );

		// Simulate outdated, i.e. invalid nonce.
		$request->set_param( 'nonce', $request->get_param( 'nonce' ) . '-old' );
		$response = $wp_rest_server->dispatch( $request );
		$this->assertEquals( 403, $response->get_status(), 'API request should return 403 with invalid nonce' );
		$stats = $this->get_stats();
		$this->assertEquals( 1, $stats['referrer'][0]['count'], 'Visit was tracked with invalid nonce' );

		// Without nonce.
		$request->set_param( 'nonce', null );
		$response = $wp_rest_server->dispatch( $request );
		$this->assertEquals( 403, $response->get_status(), 'API request should return 403 without nonce' );
		$stats = $this->get_stats();
		$this->assertEquals( 1, $stats['referrer'][0]['count'], 'Visit was tracked without nonce' );

		// Numbers should not have been increased.
		$stats = $this->get_stats();
		$this->assertEquals( 1, count( $stats['visits'] ), 'Number of days with visits should not be higher after AJAX request failed' );
		$this->assertEquals( 1, $stats['visits'][0]['count'], 'Visit count should not be higher after AJAX request failed' );

		// Now disable JS tracking.
		$this->init_statify_tracking( Statify::TRACKING_METHOD_DEFAULT );

		// The REST routes for testing are not reset, so the endpoint is evaluated and does not return 404 here.
		$response = $wp_rest_server->dispatch( $request );
		$this->assertEquals( 204, $response->get_status(), 'API request should return 204 with JS disabled (route still enabled)' );
		$stats = $this->get_stats();
		$this->assertEquals( 1, count( $stats['referrer'] ), 'Unexpected number of referrers' );
		$this->assertEquals( 1, $stats['referrer'][0]['count'], 'Unexpected referrer count' );

		// Re-enable JS without nonce.
		$this->init_statify_tracking( Statify::TRACKING_METHOD_JAVASCRIPT_WITHOUT_NONCE_CHECK );

		$response = $wp_rest_server->dispatch( $request );
		$this->assertEquals( 204, $response->get_status(), 'API request should return 204 with JS enabled' );
		$stats = $this->get_stats();
		$this->assertEquals( 1, count( $stats['referrer'] ), 'Unexpected number of referrers with nonce verification disabled' );
		$this->assertEquals( 2, $stats['referrer'][0]['count'], 'Referrer count should have increased with nonce verification disabled' );

		// Now we are logged in.
		wp_set_current_user( 1 );
		$response = $wp_rest_server->dispatch( $request );
		$this->assertEquals( 204, $response->get_status(), 'API request should return 204 with JS enabled and logged in' );
		$stats = $this->get_stats();
		$this->assertEquals( 1, count( $stats['referrer'] ), 'Unexpected number of referrers when logged in' );
		$this->assertEquals( 2, $stats['referrer'][0]['count'], 'Referrer count should not have increased when logged in' );

		/*
		 * Allow tracking for logged-in users.
		 * This also check the overruled check, i.e. the login status is not reset without nonce.
		 */
		$this->init_statify_tracking( Statify::TRACKING_METHOD_JAVASCRIPT_WITHOUT_NONCE_CHECK, Statify::SKIP_USERS_NONE );
		$response = $wp_rest_server->dispatch( $request );
		$this->assertEquals( 204, $response->get_status(), 'API request should return 204 with JS enabled and logged in' );
		$stats = $this->get_stats();
		$this->assertEquals( 1, count( $stats['referrer'] ), 'Unexpected number of referrers when logged in (enabled)' );
		$this->assertEquals( 3, $stats['referrer'][0]['count'], 'Referrer count should have increased when logged in (enabled)' );

		// Exclude administrators.
		$this->init_statify_tracking( Statify::TRACKING_METHOD_JAVASCRIPT_WITHOUT_NONCE_CHECK, Statify::SKIP_USERS_ADMIN );
		$response = $wp_rest_server->dispatch( $request );
		$this->assertEquals( 204, $response->get_status(), 'API request should return 204 with JS enabled and logged in' );
		$stats = $this->get_stats();
		$this->assertEquals( 3, $stats['visits'][0]['count'], 'Administrator user should not be tracked' );

		// Switch to regular user account.
		$author = $this->factory()->user->create( array( 'role' => 'author' ) );
		wp_set_current_user( $author );
		$response = $wp_rest_server->dispatch( $request );
		$this->assertEquals( 204, $response->get_status(), 'API request should return 204 with JS enabled and logged in' );
		$stats = $this->get_stats();
		$this->assertEquals( 4, $stats['visits'][0]['count'], 'Regular user should be tracked' );
	}
}
