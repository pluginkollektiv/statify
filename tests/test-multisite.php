<?php
/**
 * Statify multisite tests.
 *
 * @package Statify
 */

/**
 * Class Test_Multisite.
 *
 * @group ms-required
 */
class Test_Multisite extends WP_UnitTestCase {

	/**
	 * Setup tests and mark skipped if not multisite.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only.' );
		}
	}

	/**
	 * Test that init_site() installs into the NEW site only (regression test for wrong site ID).
	 */
	public function test_init_site_uses_new_site_id() {
		// Prevent the real hook from doing the work, so we test init_site() isolated.
		remove_action( 'wp_initialize_site', array( 'Statify_Install', 'init_site' ) );

		$original_blog_id = get_current_blog_id();
		$new_blog_id      = self::factory()->blog->create();
		$this->assertNotSame( $original_blog_id, $new_blog_id, 'Precondition: IDs must differ' );

		$this->assertFalse( $this->table_exists( $new_blog_id ), 'Precondition: no table yet' );
		$this->assertFalse( $this->cron_scheduled_on( $new_blog_id ), 'Precondition: no cron yet' );

		Statify_Install::init_site( get_site( $new_blog_id ) );

		$this->assertTrue( $this->table_exists( $new_blog_id ), 'Table created for the new site' );
		$this->assertNotFalse( $this->cron_scheduled_on( $new_blog_id ), 'Cron scheduled on the new site' );
		$this->assertSame( $original_blog_id, get_current_blog_id(), 'Current blog restored' );
	}

	/**
	 * The wp_initialize_site hook wiring works end to end.
	 */
	public function test_new_site_is_set_up_via_hook() {
		add_action( 'wp_initialize_site', array( 'Statify_Install', 'init_site' ) );

		$blog_id = self::factory()->blog->create();

		$this->assertTrue( $this->table_exists( $blog_id ) );
		$this->assertNotFalse( $this->cron_scheduled_on( $blog_id ) );
	}

	/**
	 * Network-wide activation covers every site and restores the original blog.
	 */
	public function test_network_activation_sets_up_all_sites() {
		remove_action( 'wp_initialize_site', array( 'Statify_Install', 'init_site' ) );

		$original_blog_id = get_current_blog_id();
		$blog_ids         = self::factory()->blog->create_many( 2 );

		Statify_Install::init( true );

		foreach ( $blog_ids as $blog_id ) {
			$this->assertTrue( $this->table_exists( $blog_id ), "Table for blog $blog_id" );
			$this->assertNotFalse( $this->cron_scheduled_on( $blog_id ), "Cron for blog $blog_id" );
		}

		$this->assertSame( $original_blog_id, get_current_blog_id(), 'Current blog restored' );
	}

	/**
	 * Check for the existence of the "statify" table on a specific site.
	 *
	 * @param int $blog_id Blog ID.
	 *
	 * @return bool True if table exists.
	 */
	private function table_exists( int $blog_id ): bool {
		global $wpdb;
		$table    = $wpdb->get_blog_prefix( $blog_id ) . 'statify';
		$suppress = $wpdb->suppress_errors( true );
		$result   = $wpdb->query( "SELECT 1 FROM `$table` LIMIT 1" ); // phpcs:ignore WordPress.DB
		$wpdb->suppress_errors( $suppress );

		return false !== $result;
	}

	/**
	 * Check whether the "statify_cleanup" cron task is scheduled on a specific site.
	 *
	 * @param int $blog_id Blog ID.
	 *
	 * @return bool True of cron task is scheduled.
	 */
	private function cron_scheduled_on( int $blog_id ): bool {
		switch_to_blog( $blog_id );
		$next = wp_next_scheduled( 'statify_cleanup' );
		restore_current_blog();

		return (bool) $next;
	}
}
