<?php
/**
 * Statify: Statify_Evaluation class
 *
 * Extended evaluation methods for Statify.
 * The logic was initially imported from "Statify – Extended Evaluation" by Patrick Robrecht.
 *
 * @package Statify
 * @since   2.0.0
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * Statify Extended Evaluation.
 *
 * @since 2.0.0
 */
class Statify_Evaluation extends Statify {
	const CAPABILITY_SEE_STATS = 'see_statify_evaluation';

	/**
	 * Add capability to see evaluations.
	 */
	public static function add_capability() {
		if ( isset( self::$_options['show_widget_roles'] ) ) {
			foreach ( self::$_options['show_widget_roles'] as $role_name ) {
				$role = get_role( $role_name );
				if ( $role ) {
					$role->add_cap( self::CAPABILITY_SEE_STATS );
				}
			}
		} else {
			// Backwards compatibility for older statify versions without this option.
			$role = get_role( 'administrator' );
			if ( $role ) {
				$role->add_cap( self::CAPABILITY_SEE_STATS );
			}
		}
	}

	/**
	 * Create an item and submenu items in the WordPress admin menu.
	 */
	public static function add_menu() {
		add_menu_page(
			__( 'Statify', 'statify' ),
			'Statify',
			'see_statify_evaluation',
			'statify_dashboard',
			array( __CLASS__, 'show_dashboard' ),
			'dashicons-chart-area',
			50
		);
	}

	/**
	 * Show the dashboard page.
	 */
	public static function show_dashboard() {
		self::add_js();
		self::add_style();
		wp_enqueue_script( 'chartist_js' );
		wp_enqueue_script( 'statify_chart_js' );

		load_template( wp_normalize_path( STATIFY_DIR . '/views/view-dashboard.php' ) );
	}

	/**
	 * Returns the numeric values for all days in a month.
	 *
	 * @param int $month the month as value between 1 and 12 (default: 1).
	 * @param int $year a year (default: 0).
	 *
	 * @return array an array of integers (1, 2, ..., 31).
	 */
	public static function get_days( $month = 1, $year = 0 ) {
		if ( 2 === $month ) {
			if ( checkdate( 2, 29, $year ) ) {
				$last_day = 29;
			} else {
				$last_day = 28;
			}
		} elseif ( in_array( $month, array( 4, 6, 9, 11 ), true ) ) {
			$last_day = 30;
		} else {
			$last_day = 31;
		}

		return range( 1, $last_day );
	}

	/**
	 * Returns the numeric values of all months.
	 *
	 * @return array an array of integers (1, 2, ..., 12)
	 */
	public static function get_months() {
		return range( 1, 12 );
	}

	/**
	 * Returns the years Statify has collected data for in descending order.
	 *
	 * @return array an array of integers (e. g. 2016, 2015)
	 */
	public static function get_years() {
		global $wpdb;

		$results = $wpdb->get_results(
			'SELECT DISTINCT YEAR(`created`) as `year`' .
			" FROM `$wpdb->statify` " .
			' ORDER BY `year` DESC',
			ARRAY_A
		);
		$years = array();
		foreach ( $results as $result ) {
			$years[] = (int) $result['year'];
		}

		return $years;
	}

	/**
	 * Returns the views for all days.
	 * If the given URL is not the empty string, the result is restricted to the given post.
	 *
	 * @param string $post_url the URL of the post to select for (or the empty string for all posts).
	 *
	 * @return array an array with date as key and views as value
	 */
	public static function get_views_for_all_days( $post_url = '' ) {
		global $wpdb;

		if ( empty( $post_url ) ) {
			// For all posts.
			$results = $wpdb->get_results(
				'SELECT `created` as `date`, COUNT(`created`) as `count`' .
				" FROM `$wpdb->statify`" .
				' GROUP BY `created`' .
				' ORDER BY `created`',
				ARRAY_A
			);
		} else {
			// Only for selected posts.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT `created` as `date`, COUNT(`created`) as `count`' .
					" FROM `$wpdb->statify`" .
					' WHERE `target` = %s' .
					' GROUP BY `created`' .
					' ORDER BY `created`',
					$post_url
				),
				ARRAY_A
			);
		}
		$views_for_all_days = array();
		foreach ( $results as $result ) {
			$views_for_all_days[ $result['date'] ] = intval( $result['count'] );
		}

		return $views_for_all_days;
	}

	/**
	 * Returns the views for one day.
	 * If the date does not exist (e.g. 30th February), this method returns -1.
	 *
	 * @param array $views_for_all_days an array with the daily views.
	 * @param int   $year the year.
	 * @param int   $month the month.
	 * @param int   $day the day.
	 *
	 * @return int number the number of views (or -1 if the date is invalid).
	 */
	public static function get_daily_views( $views_for_all_days, $year, $month, $day ) {
		if ( checkdate( $month, $day, $year ) ) {
			$date = sprintf(
				'%s-%s-%s',
				str_pad( $year, 4, '0', STR_PAD_LEFT ),
				str_pad( $month, 2, '0', STR_PAD_LEFT ),
				str_pad( $day, 2, '0', STR_PAD_LEFT )
			);
			if ( array_key_exists( $date, $views_for_all_days ) ) {
				return $views_for_all_days[ $date ];
			}

			return 0;
		}

		// No valid date.
		return -1;
	}

	/**
	 * Returns the views for all months.
	 * If the given URL is not the empty string, the result is restricted to the given post.
	 *
	 * @param string $post_url the URL of the post to select for (or the empty string for all posts).
	 *
	 * @return array an array with month as key and views as value.
	 */
	public static function get_views_for_all_months( $post_url = '' ) {
		global $wpdb;

		if ( empty( $post_url ) ) {
			// For all posts.
			$results = $wpdb->get_results(
				"SELECT DATE_FORMAT(`created`, '%Y-%m') as `date`, COUNT(`created`) as `count`" .
				" FROM `$wpdb->statify`" .
				' GROUP BY `date`' .
				' ORDER BY `date`',
				ARRAY_A
			);
		} else {
			// Only for selected posts.
			$results = $wpdb->get_results(
				$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder
					"SELECT DATE_FORMAT(`created`, '%Y-%m') as `date`, COUNT(`created`) as `count`
                FROM `$wpdb->statify`
                WHERE `target` = %s
                GROUP BY `date`
                ORDER BY `date`",
					$post_url
				),
				ARRAY_A
			);
		}
		$views_for_all_months = array();
		foreach ( $results as $result ) {
			$views_for_all_months[ $result['date'] ] = intval( $result['count'] );
		}

		return $views_for_all_months;
	}

	/**
	 * Returns the views for one month.
	 * If the date does not exist (e.g. 30th February), this method returns -1.
	 *
	 * @param array $views_for_all_months an array with the monthly views.
	 * @param int   $year the year.
	 * @param int   $month the month.
	 *
	 * @return int the view for the given month.
	 */
	public static function get_monthly_views( $views_for_all_months, $year, $month ) {
		if ( $month < 1 || $month > 12 ) {
			return -1;
		}

		$date = sprintf(
			'%s-%s',
			str_pad( $year, 4, '0', STR_PAD_LEFT ),
			str_pad( $month, 2, '0', STR_PAD_LEFT )
		);
		if ( array_key_exists( $date, $views_for_all_months ) ) {
			return $views_for_all_months[ $date ];
		}

		return 0;
	}

	/**
	 * Returns the average daily views in the given month.
	 *
	 * @param array $views_for_all_months an array with the monthly views.
	 * @param int   $year the year.
	 * @param int   $month the month.
	 *
	 * @return int the average daily views in the given month.
	 */
	public static function get_average_daily_views_of_month( $views_for_all_months, $year, $month ) {
		if ( $month < 1 || $month > 12 ) {
			return -1;
		}

		$views_in_month = self::get_monthly_views( $views_for_all_months, $year, $month );
		if ( self::is_current_month( $year, $month ) ) {
			$days_in_month = (int) gmdate( 'd' );
		} else {
			$days_in_month = count( self::get_days( $month, $year ) );
		}

		return (int) round( $views_in_month / $days_in_month );
	}

	/**
	 * Returns an array with the daily views for all days in the given month.
	 * If the given month is the current one, just the views for past days and the current day is included.
	 *
	 * @param array $views_for_all_days an array with the daily views.
	 * @param int   $year the year.
	 * @param int   $month the month.
	 *
	 * @return array array with the daily views for all days in the given day.
	 */
	public static function get_daily_views_of_month( $views_for_all_days, $year, $month ) {
		if ( $month < 1 || $month > 12 ) {
			return array();
		}

		if ( self::is_current_month( $year, $month ) ) {
			$days = range( 1, (int) gmdate( 'd' ) );
		} else {
			$days = self::get_days( $month, $year );
		}

		$views = array();
		foreach ( $days as $day ) {
			$views[] = self::get_daily_views( $views_for_all_days, $year, $month, $day );
		}

		return $views;
	}

	/**
	 * Returns whether the given month is the current one.
	 *
	 * @param int $year a year.
	 * @param int $month a month.
	 *
	 * @return bool true if and only if the given month is the current one.
	 */
	private static function is_current_month( $year, $month ) {
		$current_year = (int) gmdate( 'Y' );
		$current_month = (int) gmdate( 'm' );

		return $current_year === $year && $current_month === $month;
	}

	/**
	 * Returns the views for all years.
	 *
	 * If the given URL is not the empty string, the result is restricted to the given post.
	 *
	 * @param string $post_url the URL of the post to select for (or the empty string for all posts).
	 *
	 * @return array an array with the year as key and views as value.
	 */
	public static function get_views_for_all_years( $post_url = '' ) {
		global $wpdb;

		if ( empty( $post_url ) ) {
			// For all posts.
			$results = $wpdb->get_results(
				'SELECT YEAR(`created`) as `date`, COUNT(`created`) as `count`' .
				" FROM `$wpdb->statify`" .
				' GROUP BY `date`',
				ARRAY_A
			);
		} else {
			// Only for selected posts.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT YEAR(`created`) as `date`, COUNT(`created`) as `count`' .
					" FROM `$wpdb->statify`" .
					' WHERE `target` = %s' .
					' GROUP BY `date`',
					$post_url
				),
				ARRAY_A
			);
		}
		$views_for_all_years = array();
		foreach ( $results as $result ) {
			$views_for_all_years[ $result['date'] ] = intval( $result['count'] );
		}

		return $views_for_all_years;
	}

	/**
	 * Returns the views for one year. If the date does not exist (e.g. 30th February),
	 * this method returns -1.
	 *
	 * @param array $views_for_all_years an array with the yearly views.
	 * @param int   $year the year.
	 *
	 * @return int the views of the given year.
	 */
	public static function get_yearly_views( $views_for_all_years, $year ) {
		$year_key = str_pad( $year, 4, '0', STR_PAD_LEFT );
		if ( array_key_exists( $year_key, $views_for_all_years ) ) {
			return $views_for_all_years[ $year_key ];
		}

		return 0;
	}

	/**
	 * Returns the most popular posts with their views count (in the date period if set).
	 *
	 * @param string $start the start date of the period.
	 * @param string $end the end date of the period.
	 *
	 * @return array an array with the most popular posts, ordered by view count.
	 */
	public static function get_views_of_most_popular_posts( $start = '', $end = '' ) {
		global $wpdb;

		if ( empty( $start ) && empty( $end ) ) {
			$results = $wpdb->get_results(
				'SELECT COUNT(`target`) as `count`, `target` as `url`' .
				" FROM `$wpdb->statify`" .
				' GROUP BY `target`' .
				' ORDER BY `count` DESC',
				ARRAY_A
			);
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT COUNT(`target`) as `count`, `target` as `url`' .
					" FROM `$wpdb->statify`" .
					' WHERE `created` >= %s AND `created` <= %s' .
					' GROUP BY `target`' .
					' ORDER BY `count` DESC',
					$start,
					$end
				),
				ARRAY_A
			);
		}

		foreach ( $results as &$result ) {
			$result['count'] = intval( $result['count'] );
		}

		return $results;
	}

	/**
	 * Returns the number of views for the post with the given URL (in the date period if set).
	 *
	 * @param string $url the post URL.
	 * @param string $start the start date of the period.
	 * @param string $end the end date of the period.
	 *
	 * @return int the number of views for the post.
	 */
	public static function get_views_of_post( $url, $start = '', $end = '' ) {
		global $wpdb;

		if ( empty( $start ) && empty( $end ) ) {
			$where = '`target` = %s';
			$param = $url;
		} else {
			$where = '`target` = %s AND `created` >= %s AND `created` <= %s';
			$param = array( $url, $start, $end );
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT COUNT(`target`) as `count`' .
				" FROM `$wpdb->statify`" .
				' WHERE ' . $where,
				$param
			),
			OBJECT
		);

		return intval( $results[0]->count );
	}

	/**
	 * Returns the most popular referrers with their views count.
	 * If the given URL is not the empty string, the result is restricted to the given post.
	 *
	 * @param string $post_url the URL of the post to select for (or the empty string for all posts).
	 * @param string $start the start date of the period.
	 * @param string $end the end date of the period.
	 *
	 * @return array an array with the most referrers, ordered by view count
	 */
	public static function get_views_for_all_referrers( $post_url = '', $start = '', $end = '' ) {
		global $wpdb;

		if ( empty( $post_url ) ) {
			// For all posts.
			if ( empty( $start ) && empty( $end ) ) {
				$where = "`referrer` != ''";
				$param = array();
			} else {
				$where = "`referrer` != '' AND `created` >= %s AND `created` <= %s";
				$param = array( $start, $end );
			}
		} elseif ( empty( $start ) && empty( $end ) ) {
			// Only for selected posts.
			$where = "`referrer` != '' AND target = %s";
			$param = array( $post_url );
		} else {
			$where = "`referrer` != '' AND `target` = %s AND `created` >= %s AND `created` <= %s";
			$param = array( $post_url, $start, $end );
		}

		$stmt = 'SELECT COUNT(`referrer`) as `count`, `referrer` as `url`,' .
			" SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(LEADING 'www.' FROM(TRIM(LEADING 'https://' FROM TRIM(LEADING 'http://' FROM TRIM(`referrer`))))), '/', 1), ':', 1) as `host`" .
			" FROM `$wpdb->statify`" .
			' WHERE ' . $where .
			' GROUP BY `host`' .
			' ORDER BY `count` DESC';

		if ( ! empty( $param ) ) {
			$stmt = $wpdb->prepare( $stmt, $param );
		}
		$results = $wpdb->get_results( $stmt, ARRAY_A );

		foreach ( $results as &$result ) {
			$result['count'] = intval( $result['count'] );
		}

		return $results;
	}

	/**
	 * Returns a list of all target URLs.
	 *
	 * @return array an array of urls
	 */
	public static function get_post_urls() {
		global $wpdb;

		return $wpdb->get_col(
			'SELECT DISTINCT `target`' .
			" FROM `$wpdb->statify`" .
			' ORDER BY `target` ASC'
		);
	}

	/**
	 * Returns the post types of the site: post, page and custom post types.
	 *
	 * @return array an array of post type slugs.
	 */
	public static function get_post_types() {
		$types_args = array(
			'public' => true,
			'_builtin' => false,
		);

		return array_merge( array( 'post', 'page' ), get_post_types( $types_args ) );
	}
}
