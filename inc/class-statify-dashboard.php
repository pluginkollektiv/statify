<?php
/**
 * Statify: Statify_Dashboard class
 *
 * This file contains the derived class for the plugin's dashboard features.
 *
 * @package   Statify
 * @since     1.1
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * Statify_Dashboard
 *
 * @since 1.1
 */
class Statify_Dashboard extends Statify {

	/**
	 * Dashboard widget initialize
	 *
	 * @since   0.1.0
	 */
	public static function init(): void {

		// Filter user_can_see_stats.
		if ( ! self::user_can_see_stats() ) {
			return;
		}

		// Load textdomain.
		load_plugin_textdomain(
			'statify',
			false,
			wp_normalize_path( sprintf( '%s/lang', STATIFY_DIR ) )
		);

		// Add dashboard widget.
		wp_add_dashboard_widget(
			'statify_dashboard',
			'Statify',
			array( __CLASS__, 'print_frontview' ),
			array( __CLASS__, 'print_backview' )
		);

		// Init CSS.
		add_action( 'admin_print_styles', array( 'Statify', 'add_style' ) );

		// Init JS.
		add_action( 'admin_print_scripts', array( 'Statify', 'add_js' ) );
	}

	/**
	 * Print widget frontview.
	 *
	 * @since    0.1.0
	 * @version  1.4.0
	 */
	public static function print_frontview(): void {

		// Load JS.
		wp_enqueue_script( 'chartist_js' );
		wp_enqueue_script( 'statify_chart_js' );

		// Load template.
		load_template(
			wp_normalize_path( sprintf( '%s/views/widget-front.php', STATIFY_DIR ) )
		);
	}


	/**
	 * Print widget backview
	 *
	 * @since    0.4.0
	 * @version  1.4.0
	 */
	public static function print_backview(): void {

		// Capability check.
		if ( ! current_user_can( 'edit_dashboard' ) ) {
			return;
		}

		// Update plugin options.
		if ( ! empty( $_POST['statify'] ) ) {
			check_admin_referer( 'statify-dashboard' );

			self::save_widget_options();
		}

		// Load view.
		load_template(
			wp_normalize_path( sprintf( '%s/views/widget-back.php', STATIFY_DIR ) )
		);
	}


	/**
	 * Save dashboard widget options.
	 *
	 * @since    1.4.0
	 * @sicnce   1.7.0 Renamed to _save_widget_options()
	 *
	 * @return void
	 */
	private static function save_widget_options(): void {
		// Check the nonce field from the dashboard form.
		if ( ! check_admin_referer( 'statify-dashboard' ) ) {
			return;
		}

		// We only do a partial update, so initialize with current values.
		$options = Statify::$options;

		// Parse numeric values.
		foreach ( array( 'days', 'days_show', 'limit' ) as $option_name ) {
			$options[ $option_name ] = Statify::$options[ $option_name ];
			if ( isset( $_POST['statify'][ $option_name ] ) && (int) $_POST['statify'][ $option_name ] > 0 ) {
				$options[ $option_name ] = (int) $_POST['statify'][ $option_name ];
			}
		}
		if ( $options['limit'] > 100 ) {
			$options['limit'] = 100;
		}

		// Parse "today" checkbox.
		if ( isset( $_POST['statify']['today'] ) && 1 === (int) $_POST['statify']['today'] ) {
			$options['today'] = 1;
		} else {
			$options['today'] = 0;
		}

		// Parse "show totals" checkbox.
		if ( isset( $_POST['statify']['show_totals'] ) && 1 === (int) $_POST['statify']['show_totals'] ) {
			$options['show_totals'] = 1;
		} else {
			$options['show_totals'] = 0;
		}

		// Update values.
		update_option( 'statify', $options );
	}

	/**
	 * Get stats from cache
	 *
	 * @since   0.1.0
	 * @version 1.4.0
	 *
	 * @param bool $force_refresh If true, the data is recalculated, otherwise the cached value is used if available.
	 *
	 * @return  array  $data  stats data from cache or database
	 */
	public static function get_stats( bool $force_refresh = false ): array {

		// Get from cache if enabled.
		if ( ! $force_refresh ) {
			$data_from_cache = get_transient( 'statify_data' );
			if ( $data_from_cache ) {
				return $data_from_cache;
			}
		}

		// Get from DB.
		$data = self::select_data();

		// Store in cache.
		if ( ! empty( $data['visits'] ) ) {
			set_transient(
				'statify_data',
				$data,
				MINUTE_IN_SECONDS * 15
			);
		}

		return $data;
	}


	/**
	 * Get stats from DB
	 *
	 * @since    0.1.0
	 * @version  1.4.0
	 *
	 * @return  array  DB results
	 */
	private static function select_data(): array {

		// Global.
		global $wpdb;

		// Init values.
		$days_show   = (int) self::$options['days_show'];
		$limit       = (int) self::$options['limit'];
		$today       = (int) self::$options['today'];
		$show_totals = (int) self::$options['show_totals'];

		$current_date = current_time( 'Y-m-d' );

		$data = array(
			'visits'   => self::fill_gaps(
				$wpdb->get_results(
					$wpdb->prepare(
						"SELECT `created` as `date`, COUNT(*) as `count` FROM `$wpdb->statify` WHERE `created` BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE() GROUP BY `created` ORDER BY `created` ASC",
						$days_show - 1
					),
					ARRAY_A
				),
				$days_show
			),
		);

		if ( $today ) {
			$data['target'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COUNT(`target`) as `count`, `target` as `url` FROM `$wpdb->statify` WHERE created = %s GROUP BY `target` ORDER BY `count` DESC, `url` ASC LIMIT %d",
					$current_date,
					$limit
				),
				ARRAY_A
			);
			$data['referrer'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COUNT(`referrer`) as `count`, `referrer` as `url`, SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(LEADING 'www.' FROM(TRIM(LEADING 'https://' FROM TRIM(LEADING 'http://' FROM TRIM(`referrer`))))), '/', 1), ':', 1) as `host` FROM `$wpdb->statify` WHERE `referrer` != '' AND created = %s GROUP BY `host` ORDER BY `count` DESC, `url` ASC LIMIT %d",
					$current_date,
					$limit
				),
				ARRAY_A
			);
		} else {
			$data['target'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COUNT(`target`) as `count`, `target` as `url` FROM `$wpdb->statify` WHERE created > DATE_SUB(%s, INTERVAL %d DAY) GROUP BY `target` ORDER BY `count` DESC, `url` ASC LIMIT %d",
					$current_date,
					$days_show,
					$limit
				),
				ARRAY_A
			);
			$data['referrer'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COUNT(`referrer`) as `count`, `referrer` as `url`, SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(LEADING 'www.' FROM(TRIM(LEADING 'https://' FROM TRIM(LEADING 'http://' FROM TRIM(`referrer`))))), '/', 1), ':', 1) as `host` FROM `$wpdb->statify` WHERE `referrer` != '' AND created > DATE_SUB(%s, INTERVAL %d DAY) GROUP BY `host` ORDER BY `count` DESC, `url` ASC LIMIT %d",
					$current_date,
					$days_show,
					$limit
				),
				ARRAY_A
			);
		}

		if ( $show_totals ) {
			$data['visit_totals'] = array(
				'today'           => $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`created`) FROM `$wpdb->statify` WHERE created = %s",
						$current_date
					)
				),
				'since_beginning' => $wpdb->get_row(
					"SELECT COUNT(`created`) AS `count`, MIN(`created`) AS `date` FROM `$wpdb->statify`",
					ARRAY_A
				),
			);
		}

		return $data;
	}

	/**
	 * Fill gaps in statistics data array.
	 * We only fill gaps inbeteen datapoints and between the latest datapoint and "today".
	 *
	 * @param array $data {
	 *     Data from database.
	 *
	 *     @type string $date  The date (Y-m-d).
	 *     @type int    $count Count.
	 * }
	 * @param int   $days Number of days to show.
	 * @return array Array with filled gaps.
	 */
	private static function fill_gaps( array $data, int $days ): array {
		if ( empty( $data ) || count( $data ) == $days ) {
			return $data;
		}

		$result = array();
		$lookup = array_column( $data, 'count', 'date' );
		$append = false;
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$date = ( new DateTime( "-$i days" ) )->format( 'Y-m-d' );
			if ( $append || isset( $lookup[ $date ] ) ) {
				$append   = true;
				$result[] = array(
					'date'  => $date,
					'count' => intval( $lookup[ $date ] ?? 0 ),
				);
			}
		}

		return $result;
	}
}
