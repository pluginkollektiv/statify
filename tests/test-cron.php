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
	 * Test Statify Cron Job execution.
	 *
	 * @runInSeparateProcess Must not preserve global constant.
	 * @preserveGlobalState disabled
	 */
	public function test_cronjob() {
		// Initialize normal cycle, configure storage period of 3 days.
		$this->init_statify_widget( 3 );
		$this->assertNotFalse(
			has_action( 'statify_cleanup', array( 'Statify_Cron', 'cleanup_data' ) ),
			'Statify cleanup cron job should be registered in normal cycle (always)'
		);

		// Initialize cron cycle.
		define( 'DOING_CRON', true );
		Statify::init();
		$this->assertNotFalse(
			has_action( 'statify_cleanup', array( 'Statify_Cron', 'cleanup_data' ) ),
			'Statify cleanup cron job was not registered'
		);

		// Insert some test data, 2 entries over the last 5 days (including today).
		$date  = new DateTime();
		$dates = array();
		for ( $i = 0; $i < 5; $i ++ ) {
			$dates[] = $date->format( 'Y-m-d' );
			$this->insert_test_data( $date->format( 'Y-m-d' ), '', '', 2 );
			$date->modify( '-1 days' );
		}

		// Make sure our test data is correct.
		$stats = $this->get_stats();
		$this->assertEquals( 5, count( $stats['visits'] ), 'Unexpected number of days with visits' );
		foreach ( $stats['visits'] as $v ) {
			$this->assertContains( $v['date'], $dates, 'Unexpected creation date in stats' );
			$this->assertEquals( 2, $v['count'], 'Unexpected visit count' );
		}

		// Run the cron job.
		Statify_Cron::cleanup_data();

		// Verify that 2 days have been deleted.
		$stats = $this->get_stats();
		$this->assertEquals( 3, count( $stats['visits'] ), 'Unexpected number of days with visits after cleanup' );
		$remaining_dates = array_slice( $dates, 0, 3 );
		foreach ( $stats['visits'] as $v ) {
			$this->assertContains( $v['date'], $remaining_dates, 'Unexpected remaining date in stats' );
			$this->assertEquals( 2, $v['count'], 'Unexpected visit count' );
		}
	}
}
