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
		if ( isset( self::$options['show_widget_roles'] ) ) {
			foreach ( self::$options['show_widget_roles'] as $role_name ) {
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
	 * Create the Statify entry and its sub pages in the WordPress admin menu.
	 *
	 * The evaluation is shown as a single "Statify" entry below the Dashboard
	 * menu. The content and referrers pages are registered as routable sub pages
	 * as well, but hidden from the menu so that only one entry is displayed. The
	 * in-page navigation (see show_navigation()) switches between them.
	 */
	public static function add_menu(): void {
		add_submenu_page(
			'index.php',
			__( 'Statify', 'statify' ),
			'Statify',
			'see_statify_evaluation',
			'statify_dashboard',
			array( __CLASS__, 'show_dashboard' )
		);

		add_submenu_page(
			'index.php',
			__( 'Content', 'statify' ) . ' &mdash; ' . __( 'Statify', 'statify' ),
			__( 'Content', 'statify' ),
			'see_statify_evaluation',
			'statify_content',
			array( __CLASS__, 'show_content' )
		);

		add_submenu_page(
			'index.php',
			__( 'Referrers', 'statify' ) . ' &mdash; ' . __( 'Statify', 'statify' ),
			__( 'Referrers', 'statify' ),
			'see_statify_evaluation',
			'statify_referrers',
			array( __CLASS__, 'show_referrers' )
		);

		// Only the main "Statify" entry should be visible in the menu; the other
		// pages stay accessible via their in-page navigation.
		remove_submenu_page( 'index.php', 'statify_content' );
		remove_submenu_page( 'index.php', 'statify_referrers' );

		// Keep the "Statify" entry highlighted while on the hidden sub pages.
		add_filter( 'parent_file', array( __CLASS__, 'set_active_menu' ) );
		add_filter( 'submenu_file', array( __CLASS__, 'set_active_submenu' ) );
	}

	/**
	 * Render the navigation tabs shared by all evaluation pages.
	 *
	 * @param string $current The page slug of the currently active tab.
	 */
	public static function show_navigation( string $current ): void {
		$pages = array(
			'statify_dashboard' => __( 'Overview', 'statify' ),
			'statify_content'   => __( 'Content', 'statify' ),
			'statify_referrers' => __( 'Referrers', 'statify' ),
		);
		?>
		<nav class="statify-page-nav nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Statify evaluation', 'statify' ); ?>">
			<?php foreach ( $pages as $slug => $label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'index.php?page=' . $slug ) ); ?>"
			   class="nav-tab<?php echo ( $current === $slug ) ? ' nav-tab-active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
			</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Render a secondary navigation as a list of links (WordPress subsubsub style).
	 *
	 * Used below the primary tab navigation to switch between e.g. years or post
	 * types without repeating the tab styling of the primary navigation.
	 *
	 * @param array  $items      List of items, each an array with the keys 'url',
	 *                           'label' and (optionally) 'current'.
	 * @param string $aria_label Accessible label for the navigation element.
	 */
	public static function show_subnavigation( array $items, string $aria_label ): void {
		if ( empty( $items ) ) {
			return;
		}

		$last_key = array_key_last( $items );
		?>
		<nav class="statify-subnav wp-clearfix" aria-label="<?php echo esc_attr( $aria_label ); ?>">
			<ul class="subsubsub">
				<?php foreach ( $items as $key => $item ) : ?>
				<li>
					<a href="<?php echo esc_url( $item['url'] ); ?>"<?php echo empty( $item['current'] ) ? '' : ' class="current" aria-current="page"'; ?>><?php echo esc_html( $item['label'] ); ?></a><?php echo ( $key === $last_key ) ? '' : ' |'; ?>
				</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
	}

	/**
	 * Keep the Dashboard menu open while on one of the hidden sub pages.
	 *
	 * @param string $parent_file The current parent menu file.
	 * @return string The (possibly adjusted) parent menu file.
	 */
	public static function set_active_menu( string $parent_file ): string {
		if ( self::is_hidden_subpage() ) {
			$parent_file = 'index.php';
		}

		return $parent_file;
	}

	/**
	 * Keep the "Statify" entry highlighted while on one of the hidden sub pages.
	 *
	 * @param string|null $submenu_file The current submenu file.
	 * @return string|null The (possibly adjusted) submenu file.
	 */
	public static function set_active_submenu( $submenu_file ) {
		if ( self::is_hidden_subpage() ) {
			$submenu_file = 'statify_dashboard';
		}

		return $submenu_file;
	}

	/**
	 * Whether the current request is one of the menu-hidden sub pages.
	 *
	 * @return bool True if the content or referrers page is being displayed.
	 */
	private static function is_hidden_subpage(): bool {
		global $plugin_page;

		return 'statify_content' === $plugin_page || 'statify_referrers' === $plugin_page;
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
	 * Show the referrers page.
	 */
	public static function show_referrers(): void {
		self::show_view( 'referrers' );
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

		$query = "SELECT `created` AS `date`, COUNT(*) AS `count` FROM `$wpdb->statify`";
		$where = array();
		$args  = array();

		if ( $single_year > 0 ) {
			$where[] = 'YEAR(`created`) = %d';
			$args[]  = $single_year;
		}

		if ( ! empty( $post_url ) ) {
			$where[] = '`target` = %s';
			$args[]  = $post_url;
		}

		if ( ! empty( $where ) ) {
			$query .= ' WHERE ' . implode( ' AND ', $where );
		}

		$query .= ' GROUP BY `created` ORDER BY `created`';

		if ( ! empty( $args ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is dynamic but does not contain user input.
			$query = $wpdb->prepare( $query, $args );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is either prepared or does not contain any placeholders.
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
				"SELECT DATE_FORMAT(`created`, '%Y-%m') AS `date`, COUNT(*) AS `count`" .
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
					"SELECT DATE_FORMAT(`created`, '%Y-%m') AS `date`, COUNT(*) AS `count`
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
				'SELECT YEAR(`created`) AS `date`, COUNT(*) AS `count`' .
				" FROM `$wpdb->statify`" .
				' GROUP BY `date`',
				ARRAY_A
			);
		} else {
			// Only for selected posts.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT YEAR(`created`) AS `date`, COUNT(*) AS `count`' .
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
				'SELECT COUNT(`target`) AS `count`, `target` AS `url`' .
				" FROM `$wpdb->statify`" .
				' WHERE `target` IS NOT NULL' .
				' GROUP BY `target`' .
				' ORDER BY `count` DESC',
				ARRAY_A
			);
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT COUNT(`target`) AS `count`, `target` AS `url`' .
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

		$stmt = 'SELECT COUNT(`referrer`) AS `count`, `referrer` AS `url`,' .
			" SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(LEADING 'www.' FROM(TRIM(LEADING 'https://' FROM TRIM(LEADING 'http://' FROM TRIM(`referrer`))))), '/', 1), ':', 1) AS `host`" .
			" FROM `$wpdb->statify`" .
			' WHERE ' . $where .
			' GROUP BY `host`' .
			' ORDER BY `count` DESC';

		if ( ! empty( $param ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is dynamic but does not contain user input.
			$stmt = $wpdb->prepare( $stmt, $param );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is either prepared or does not contain any placeholders.
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

	/**
	 * Get post title by URL.
	 *
	 * @param string|null $url Target URL.
	 *
	 * @return string Post title, fallback to URL of not available.
	 */
	public static function post_title( ?string $url ): string {
		if ( empty( $url ) ) {
			$title = __( 'all posts', 'statify' );
		} elseif ( '/' === $url ) {
			$title = __( 'Home Page', 'statify' );
		} else {
			$post_id = url_to_postid( $url );
			if ( 0 === $post_id ) {
				$title = esc_url( $url );
			} else {
				$title = get_the_title( $post_id );
			}
		}

		return $title;
	}
}
