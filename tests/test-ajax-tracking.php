<?php
/**
 * Statify AJAX tests.
 *
 * @package Statify
 */

/**
 * Class Test_Ajax_Tracking.
 * Tests for JavaScript based tracking using WP AJAX.
 */
class Test_Ajax_Tracking extends WP_Ajax_UnitTestCase {

	/**
	 * Set up the test case.
	 */
	public function setUp() {
		parent::setUp();

		// "Install" Statify, i.e. create tables and options.
		Statify_Install::init();
	}

	/**
	 * Test case for AJAX tracking.
	 */
	public function test_track_ajax() {
		global $_POST;

		// Initialize a valid Statify tracking request.
		$_POST['_wpnonce']         = wp_create_nonce( 'statify_track' );
		$_POST['statify_target']   = '/';
		$_POST['statify_referrer'] = 'https://statify.pluginkollektiv.org/';

		// Initialize Statify with default configuration: no JS tracking, no logged-in users.
		$this->init_statify_ajax( false, false );

		try {
			$this->_handleAjax( 'nopriv_statify_track' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected exception.
		}

		$this->assertFalse( isset( $e ), 'AJAX should not fail for valid request without JS enabled' );

		// Get the stats and assert emptiness.
		$stats = Statify_Dashboard::get_stats();
		$this->assertNull( $stats, 'Stats should be empty, i.e. visit should not have been tracked' );

		// Now enable JS tracking.
		$this->init_statify_ajax( true, false );

		try {
			$_POST['_wpnonce'] = wp_create_nonce( 'statify_track' );
			$this->_handleAjax( 'nopriv_statify_track' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected exception.
		}
		$this->assertTrue( isset( $e ), 'AJAX should have stopped' );
		$this->assertEquals( 0, $e->getCode(), 'Unexpected exit code after AJAX processing' );

		delete_transient( 'statify_data' );
		$stats = Statify_Dashboard::get_stats();
		$this->assertNotNull( $stats, 'Stats should be filled after tracking' );

		$this->assertEquals( 1, count( $stats['visits'] ), 'Unexpected number of days with visits' );
		$this->assertEquals( ( new DateTime() )->format( 'Y-m-d' ), $stats['visits'][0]['date'], 'Unexpected date of tracking' );
		$this->assertEquals( 1, $stats['visits'][0]['count'], 'Unexpected visit count' );

		$this->assertEquals( 1, count( $stats['target'] ), 'Unexpected number of targets' );
		$this->assertEquals( '', $stats['target'][0]['url'], 'Unexpected target URL' );
		$this->assertEquals( 1, $stats['target'][0]['count'], 'Unexpected target count' );

		$this->assertEquals( 1, count( $stats['referrer'] ), 'Unexpected number of referrers' );
		$this->assertEquals( 'https://statify.pluginkollektiv.org/', $stats['referrer'][0]['url'], 'Unexpected referrer URL' );
		$this->assertEquals( 'statify.pluginkollektiv.org', $stats['referrer'][0]['host'], 'Unexpected referrer hostname' );
		$this->assertEquals( 1, $stats['referrer'][0]['count'], 'Unexpected referrer count' );

		unset( $e );

		// Now we are logged in.
		wp_set_current_user( 1 );

		try {
			$_POST['_wpnonce'] = wp_create_nonce( 'statify_track' );
			$this->_handleAjax( 'statify_track' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected exception.
		}
		$this->assertTrue( isset( $e ), 'AJAX should have stopped' );
		$this->assertEquals( 0, $e->getCode(), 'Unexpected exit code after AJAX processing' );

		delete_transient( 'statify_data' );
		$stats = Statify_Dashboard::get_stats();
		$this->assertNotNull( $stats, 'Stats should be filled after tracking' );

		// Numbers should not have been increased.
		$this->assertEquals( 1, count( $stats['visits'] ), 'Unexpected number of days with visits' );
		$this->assertEquals( 1, $stats['visits'][0]['count'], 'Unexpected visit count' );
		$this->assertEquals( 1, count( $stats['target'] ), 'Unexpected number of targets' );
		$this->assertEquals( 1, $stats['target'][0]['count'], 'Unexpected target count' );
		$this->assertEquals( 1, count( $stats['referrer'] ), 'Unexpected number of referrers' );
		$this->assertEquals( 1, $stats['referrer'][0]['count'], 'Unexpected referrer count' );

		unset( $e );

		// Now we allow tracking for logged-in users.
		$this->init_statify_ajax( true, true );

		try {
			$_POST['_wpnonce'] = wp_create_nonce( 'statify_track' );
			$this->_handleAjax( 'statify_track' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected exception.
		}
		$this->assertTrue( isset( $e ), 'AJAX should have stopped' );
		$this->assertEquals( 0, $e->getCode(), 'Unexpected exit code after AJAX processing' );

		delete_transient( 'statify_data' );
		$stats = Statify_Dashboard::get_stats();
		$this->assertNotNull( $stats, 'Stats should be filled after tracking' );

		// Numbers should not have been increased.
		$this->assertEquals( 1, count( $stats['visits'] ), 'Unexpected number of days with visits' );
		$this->assertEquals( 2, $stats['visits'][0]['count'], 'Unexpected visit count' );
		$this->assertEquals( 1, count( $stats['target'] ), 'Unexpected number of targets' );
		$this->assertEquals( 2, $stats['target'][0]['count'], 'Unexpected target count' );
		$this->assertEquals( 1, count( $stats['referrer'] ), 'Unexpected number of referrers' );
		$this->assertEquals( 2, $stats['referrer'][0]['count'], 'Unexpected referrer count' );
	}

	/**
	 * Initialize Statify for AJAX call.
	 *
	 * @param bool $use_snippet     Configure tracking via JavaScript.
	 * @param bool $track_logged_in Configure tracking for logged-in users.
	 */
	private function init_statify_ajax( $use_snippet, $track_logged_in ) {
		$options = get_option( 'statify' );

		$options['snippet']           = $use_snippet ? 1 : 0;
		$options['skip']['logged_in'] = $track_logged_in ? 0 : 1;

		update_option( 'statify', $options );

		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}
		Statify::init();
	}
}
