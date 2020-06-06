<?php
/**
 * Statify frontend tests.
 *
 * @package Statify
 */

/**
 * Class Test_Frontend.
 * Tests for frontend integration.
 */
class Test_Frontend extends WP_UnitTestCase {

	/**
	 * Test wp_footer() generation.
	 */
	public function test_wp_footer() {
		// Disable JS tracking.
		$options            = get_option( 'statify' );
		$options['snippet'] = 0;
		update_option( 'statify', $options );
		Statify::init();
		$this->assertNotFalse(
			has_action( 'wp_footer', array( 'Statify_Frontend', 'wp_footer' ) ),
			'Statify footer action not registered'
		);

		Statify_Frontend::wp_footer();
		$this->assertFalse(
			wp_script_is( 'statify-js', 'enqueued' ),
			'Statify JS should not be enqueued if JS tracking is disabled'
		);

		// Enable JS tracking.
		$options['snippet'] = 1;
		update_option( 'statify', $options );
		Statify::init();

		Statify_Frontend::wp_footer();
		$this->assertTrue(
			wp_script_is( 'statify-js', 'enqueued' ),
			'Statify JS must be equeued if JS tracking is enabled'
		);
		$script_data = wp_scripts()->registered['statify-js']->extra['data'];
		$this->assertNotNull( $script_data, 'Statify script not localized' );
		$this->assertRegExp(
			'/^var statify_ajax = {"url":"[^"]+","nonce":"[^"]+"};$/',
			$script_data,
			'unexpected JS localization values'
		);
	}

	/**
	 * Test query_vars() integration.
	 */
	public function test_query_vars() {
		Statify::init();
		$this->assertNotFalse(
			has_action(
				'query_vars',
				array( 'Statify_Frontend', 'query_vars' )
			),
			'Statify query_vars action not registered'
		);

		$vars = Statify_Frontend::query_vars( array() );
		$this->assertCount( 2, $vars, 'Unexpected number of query vars' );
		$this->assertContains( 'statify_referrer', $vars, 'Referrer variable not declared' );
		$this->assertContains( 'statify_target', $vars, 'Target variable not declared' );
	}
}
