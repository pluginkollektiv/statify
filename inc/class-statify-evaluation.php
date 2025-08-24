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
	public static function add_capability(): void {
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
	public static function add_menu(): void {
		add_menu_page(
			__( 'Statify', 'statify' ),
			'Statify',
			'see_statify_evaluation',
			'statify_dashboard',
			array( __CLASS__, 'show_dashboard' ),
			'dashicons-chart-area',
			50
		);

		add_submenu_page(
			'statify_dashboard',
			__( 'Content', 'statify' ) . ' &mdash; ' . __( 'Statify', 'statify' ),
			__( 'Content', 'statify' ),
			'see_statify_evaluation',
			'statify_content',
			array( __CLASS__, 'show_content' )
		);
	}

	/**
	 * Show the dashboard page.
	 */
	public static function show_dashboard(): void {
		self::show_view( 'dashboard' );
	}

	/**
	 * Show the content page.
	 */
	public static function show_content(): void {
		self::show_view( 'content' );
	}

	/**
	 * Load a specific page view.
	 *
	 * @param string $view The view to load.
	 */
	private static function show_view( string $view ): void {
		self::add_js();
		self::add_style();
		wp_enqueue_script( 'chartist_js' );
		wp_enqueue_script( 'statify_chart_js' );

		load_template( wp_normalize_path( STATIFY_DIR . '/views/view-' . $view . '.php' ) );
	}

	/**
	 * Returns the years Statify has collected data for in descending order.
	 *
	 * @return int[] an array of integers (e.g. 2016, 2015)
	 */
	public static function get_years(): array {
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
	 * @param int         $single_year single year.
	 * @param string|null $post_url    the URL of the post to select for (or the empty string for all posts).
	 *
	 * @return array an array with date as key and views as value
	 */
	public static function get_views_for_all_days( int $single_year = 0, ?string $post_url = '' ): array {
		global $wpdb;

		$query = "SELECT `created` as `date`, COUNT(`created`) as `count` FROM `$wpdb->statify`";
		$args = array();

		if ( $single_year > 0 ) {
			$query .= ' WHERE YEAR(`created`) = %d';
			$args[] = $single_year;
		}

		if ( ! empty( $post_url ) ) {
			$query .= ( $single_year > 0 ? ' AND' : ' WHERE' ) . ' `target` = %s';
			$args[] = $post_url;
		}

		$query .= ' GROUP BY `created` ORDER BY `created`';

		if ( ! empty( $args ) ) {
			$query = $wpdb->prepare( $query, $args );
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		$views_for_all_days = array();
		foreach ( $results as $result ) {
			$views_for_all_days[ $result['date'] ] = intval( $result['count'] );
		}

		// Fill gaps with zeros.
		if ( count( $views_for_all_days ) > 1 ) {
			$period = new DatePeriod(
				new DateTime( array_keys( $views_for_all_days )[0] ),
				DateInterval::createFromDateString( '1 day' ),
				new DateTime( array_keys( $views_for_all_days )[ count( $views_for_all_days ) - 1 ] )
			);
			foreach ( $period as $date ) {
				$date = $date->format( 'Y-m-d' );
				if ( ! array_key_exists( $date, $views_for_all_days ) ) {
					$views_for_all_days[ $date ] = 0;
				}
			}
			ksort( $views_for_all_days );
		}

		return $views_for_all_days;
	}

	/**
	 * Returns the views for all months.
	 * If the given URL is not the empty string, the result is restricted to the given post.
	 *
	 * @param string|null $post_url the URL of the post to select for (or the empty string for all posts).
	 *
	 * @return array an array with month as key and views as value.
	 */
	public static function get_views_for_all_months( ?string $post_url = '' ): array {
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

		// Fill gaps with zeros.
		if ( count( $views_for_all_months ) > 1 ) {
			$period = new DatePeriod(
				new DateTime( array_keys( $views_for_all_months )[0] ),
				DateInterval::createFromDateString( '1 month' ),
				new DateTime( array_keys( $views_for_all_months )[ count( $views_for_all_months ) - 1 ] )
			);
			foreach ( $period as $date ) {
				$date = $date->format( 'Y-m' );
				if ( ! array_key_exists( $date, $views_for_all_months ) ) {
					$views_for_all_months[ $date ] = 0;
				}
			}
			ksort( $views_for_all_months );
		}

		return $views_for_all_months;
	}

	/**
	 * Returns the views for all years.
	 *
	 * If the given URL is not the empty string, the result is restricted to the given post.
	 *
	 * @param string|null $post_url the URL of the post to select for (or the empty string for all posts).
	 *
	 * @return array an array with the year as key and views as value.
	 */
	public static function get_views_for_all_years( ?string $post_url = '' ): array {
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
	 * Returns the most popular posts with their views count (in the date period if set).
	 *
	 * @param string $start the start date of the period.
	 * @param string $end the end date of the period.
	 *
	 * @return array an array with the most popular posts, ordered by view count.
	 */
	public static function get_views_of_most_popular_posts( string $start = '', string $end = '' ): array {
		global $wpdb;

		if ( empty( $start ) || empty( $end ) ) {
			$results = $wpdb->get_results(
				'SELECT COUNT(`target`) as `count`, `target` as `url`' .
				" FROM `$wpdb->statify`" .
				' WHERE `target` IS NOT NULL' .
				' GROUP BY `target`' .
				' ORDER BY `count` DESC',
				ARRAY_A
			);
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT COUNT(`target`) as `count`, `target` as `url`' .
					" FROM `$wpdb->statify`" .
					' WHERE `created` BETWEEN %s AND %s' .
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
	 * Returns the most popular referrers with their views count.
	 * If the given URL is not the empty string, the result is restricted to the given post.
	 *
	 * @param string|null $post_url the URL of the post to select for (or the empty string for all posts).
	 * @param string|null $start the start date of the period.
	 * @param string|null $end the end date of the period.
	 *
	 * @return array an array with the most referrers, ordered by view count
	 */
	public static function get_views_for_all_referrers( ?string $post_url = '', ?string $start = '', ?string $end = '' ): array {
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
	 * @return string[] an array of urls
	 */
	public static function get_post_urls(): array {
		global $wpdb;

		return $wpdb->get_col(
			'SELECT DISTINCT `target`' .
			" FROM `$wpdb->statify`" .
			' WHERE `target` IS NOT NULL' .
			' ORDER BY `target` ASC'
		);
	}

	/**
	 * Returns the post types of the site: post, page and custom post types.
	 *
	 * @return string[] an array of post type slugs.
	 */
	public static function get_post_types(): array {
		$types_args = array(
			'public' => true,
			'_builtin' => false,
		);

		return array_merge( array( 'post', 'page' ), get_post_types( $types_args ) );
	}
}
