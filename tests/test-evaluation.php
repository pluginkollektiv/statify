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
	 * Test days per month.
	 */
	public function test_get_days() {
		foreach ( array( 1, 3, 5, 7, 8, 10, 12 ) as $m ) {
			self::assertSame(
				range( 1, 31 ),
				Statify_Evaluation::get_days( $m, 2023 )
			);
		}
		foreach ( array( 4, 6, 9, 11 ) as $m ) {
			self::assertSame(
				range( 1, 30 ),
				Statify_Evaluation::get_days( $m, 2023 )
			);
		}
		self::assertSame(
			range( 1, 28 ),
			Statify_Evaluation::get_days( 2, 2023 )
		);
		self::assertSame(
			range( 1, 29 ),
			Statify_Evaluation::get_days( 2, 2024 )
		);
	}

	/**
	 * Test months.
	 */
	public function test_get_months() {
		self::assertSame( range( 1, 12 ), Statify_Evaluation::get_months() );
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
		$this->insert_test_data( '2022-10-20', '', '/test/' );
		$this->insert_test_data( '2023-03-23', '', '/', 3 );
		$this->insert_test_data( '2023-03-23', '', '/test/' );
		$this->insert_test_data( '2023-03-25', '', '/' );
		$this->insert_test_data( '2023-03-25', '', '/test/', 2 );

		self::assertSame(
			array(
				'2022-10-20' => 1,
				'2023-03-23' => 4,
				'2023-03-25' => 3,
			),
			Statify_Evaluation::get_views_for_all_days(),
			'unexpected results without anyfilter'
		);
		self::assertSame(
			array(
				'2022-10-20' => 1,
				'2023-03-23' => 1,
				'2023-03-25' => 2,
			),
			Statify_Evaluation::get_views_for_all_days( 0, '/test/' ),
			'unexpected results with post filter'
		);
		self::assertSame(
			array(
				'2023-03-23' => 4,
				'2023-03-25' => 3,
			),
			Statify_Evaluation::get_views_for_all_days( 2023 ),
			'unexpected results with year filter'
		);
		self::assertSame(
			array(
				'2023-03-23' => 1,
				'2023-03-25' => 2,
			),
			Statify_Evaluation::get_views_for_all_days( 2023, '/test/' ),
			'unexpected results with year and post filter'
		);
	}

	/**
	 * Test views for a single day.
	 */
	public function test_get_daily_views() {
		$all = array(
			'2023-03-25' => 3,
			'2023-03-23' => 4,
			'2023-03-22' => 5,
		);

		self::assertSame( 3, Statify_Evaluation::get_daily_views( $all, 2023, 3, 25 ) );
		self::assertSame( 5, Statify_Evaluation::get_daily_views( $all, 2023, 3, 22 ) );
		self::assertSame( 0, Statify_Evaluation::get_daily_views( $all, 2023, 3, 21 ) );
		self::assertSame( -1, Statify_Evaluation::get_daily_views( $all, 2023, 3, 32 ) );
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
	 * Test views for a single month.
	 */
	public function test_get_monthly_views() {
		$all = array(
			'2023-03' => 3,
			'2023-02' => 4,
			'2023-01' => 5,
		);

		self::assertSame( 3, Statify_Evaluation::get_monthly_views( $all, 2023, 3 ) );
		self::assertSame( 5, Statify_Evaluation::get_monthly_views( $all, 2023, 1 ) );
		self::assertSame( 0, Statify_Evaluation::get_monthly_views( $all, 2023, 4 ) );
		self::assertSame( -1, Statify_Evaluation::get_monthly_views( $all, 2023, 13 ) );
	}

	/**
	 * Test average views for a single month.
	 */
	public function test_get_average_daily_views_of_month() {
		$now = new DateTime();
		$all = array(
			$now->format( 'Y-m' ) => intval( $now->format( 'd' ) ) * 25,
			'2023-02' => 1400,
			'2023-01' => 2325,
		);

		self::assertSame(
			25,
			Statify_Evaluation::get_average_daily_views_of_month( $all, intval( $now->format( 'Y' ) ), intval( $now->format( 'm' ) ) )
		);
		self::assertSame( 50, Statify_Evaluation::get_average_daily_views_of_month( $all, 2023, 2 ) );
		self::assertSame( 75, Statify_Evaluation::get_average_daily_views_of_month( $all, 2023, 1 ) );
		self::assertSame( 0, Statify_Evaluation::get_average_daily_views_of_month( $all, 2022, 12 ) );
		self::assertSame( -1, Statify_Evaluation::get_average_daily_views_of_month( $all, 2023, 13 ) );
	}

	/**
	 * Test daily views for a single month.
	 */
	public function test_get_daily_views_of_month() {
		$now = new DateTime();

		$all = array(
			$now->format( 'Y-m-01' ) => 5,
			'2023-02-15' => 6,
			'2023-02-10' => 7,
			'2023-02-05' => 8,
		);

		$current_month = array_fill( 0, intval( $now->format( 'd' ) ), 0 );
		$current_month[0] = 5;
		self::assertSame(
			$current_month,
			Statify_Evaluation::get_daily_views_of_month( $all, intval( $now->format( 'Y' ) ), intval( $now->format( 'm' ) ) )
		);
		self::assertSame(
			array( 0, 0, 0, 0, 8, 0, 0, 0, 0, 7, 0, 0, 0, 0, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 ),
			Statify_Evaluation::get_daily_views_of_month( $all, 2023, 2 )
		);
		self::assertSame(
			array( 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 ),
			Statify_Evaluation::get_daily_views_of_month( $all, 2023, 1 )
		);
		self::assertSame( array(), Statify_Evaluation::get_daily_views_of_month( $all, 2023, 13 ) );
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
	 * Test views for a single year.
	 */
	public function test_get_yearly_views() {
		$all = array(
			'2023' => 3,
			'2022' => 4,
			'2021' => 5,
		);

		self::assertSame( 0, Statify_Evaluation::get_yearly_views( $all, 2024 ) );
		self::assertSame( 3, Statify_Evaluation::get_yearly_views( $all, 2023 ) );
		self::assertSame( 5, Statify_Evaluation::get_yearly_views( $all, 2021 ) );
		self::assertSame( 0, Statify_Evaluation::get_yearly_views( $all, 2020 ) );
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
					'url' => '/foo/',
				),
				array(
					'count' => 3,
					'url' => '/test/',
				),
				array(
					'count' => 2,
					'url' => '/',
				),
			),
			Statify_Evaluation::get_views_of_most_popular_posts()
		);

		self::assertSame(
			array(
				array(
					'count' => 3,
					'url' => '/test/',
				),
				array(
					'count' => 1,
					'url' => '/',
				),
			),
			Statify_Evaluation::get_views_of_most_popular_posts( '2023-03-23', '2023-03-24' )
		);
	}

	/**
	 * Test views for a single post.
	 */
	public function test_get_views_of_post() {
		$this->insert_test_data( '2023-03-22', '', '/' );
		$this->insert_test_data( '2023-03-23', '', '/test/', 3 );
		$this->insert_test_data( '2023-03-24', '', '/' );
		$this->insert_test_data( '2023-03-25', '', '/foo/', 4 );

		self::assertSame( 2, Statify_Evaluation::get_views_of_post( '/' ) );
		self::assertSame( 3, Statify_Evaluation::get_views_of_post( '/test/' ) );

		self::assertSame(
			1,
			Statify_Evaluation::get_views_of_post( '/', '2023-03-01', '2023-03-23' )
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
					'url' => 'https://pluginkollektiv.de/',
					'host' => 'pluginkollektiv.de',
				),
			),
			Statify_Evaluation::get_views_for_all_referrers( '/foo/' )
		);

		self::assertSame(
			array(
				array(
					'count' => 1,
					'url' => 'https://example.com',
					'host' => 'example.com',
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
