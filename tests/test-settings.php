<?php
/**
 * Statify settings tests.
 *
 * @package Statify
 */

/**
 * Class Test_Settings.
 * Tests for settings page.
 */
class Test_Settings extends WP_UnitTestCase {
	use Statify_Test_Support;

	/**
	 * Test Statify Dashboard initialization.
	 */
	public function test_sanitize_options() {
		self::assertSame(
			array(
				'days'        => 14,
				'days_show'   => 14,
				'limit'       => 3,
				'today'       => 0,
				'blacklist'   => 0,
				'show_totals' => 0,
			),
			Statify_Settings::sanitize_options( array() ),
			'unexpected results for empty input'
		);

		self::assertSame(
			array(
				'days'        => 15,
				'days_show'   => 13,
				'limit'       => 4,
				'today'       => 1,
				'blacklist'   => 0,
				'show_totals' => 1,
			),
			Statify_Settings::sanitize_options(
				array(
					'days'        => '15',
					'days_show'   => '13',
					'limit'       => '4',
					'today'       => '1',
					'blacklist'   => 5,
					'show_totals' => '1',
				)
			),
			'string values should be sanitized to numbers or 1/0 for boolean flags'
		);

		self::assertSame(
			array(
				'days'              => 14,
				'days_show'         => 14,
				'limit'             => 3,
				'today'             => 0,
				'blacklist'         => 0,
				'show_totals'       => 0,
				'show_widget_roles' => array( 'administrator', 'author' ),
			),
			Statify_Settings::sanitize_options(
				array(
					'show_widget_roles' => array( 'administrator', '', 'author', 'doesnotexist' ),
				)
			),
			'unknown widget roles should have been removed'
		);
	}
}
