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
		Statify::$options = array(
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

	/**
	 * Test localization of built-in user role names in the roles list.
	 */
	public function test_show_roles_list_translates_names() {
		$original_options = Statify::$options;

		Statify::$options = array(
			'days'              => 14,
			'days_show'         => 14,
			'limit'             => 3,
			'today'             => 0,
			'snippet'           => 0,
			'blacklist'         => 0,
			'show_totals'       => 0,
			'show_widget_roles' => array( 'editor' ),
			'skip'              => array(
				'logged_in' => Statify::SKIP_USERS_ALL,
			),
		);

		$roles_callback = static function () {
			return array(
				'editor' => array(
					'name'         => 'Editor',
					'capabilities' => array(),
				),
			);
		};
		add_filter( 'statify__available_roles', $roles_callback );

		// Force a translated name for the built-in "Editor" role.
		$translate_callback = static function ( $translation, $text, $context, $domain ) {
			if ( 'Editor' === $text && 'User role' === $context && 'default' === $domain ) {
				return 'Redakteur';
			}

			return $translation;
		};
		add_filter( 'gettext_with_context', $translate_callback, 10, 4 );

		try {
			ob_start();
			Statify_Settings::options_show_widget_roles();
			$html = ob_get_clean();
		} finally {
			remove_filter( 'statify__available_roles', $roles_callback );
			remove_filter( 'gettext_with_context', $translate_callback, 10, 4 );
			Statify::$options = $original_options;
		}

		self::assertStringContainsString(
			'>Redakteur</label>',
			$html,
			'built-in role display names should be localized via translate_user_role'
		);
	}
}
