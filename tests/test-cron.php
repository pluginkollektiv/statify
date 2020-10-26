<?php
/**
 * Statify cron tests.
 *
 * @package Statify
 */

/**
 * Class Test_Cron.
 * Tests for cron integration.
 */
class Test_Cron extends WP_UnitTestCase {
	/**
	 * Set up the test case.
	 */
	public function setUp() {
		parent::setUp();

		// "Install" Statify, i.e. create tables and options.
		Statify_Install::init();
	}

	/**
	 * Test Statify Cron Job execution.
	 *
	 * @runInSeparateProcess Must not preserve global constant.
	 */
	public function test_cronjob() {
		// Initialize normal cycle, configure storage period of 3 days.
		$options         = get_option( 'statify' );
		$options['days'] = 3;
		update_option( 'statify', $options );
		Statify::init();
		$this->assertFalse(
			has_action( 'statify_cleanup', array( 'Statify_Cron', 'cleanup_data' ) ),
			'Statify cleanup cron job should not be registered in normal cycle'
		);

		// Initialize cron cycle.
		define( 'DOING_CRON', true );
		Statify::init();
		$this->assertNotFalse(
			has_action( 'statify_cleanup', array( 'Statify_Cron', 'cleanup_data' ) ),
			'Statify cleanup cron job was not registered'
		);

		// Insert some test data, 2 entries over the last 5 days (including today).
		global $wpdb;

		$date  = new DateTime();
		$dates = array();
		$data  = array(
			'created'  => '',
			'referrer' => '',
			'target'   => '',
		);
		for ( $i = 0; $i < 5; $i ++ ) {
			$data['created'] = $date->format( 'Y-m-d' );
			$dates[]         = $date->format( 'Y-m-d' );
			$wpdb->insert( $wpdb->statify, $data );
			$wpdb->insert( $wpdb->statify, $data );
			$date->modify( '-1 days' );
		}

		// Make sure our test data is correct.
		delete_transient( 'statify_data' );
		$stats = Statify_Dashboard::get_stats();
		$this->assertEquals( 5, count( $stats['visits'] ), 'Unexpected number of days with visits' );
		foreach ( $stats['visits'] as $v ) {
			$this->assertContains( $v['date'], $dates, 'Unexpected creation date in stats' );
			$this->assertEquals( 2, $v['count'], 'Unexpected visit count' );
		}

		// Run the cron job.
		Statify_Cron::cleanup_data();

		// Verify that 2 days have been deleted.
		delete_transient( 'statify_data' );
		$stats = Statify_Dashboard::get_stats();
		$this->assertEquals( 3, count( $stats['visits'] ), 'Unexpected number of days with visits after cleanup' );
		$remaining_dates = array_slice( $dates, 0, 3 );
		foreach ( $stats['visits'] as $v ) {
			$this->assertContains( $v['date'], $remaining_dates, 'Unexpected remaining date in stats' );
			$this->assertEquals( 2, $v['count'], 'Unexpected visit count' );
		}
	}
}
