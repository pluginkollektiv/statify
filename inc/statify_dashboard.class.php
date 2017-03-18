<?php
/** Quit */
defined( 'ABSPATH' ) || exit;

/**
 * Statify_Dashboard
 *
 * @since 1.1
 */
class Statify_Dashboard extends Statify {

	/**
	 * Plugin version.
	 *
	 * @since  1.4.0
	 * @var    string
	 */
	protected static $_plugin_version;

	/**
	 * Dashboard widget initialize
	 *
	 * @since   0.1.0
	 * @version 2016-12-21
	 *
	 * @wp-hook boolean  statify__user_can_see_stats
	 * @see     https://github.com/pluginkollektiv/statify/wiki/en-Hooks#statify__user_can_see_stats
	 */
	public static function init() {

		/** Filter user_can_see_stats */
		if ( ! apply_filters( 'statify__user_can_see_stats', current_user_can( 'edit_dashboard' ) ) ) {
			return;
		}

		/** Load textdomain */
		load_plugin_textdomain(
			'statify',
			false,
			wp_normalize_path( sprintf( '%s/lang', STATIFY_DIR ) )
		);

		/** Plugin version */
		self::_get_version();

		/** Add dashboard widget */
		wp_add_dashboard_widget(
			'statify_dashboard',
			'Statify',
			array( __CLASS__, 'print_frontview' ),
			array( __CLASS__, 'print_backview' )
		);

		/** Init CSS */
		add_action( 'admin_print_styles', array( __CLASS__, 'add_style' ) );

		/** Init JS */
		add_action( 'admin_print_scripts', array( __CLASS__, 'add_js' ) );
	}

	/**
	 * Print CSS
	 *
	 * @since   0.1.0
	 * @version 1.4.0
	 */
	public static function add_style() {

		/** Register CSS */
		wp_register_style(
			'statify',
			plugins_url( '/css/dashboard.min.css', STATIFY_FILE ),
			array(),
			self::$_plugin_version
		);

		/** Load CSS */
		wp_enqueue_style( 'statify' );
	}

	/**
	 * Print JavaScript
	 *
	 * @since    0.1.0
	 * @version  1.4.0
	 */
	public static function add_js() {

		/** Register JS */
		wp_register_script(
			'raphael',
			plugins_url( 'js/raphael.min.js', STATIFY_FILE ),
			array(),
			self::$_plugin_version,
			true
		);
		wp_register_script(
			'sm_raphael_helper',
			plugins_url( 'js/raphael.helper.min.js', STATIFY_FILE ),
			array( 'raphael' ),
			self::$_plugin_version,
			true
		);
		wp_register_script(
			'statify_chart_js',
			plugins_url(
				'js/dashboard.min.js',
				STATIFY_FILE
			),
			array( 'jquery', 'sm_raphael_helper' ),
			self::$_plugin_version,
			true
		);

		/** Localize strings */
		wp_localize_script(
			'statify_chart_js',
			'statify_translations',
			array(
				'pageview'  => strip_tags( esc_html__( 'Pageview', 'statify' ) ),
				'pageviews' => strip_tags( esc_html__( 'Pageviews', 'statify' ) ),
			)
		);
	}


	/**
	 * Print widget frontview.
	 *
	 * @since    0.1.0
	 * @version  1.4.0
	 */
	public static function print_frontview() {

		/* Load JS */
		wp_enqueue_script( 'sm_raphael_js' );
		wp_enqueue_script( 'sm_raphael_helper' );
		wp_enqueue_script( 'statify_chart_js' );

		/* Load template */
		load_template(
			wp_normalize_path( sprintf( '%s/views/widget_front.view.php', STATIFY_DIR ) )
		);
	}


	/**
	 * Print widget backview
	 *
	 * @since    0.4.0
	 * @version  1.4.0
	 */
	public static function print_backview() {

		/* Capability check */
		if ( ! current_user_can( 'edit_dashboard' ) ) {
			return;
		}

		/** Update plugin options */
		if ( ! empty( $_POST[ 'statify' ] ) ) {
			self::_save_options();
		}

		/* Load view */
		load_template(
			wp_normalize_path( sprintf( '%s/views/widget_back.view.php', STATIFY_DIR ) )
		);
	}


	/**
	 * Save plugin options
	 *
	 * @since    1.4.0
	 * @version  2017-01-10
	 */
	private static function _save_options() {
		/** Check the nonce field from the dashboard form. */
		if ( ! check_admin_referer( 'statify-dashboard' ) ) {
			return;
		}

		/** Get numeric values from POST variables */
		$options = array();
		foreach ( array( 'days', 'limit' ) as $option_name ) {
			if ( isset( $_POST['statify'][ $option_name ] ) && intval( $_POST['statify'][ $option_name ] ) > 0 ) {
				$options[ $option_name ] = intval( $_POST['statify'][ $option_name ] );
			} else {
				$options[ $option_name ] = Statify::$_options[ $option_name ];
			}
		}
		if ( intval( $options['limit'] ) > 100 ) {
			$options['limit'] = 100;
		}
		
		/** Get checkbox values from POST variables */
		foreach ( array( 'today', 'snippet', 'blacklist' ) as $option_name ) {
			if ( isset( $_POST['statify'][ $option_name ] ) && intval( $_POST['statify'][ $option_name ] ) === 1 ) {
				$options[ $option_name ] = 1;
			} else {
				$options[ $option_name ] = 0;
			}
		}

		/** Update values */
		update_option( 'statify', $options );

		/** Delete transient */
		delete_transient( 'statify_data' );

		/** Clear Cachify cache */
		if ( has_action( 'cachify_flush_cache' ) ) {
			do_action( 'cachify_flush_cache' );
		}
	}


	/**
	 * Set plugin version from plugin meta data
	 *
	 * @since    1.4.0
	 * @version  1.4.0
	 */
	private static function _get_version() {

		/* Get plugin meta */
		$meta = get_plugin_data( STATIFY_FILE );

		self::$_plugin_version = $meta['Version'];
	}


	/**
	 * Get stats from cache
	 *
	 * @since   0.1.0
	 * @version 1.4.0
	 *
	 * @return  array  $data  Data from cache or DB
	 */
	public static function get_stats() {

		/** Get from cache */
		if ( $data = get_transient( 'statify_data' ) ) {
			return $data;
		}

		/** Get from DB */
		$data = self::_select_data();

		/** Prepare data */
		if ( ! empty( $data['visits'] ) ) {
			$data['visits'] = array_reverse( $data['visits'] );
		} else {
			$data = null;
		}

		/** Make cache */
		set_transient(
			'statify_data',
			$data,
			MINUTE_IN_SECONDS * 4
		);

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
	private static function _select_data() {

		/** Global */
		global $wpdb;

		/** Init values */
		$days  = (int) self::$_options['days'];
		$limit = (int) self::$_options['limit'];
		$today = (int) self::$_options['today'];

		return array(
			'visits'   => $wpdb->get_results(
				$wpdb->prepare(
					"SELECT `created` as `date`, COUNT(`created`) as `count` FROM `$wpdb->statify` GROUP BY `created` ORDER BY `created` DESC LIMIT %d",
					$days
				),
				ARRAY_A
			),
			'target'   => $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COUNT(`target`) as `count`, `target` as `url` FROM `$wpdb->statify` " . ( $today ? 'WHERE created = DATE(NOW())' : '' ) . " GROUP BY `target` ORDER BY `count` DESC LIMIT %d",
					$limit
				),
				ARRAY_A
			),
			'referrer' => $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COUNT(`referrer`) as `count`, `referrer` as `url`, SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(LEADING 'www.' FROM(TRIM(LEADING 'https://' FROM TRIM(LEADING 'http://' FROM TRIM(`referrer`))))), '/', 1), ':', 1) as `host` FROM `$wpdb->statify` WHERE `referrer` != '' " . ( $today ? 'AND created = DATE(NOW())' : '' ) . " GROUP BY `host` ORDER BY `count` DESC LIMIT %d",
					$limit
				),
				ARRAY_A
			)
		);
	}
}
