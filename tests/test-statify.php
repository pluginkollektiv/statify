<?php
/**
 * Statify tests.
 *
 * @package Statify
 */

/**
 * Class Test_Statify.
 * Tests for Statify core class.
 */
class Test_Statify extends WP_UnitTestCase {
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
	 * Test evaluation of the statify__user_can_see_stats hook.
	 */
	public function test_user_can_see_stats_hook() {
		Statify::init();
		self::assertFalse( Statify::user_can_see_stats(), 'Anonymous user must not see stats' );

		// With default capability
		wp_get_current_user()->add_cap( 'edit_dashboard' );
		self::assertTrue( Statify::user_can_see_stats(), 'User should see stats with default capabilities' );

		// With custom roles.
		$this->init_statify( array(
			'show_widget_roles' => array( 'author' ),
		) );
		self::assertFalse( Statify::user_can_see_stats(), 'User must not see stats with custom role filter' );

		// Now the user has this specific role.
		wp_get_current_user()->add_role( 'author' );
		self::assertTrue( Statify::user_can_see_stats(), 'User should see stats with custom role' );

		// Add a custom filter.
		add_filter(
			'statify__user_can_see_stats',
			function () {
				return false;
			}
		);
		self::assertFalse( Statify::user_can_see_stats(), 'Anonymous must not see stats with hook override' );
	}
}
