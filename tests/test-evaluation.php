<?php
/**
 * Statify evaluation tests.
 *
 * @package Statify
 */

/**
 * Class Test_Evaluation.
 * Tests for extended evaluation queries.
 */
class Test_Evaluation extends WP_UnitTestCase {
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
	 * Test years.
	 */
	public function test_get_years() {
		self::assertSame( array(), Statify_Evaluation::get_years() );
		$this->insert_test_data( '2023-03-25' );
		self::assertSame( array( 2023 ), Statify_Evaluation::get_years() );
		$this->insert_test_data( '2023-03-24' );
		$this->insert_test_data( '2022-03-04' );
		$this->insert_test_data( '2024-05-06' );
		$this->insert_test_data( '2020-01-02' );
		self::assertSame( array( 2024, 2023, 2022, 2020 ), Statify_Evaluation::get_years() );
	}

	/**
	 * Test views for all days.
	 */
	public function test_get_views_for_all_days() {
		$this->insert_test_data( '2023-03-23', '', '/', 3 );
		$this->insert_test_data( '2023-03-23', '', '/test/' );
		$this->insert_test_data( '2023-03-25', '', '/' );
		$this->insert_test_data( '2023-03-25', '', '/test/', 2 );

		self::assertSame(
			array(
				'2023-03-23' => 4,
				'2023-03-25' => 3,
			),
			Statify_Evaluation::get_views_for_all_days()
		);
		self::assertSame(
			array(
				'2023-03-23' => 1,
				'2023-03-25' => 2,
			),
			Statify_Evaluation::get_views_for_all_days( '/test/' )
		);
	}

	/**
	 * Test views for all months.
	 */
	public function test_get_views_for_all_months() {
		$this->insert_test_data( '2023-02-23', '', '/', 3 );
		$this->insert_test_data( '2023-02-22', '', '/test/' );
		$this->insert_test_data( '2023-03-24', '', '/' );
		$this->insert_test_data( '2023-03-25', '', '/test/', 2 );

		self::assertSame(
			array(
				'2023-02' => 4,
				'2023-03' => 3,
			),
			Statify_Evaluation::get_views_for_all_months()
		);
		self::assertSame(
			array(
				'2023-02' => 1,
				'2023-03' => 2,
			),
			Statify_Evaluation::get_views_for_all_months( '/test/' )
		);
	}

	/**
	 * Test views for all years.
	 */
	public function test_get_views_for_all_years() {
		$this->insert_test_data( '2022-02-23', '', '/', 3 );
		$this->insert_test_data( '2022-02-22', '', '/test/' );
		$this->insert_test_data( '2023-03-24', '', '/' );
		$this->insert_test_data( '2023-03-25', '', '/test/', 2 );

		self::assertSame(
			array(
				'2022' => 4,
				'2023' => 3,
			),
			Statify_Evaluation::get_views_for_all_years()
		);
		self::assertSame(
			array(
				'2022' => 1,
				'2023' => 2,
			),
			Statify_Evaluation::get_views_for_all_years( '/test/' )
		);
	}

	/**
	 * Test views for most popular posts.
	 */
	public function test_get_views_of_most_popular_posts() {
		$this->insert_test_data( '2023-03-22', '', '/' );
		$this->insert_test_data( '2023-03-23', '', '/test/', 3 );
		$this->insert_test_data( '2023-03-24', '', '/' );
		$this->insert_test_data( '2023-03-25', '', '/foo/', 4 );

		self::assertSame(
			array(
				array(
					'count' => 4,
					'url'   => '/foo/',
				),
				array(
					'count' => 3,
					'url'   => '/test/',
				),
				array(
					'count' => 2,
					'url'   => '/',
				),
			),
			Statify_Evaluation::get_views_of_most_popular_posts()
		);

		self::assertSame(
			array(
				array(
					'count' => 3,
					'url'   => '/test/',
				),
				array(
					'count' => 1,
					'url'   => '/',
				),
			),
			Statify_Evaluation::get_views_of_most_popular_posts( '2023-03-23', '2023-03-24' )
		);
	}

	/**
	 * Test views for all referrers.
	 */
	public function test_get_views_for_all_referrers() {
		$this->insert_test_data( '2023-03-22', 'https://example.com', '/' );
		$this->insert_test_data( '2023-03-23', 'https://example.com/foo', '/test/', 2 );
		$this->insert_test_data( '2023-03-24', 'http://example.org', '/' );
		$this->insert_test_data( '2023-03-24', '', '/' );
		$this->insert_test_data( '2023-03-25', 'https://pluginkollektiv.de/', '/foo/', 4 );

		self::assertSame(
			array(
				array(
					'count' => 4,
					'url'   => 'https://pluginkollektiv.de/',
					'host'  => 'pluginkollektiv.de',
				),
				array(
					'count' => 3,
					'url'   => 'https://example.com',
					'host'  => 'example.com',
				),
				array(
					'count' => 1,
					'url'   => 'http://example.org',
					'host'  => 'example.org',
				),
			),
			Statify_Evaluation::get_views_for_all_referrers()
		);

		self::assertSame(
			array(
				array(
					'count' => 2,
					'url'   => 'https://example.com/foo',
					'host'  => 'example.com',
				),
				array(
					'count' => 1,
					'url'   => 'http://example.org',
					'host'  => 'example.org',
				),
			),
			Statify_Evaluation::get_views_for_all_referrers( '', '2023-03-23', '2023-03-24' )
		);

		self::assertSame(
			array(
				array(
					'count' => 4,
					'url'   => 'https://pluginkollektiv.de/',
					'host'  => 'pluginkollektiv.de',
				),
			),
			Statify_Evaluation::get_views_for_all_referrers( '/foo/' )
		);

		self::assertSame(
			array(
				array(
					'count' => 1,
					'url'   => 'https://example.com',
					'host'  => 'example.com',
				),
			),
			Statify_Evaluation::get_views_for_all_referrers( '/', '2023-03-20', '2023-03-23' )
		);
	}

	/**
	 * Test post URLs.
	 */
	public function test_get_post_urls() {
		$this->insert_test_data( '2023-03-22', '', '/' );
		$this->insert_test_data( '2023-03-23', '', '/test/', 3 );
		$this->insert_test_data( '2023-03-24', '', '/' );
		$this->insert_test_data( '2023-03-25', '', '/foo/', 4 );

		self::assertSame(
			array( '/', '/foo/', '/test/' ),
			Statify_Evaluation::get_post_urls()
		);
	}
}
