<?php
/**
 * Statify dashboard tests.
 *
 * @package Statify
 */

/**
 * Class Test_Dashboard.
 * Tests for dashboard integration.
 */
class Test_Dashboard extends WP_UnitTestCase {
	use Statify_Test_Support;

	/**
	 * Set up the test case.
	 */
	public function set_up() {
		parent::set_up();

		set_current_screen( 'dashboard' );

		// "Install" Statify, i.e. create tables and options.
		Statify_Install::init();

		if ( ! function_exists( 'wp_add_dashboard_widget' ) ) {
			global $widget_capture;
			$widget_capture = array();

			/**
			 * Capturing override for widget registration.
			 *
			 * @param string   $widget_id        Widget ID  (used in the 'id' attribute for the widget).
			 * @param string   $widget_name      Title of the widget.
			 * @param callable $callback         Function that fills the widget with the desired content.
			 *                                   The function should echo its output.
			 * @param callable $control_callback Optional. Function that outputs controls for the widget. Default null.
			 * @param array    $callback_args    Optional. Data that should be set as the $args property of the widget array
			 *                                   (which is the second parameter passed to your callback). Default null.
			 */
			function wp_add_dashboard_widget( $widget_id, $widget_name, $callback, $control_callback = null, $callback_args = null ) {
				global $widget_capture;
				$widget_capture['widget_id']        = $widget_id;
				$widget_capture['widget_name']      = $widget_name;
				$widget_capture['callback']         = $callback;
				$widget_capture['control_callback'] = $control_callback;
				$widget_capture['callback_args']    = $callback_args;
			}
		}
	}

	/**
	 * Test Statify Dashboard initialization.
	 */
	public function test_init() {
		global $widget_capture;

		// Anonymous users do not have the "edit_dashboard" capability, i.e. can't see the stats.
		wp_set_current_user( 0 );
		Statify_Dashboard::init();
		$this->assertFalse(
			has_action( 'admin_print_styles', array( Statify_Dashboard::class, 'add_style' ) ),
			'Styles unexpectedly added'
		);
		$this->assertFalse(
			has_action( 'admin_print_scripts', array( Statify_Dashboard::class, 'add_js' ) ),
			'Scripts unexpectedly added'
		);

		// The current user gets the "edit_dashboard" capability.
		wp_get_current_user()->add_cap( 'edit_dashboard' );
		wp_set_current_user( 1 );

		Statify_Dashboard::init();
		$this->assertCount( 5, $widget_capture, 'No widget registered' );
		$this->assertEquals( 'statify_dashboard', $widget_capture['widget_id'], 'Unexpected widget ID' );
		$this->assertEquals( 'Statify', $widget_capture['widget_name'], 'Unexpected widget name' );
		$this->assertEquals(
			array( Statify_Dashboard::class, 'print_frontview' ),
			$widget_capture['callback'],
			'Unexpected widget callback'
		);
		$this->assertEquals(
			array( Statify_Dashboard::class, 'print_backview' ),
			$widget_capture['control_callback'],
			'Unexpected control callback'
		);
		$this->assertNotFalse(
			has_action( 'admin_print_styles', array( Statify_Dashboard::class, 'add_style' ) ),
			'Styles not added'
		);
		$this->assertNotFalse(
			has_action( 'admin_print_scripts', array( Statify_Dashboard::class, 'add_js' ) ),
			'Scripts not added'
		);
	}

	/**
	 * Test evaluation of the statify__user_can_see_stats hook.
	 */
	public function test_user_can_see_stats_hook() {
		global $widget_capture;

		// Add a custom filter that captures the original result and overrides the response.
		$original_capture = null;
		$override         = true;
		add_filter(
			'statify__user_can_see_stats',
			function ( $original ) use ( &$original_capture, &$override ) {
				$original_capture = $original;

				return $override;
			}
		);

		// No Capability, but override to TRUE.
		wp_set_current_user( 0 );
		wp_get_current_user()->remove_all_caps();
		$widget_capture = array();
		$override       = true;

		Statify_Dashboard::init();
		$this->assertCount( 5, $widget_capture, 'Widget should not have been registered' );
		$this->assertFalse( $original_capture, 'Expected original result to be FALSE' );

		// With capability, but overridden to FALSE.
		$widget_capture = array();
		wp_get_current_user()->add_cap( 'edit_dashboard' );
		$override = false;

		Statify_Dashboard::init();
		$this->assertEmpty( $widget_capture, 'Widget should not have been registered' );
		$this->assertTrue( $original_capture, 'Expected original result to be TRUE' );
	}

	/**
	 * Test prepared stats data.
	 */
	public function test_get_stats() {
		// Initially the database is empty.
		$this->assertNull( $this->get_stats(), 'Expected NULL stats for empty database' );

		// Now insert data for the last 3 days.
		$date1 = new DateTime();
		$date2 = ( new DateTime() )->modify( '-1 days' );
		$date3 = ( new DateTime() )->modify( '-2 days' );

		$this->insert_test_data( $date1->format( 'Y-m-d' ), 'https://statify.pluginkollektiv.org/', '/', 3 );
		$this->insert_test_data( $date1->format( 'Y-m-d' ), 'https://statify.pluginkollektiv.org/', '/test/', 4 );
		$this->insert_test_data( $date1->format( 'Y-m-d' ), 'https://pluginkollektiv.org/', '', 1 );

		$this->insert_test_data( $date2->format( 'Y-m-d' ), 'https://pluginkollektiv.org/', '/', 1 );
		$this->insert_test_data( $date2->format( 'Y-m-d' ), 'https://statify.pluginkollektiv.org/documentation/', '/test/', 2 );
		$this->insert_test_data( $date2->format( 'Y-m-d' ), 'https://wordpress.org/plugins/statify/', '', 1 );

		$this->insert_test_data( $date3->format( 'Y-m-d' ), 'https://pluginkollektiv.org/', '', 2 );
		$this->insert_test_data( $date3->format( 'Y-m-d' ), '', '/', 1 );

		// Initialize with default configuration, all limits greater data dimension.
		Statify::init();
		$this->init_statify_widget( 14, 14, 3, false, false );
		$stats = $this->get_stats();

		$this->assertEquals( 3, count( $stats['visits'] ), 'Unexpected number of days with visits' );
		$this->assertEquals( $date3->format( 'Y-m-d' ), $stats['visits'][0]['date'], 'Unexpected date of tracking 2 days ago' );
		$this->assertEquals( 3, $stats['visits'][0]['count'], 'Unexpected number of visits 2 days ago' );
		$this->assertEquals( $date2->format( 'Y-m-d' ), $stats['visits'][1]['date'], 'Unexpected date of tracking yesterday' );
		$this->assertEquals( 4, $stats['visits'][1]['count'], 'Unexpected number of visits yesterday' );
		$this->assertEquals( $date1->format( 'Y-m-d' ), $stats['visits'][2]['date'], 'Unexpected date of tracking today' );
		$this->assertEquals( 8, $stats['visits'][2]['count'], 'Unexpected number of visits today' );

		$this->assertEquals( 3, count( $stats['target'] ), 'Unexpected number of top targets' );
		$this->assertEquals( '/test/', $stats['target'][0]['url'], 'Unexpected 1st target path' );
		$this->assertEquals( 6, $stats['target'][0]['count'], 'Unexpected 1st target count' );
		$this->assertEquals( '/', $stats['target'][1]['url'], 'Unexpected 2nd target path' );
		$this->assertEquals( 5, $stats['target'][1]['count'], 'Unexpected 2nd target count' );
		$this->assertEquals( '', $stats['target'][2]['url'], 'Unexpected 3rd target path' );
		$this->assertEquals( 4, $stats['target'][2]['count'], 'Unexpected 3rd target count' );

		$this->assertEquals( 3, count( $stats['referrer'] ), 'Unexpected number of referrers' );
		$this->assertEquals( 9, $stats['referrer'][0]['count'], 'Unexpected referrer URL' );
		/* Top referrer URL is "https://statify.pluginkollektiv.org/". As we aggregate by host, the reported URL however
		   depends on the DB server, so it might be ".../documentation/", too. Just check the prefix here. */
		$this->assertEquals( 'https://statify.pluginkollektiv.org/', substr( $stats['referrer'][0]['url'], 0, 36 ), 'Unexpected 1st referrer URL' );
		$this->assertEquals( 'statify.pluginkollektiv.org', $stats['referrer'][0]['host'], 'Unexpected 1st referrer hostname' );
		$this->assertEquals( 4, $stats['referrer'][1]['count'], 'Unexpected 1st referrer URL' );
		$this->assertEquals( 'https://pluginkollektiv.org/', $stats['referrer'][1]['url'], 'Unexpected 2nd referrer URL' );
		$this->assertEquals( 'pluginkollektiv.org', $stats['referrer'][1]['host'], 'Unexpected 3rd referrer hostname' );
		$this->assertEquals( 1, $stats['referrer'][2]['count'], 'Unexpected 1st referrer URL' );
		$this->assertEquals( 'https://wordpress.org/plugins/statify/', $stats['referrer'][2]['url'], 'Unexpected 3rd referrer URL' );
		$this->assertEquals( 'wordpress.org', $stats['referrer'][2]['host'], 'Unexpected 3rd referrer hostname' );

		$this->assertArrayNotHasKey( 'visit_totals', $stats, 'Totals should not be provided, if not configured' );

		// Top lists only for today.
		$this->init_statify_widget( 14, 14, 3, true, false );
		$stats2 = $this->get_stats();

		$this->assertEquals( $stats['visits'], $stats2['visits'], 'Visit counts should not be affected by "today" switch' );

		$this->assertEquals( 3, count( $stats['target'] ), 'Unexpected number of top targets' );
		$this->assertEquals( '/test/', $stats2['target'][0]['url'], 'Unexpected 1st target path' );
		$this->assertEquals( 4, $stats2['target'][0]['count'], 'Unexpected 1st target count' );
		$this->assertEquals( '/', $stats2['target'][1]['url'], 'Unexpected 2nd target path' );
		$this->assertEquals( 3, $stats2['target'][1]['count'], 'Unexpected 2nd target count' );
		$this->assertEquals( '', $stats2['target'][2]['url'], 'Unexpected 3rd target path' );
		$this->assertEquals( 1, $stats2['target'][2]['count'], 'Unexpected 3rd target count' );

		$this->assertEquals( 2, count( $stats2['referrer'] ), 'Unexpected number of referrers' );
		$this->assertEquals( 7, $stats2['referrer'][0]['count'], 'Unexpected referrer URL' );
		$this->assertEquals( 'https://statify.pluginkollektiv.org/', $stats2['referrer'][0]['url'], 'Unexpected 1st referrer URL' );
		$this->assertEquals( 'statify.pluginkollektiv.org', $stats2['referrer'][0]['host'], 'Unexpected 1st referrer hostname' );
		$this->assertEquals( 1, $stats2['referrer'][1]['count'], 'Unexpected 1st referrer URL' );
		$this->assertEquals( 'https://pluginkollektiv.org/', $stats2['referrer'][1]['url'], 'Unexpected 2nd referrer URL' );
		$this->assertEquals( 'pluginkollektiv.org', $stats2['referrer'][1]['host'], 'Unexpected 3rd referrer hostname' );

		$this->assertArrayNotHasKey( 'visit_totals', $stats2, 'Totals should not be provided, if not configured' );

		// Limited display range of 2 days with total numbers.
		$this->init_statify_widget( 14, 2, 3, false, true );
		$stats3 = $this->get_stats();

		$this->assertEquals(
			array_slice( $stats['visits'], 1 ),
			$stats3['visits'],
			'Stats for 2 days should be equal to the slice of complete data'
		);

		$this->assertEquals( 3, count( $stats3['target'] ), 'Unexpected number of top targets' );
		$this->assertEquals( '/test/', $stats3['target'][0]['url'], 'Unexpected 1st target path' );
		$this->assertEquals( 6, $stats3['target'][0]['count'], 'Unexpected 1st target count' );
		$this->assertEquals( '/', $stats3['target'][1]['url'], 'Unexpected 2nd target path' );
		$this->assertEquals( 4, $stats3['target'][1]['count'], 'Unexpected 2nd target count' );
		$this->assertEquals( '', $stats3['target'][2]['url'], 'Unexpected 3rd target path' );
		$this->assertEquals( 2, $stats3['target'][2]['count'], 'Unexpected 3rd target count' );

		$this->assertEquals( 3, count( $stats3['referrer'] ), 'Unexpected number of referrers' );
		$this->assertEquals( 9, $stats3['referrer'][0]['count'], 'Unexpected referrer URL' );
		/* Top referrer URL is "https://statify.pluginkollektiv.org/". As we aggregate by host, the reported URL however
		depends on the DB server, so it might be ".../documentation/", too. Just check the prefix here. */
		$this->assertEquals( 'https://statify.pluginkollektiv.org/', substr( $stats['referrer'][0]['url'], 0, 36 ), 'Unexpected 1st referrer URL' );
		$this->assertEquals( 'statify.pluginkollektiv.org', $stats3['referrer'][0]['host'], 'Unexpected 1st referrer hostname' );
		$this->assertEquals( 2, $stats3['referrer'][1]['count'], 'Unexpected 1st referrer URL' );
		$this->assertEquals( 'https://pluginkollektiv.org/', $stats3['referrer'][1]['url'], 'Unexpected 2nd referrer URL' );
		$this->assertEquals( 'pluginkollektiv.org', $stats3['referrer'][1]['host'], 'Unexpected 3rd referrer hostname' );
		$this->assertEquals( 1, $stats3['referrer'][2]['count'], 'Unexpected 1st referrer URL' );
		$this->assertEquals( 'https://wordpress.org/plugins/statify/', $stats3['referrer'][2]['url'], 'Unexpected 3rd referrer URL' );
		$this->assertEquals( 'wordpress.org', $stats3['referrer'][2]['host'], 'Unexpected 3rd referrer hostname' );

		$this->assertArrayHasKey( 'visit_totals', $stats3, 'Totals should be provided, if configured' );
		$this->assertEquals( 8, $stats3['visit_totals']['today'], 'Unexpected total for today' );
		$this->assertEquals( 15, $stats3['visit_totals']['since_beginning']['count'], 'Unexpected total since beginning' );
		$this->assertEquals( $date3->format( 'Y-m-d' ), $stats3['visit_totals']['since_beginning']['date'], 'Unexpected first date' );

		// Finally we add another entry in the database, but utilize the transient cache (4min should be enough for the test case).
		$this->insert_test_data( $date1->format( 'Y-m-d' ), 'https://example.com/', '/example/', 1 );
		$stats4 = Statify_Dashboard::get_stats();
		$this->assertEquals( $stats3, $stats4, 'Stats expected to be equal, is the transient cache active?' );

		// Add more data and force reload. We now should see that fallback ordering by URL works.
		$this->insert_test_data( $date1->format( 'Y-m-d' ), 'https://example.com/', '/', 1 );
		$this->insert_test_data( $date1->format( 'Y-m-d' ), 'https://example.net/', '/', 1 );
		$stats5 = Statify_Dashboard::get_stats( true );
		$this->assertEquals( 18, $stats5['visit_totals']['since_beginning']['count'], 'Unexpected total since beginning' );
		$this->assertEquals( 2, $stats5['referrer'][1]['count'], 'Unexpected 2nd referrer count' );
		$this->assertEquals( 'example.com', $stats5['referrer'][1]['host'], 'Unexpected 2nd referrer hostname' );
		$this->assertEquals( 2, $stats5['referrer'][2]['count'], 'Unexpected 3rd referrer count' );
		$this->assertEquals( 'pluginkollektiv.org', $stats5['referrer'][2]['host'], 'Unexpected 3rd referrer hostname' );
		$this->assertEquals( 6, $stats5['target'][0]['count'], 'Unexpected 1st target count' );
		$this->assertEquals( '/', $stats5['target'][0]['url'], 'Unexpected 1st target url' );
		$this->assertEquals( 6, $stats5['target'][1]['count'], 'Unexpected 2nd target count' );
		$this->assertEquals( '/test/', $stats5['target'][1]['url'], 'Unexpected 2nd target url' );
	}
}
