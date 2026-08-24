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

		wp_get_current_user()->remove_all_caps();
		self::assertFalse( Statify::user_can_see_stats(), 'Anonymous user must not see stats' );

		// With default capability.
		wp_get_current_user()->add_cap( 'edit_dashboard' );
		self::assertTrue( Statify::user_can_see_stats(), 'User should see stats with default capabilities' );

		// With custom roles.
		$this->init_statify( array( 'show_widget_roles' => array( 'author' ) ) );
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

	/**
	 * Test that the dashboard script localize payload exposes the auto-refresh flag.
	 */
	public function test_localize_auto_refresh() {
		// Default: option absent => autoRefresh false.
		Statify::$options['auto_refresh'] = 0;
		Statify::add_js();
		$registered = wp_scripts()->registered['statify_chart_js'] ?? null;
		self::assertNotNull( $registered, 'statify_chart_js should be registered' );
		$extra_data = $registered->extra['data'] ?? '';
		self::assertNotEmpty( $extra_data, 'extra[data] should be set after add_js' );
		self::assertStringContainsString( 'statifyDashboard', $extra_data, 'localize var should be present' );
		preg_match( '/var\s+statifyDashboard\s*=\s*(\{.*?\});/', $extra_data, $matches );
		self::assertArrayHasKey( 1, $matches, 'statifyDashboard object should be in data' );
		$payload = json_decode( $matches[1], true );
		self::assertIsArray( $payload, 'localized payload should decode to an array' );
		self::assertArrayHasKey( 'autoRefresh', $payload, 'autoRefresh key should be present' );
		// wp_localize_script casts booleans to strings ('1'/''), so we
		// assert on the JS-side truthy/falsy semantics rather than the
		// PHP value type.
		self::assertEmpty( $payload['autoRefresh'], 'autoRefresh should be falsy when option is off' );

		// Reset for second pass.
		wp_deregister_script( 'statify_chart_js' );

		// Enabled: option set => autoRefresh true.
		Statify::$options['auto_refresh'] = 1;
		Statify::add_js();
		$registered = wp_scripts()->registered['statify_chart_js'] ?? null;
		self::assertNotNull( $registered, 'statify_chart_js should be registered when enabled' );
		$extra_data = $registered->extra['data'] ?? '';
		self::assertNotEmpty( $extra_data, 'extra[data] should be set when enabled' );
		preg_match( '/var\s+statifyDashboard\s*=\s*(\{.*?\});/', $extra_data, $matches );
		self::assertArrayHasKey( 1, $matches, 'statifyDashboard object should be in data when enabled' );
		$payload = json_decode( $matches[1], true );
		self::assertNotEmpty( $payload['autoRefresh'], 'autoRefresh should be truthy when option is on' );
	}
}
