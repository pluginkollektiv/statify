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
		// Reset options to default.
		Statify::$_options = array(
			'days'              => 14,
			'days_show'         => 14,
			'limit'             => 3,
			'today'             => 0,
			'snippet'           => 0,
			'blacklist'         => 0,
			'show_totals'       => 0,
			'show_widget_roles' => null,
			'skip'              => array(
				'logged_in' => Statify::SKIP_USERS_ALL,
			),
		);

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
				'days'        => 14,
				'days_show'   => 14,
				'limit'       => 100,
				'today'       => 0,
				'blacklist'   => 0,
				'show_totals' => 0,
			),
			Statify_Settings::sanitize_options( array( 'limit' => 101 ) ),
			'limit was not capped at 100'
		);

		self::assertSame(
			array(
				'days'        => 14,
				'days_show'   => 14,
				'limit'       => 3,
				'snippet'     => 1,
				'today'       => 0,
				'blacklist'   => 0,
				'show_totals' => 0,
				'skip'        => array(
					'logged_in' => 0,
				),
			),
			Statify_Settings::sanitize_options(
				array(
					'snippet' => '1',
					'skip'    => array(
						'logged_in' => '0',
					),
				)
			),
			'valid "snippet" and "logged_in" settings not passed through'
		);

		self::assertSame(
			array(
				'days'        => 14,
				'days_show'   => 14,
				'limit'       => 3,
				'today'       => 0,
				'blacklist'   => 0,
				'show_totals' => 0,
			),
			Statify_Settings::sanitize_options(
				array(
					'snippet'   => 3,
					'logged_in' => -1,
				)
			),
			'illegal "snippet" and "logged_in" settings not removed'
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
