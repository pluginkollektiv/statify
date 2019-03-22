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
	 * @see     https://wordpress.org/plugins/statify/
	 */
	public static function init() {

		// Filter user_can_see_stats.
		if ( ! apply_filters( 'statify__user_can_see_stats', current_user_can( 'edit_dashboard' ) ) ) {
			return;
		}

		// Check if user can edit the widget.
		$can_edit = apply_filters( 'statify__user_can_see_stats', current_user_can( 'edit_dashboard' ) );

		// Load textdomain.
		load_plugin_textdomain(
			'statify',
			false,
			wp_normalize_path( sprintf( '%s/lang', STATIFY_DIR ) )
		);

		// Plugin version.
		self::_get_version();

		// Add dashboard widget.
		wp_add_dashboard_widget(
			'statify_dashboard',
			'Statify',
			array( __CLASS__, 'print_frontview' ),
			$can_edit ? array( __CLASS__, 'print_backview' ) : null
		);

		// Init CSS.
		add_action( 'admin_print_styles', array( __CLASS__, 'add_style' ) );

		// Init JS.
		add_action( 'admin_print_scripts', array( __CLASS__, 'add_js' ) );
	}

	/**
	 * Print CSS
	 *
	 * @since   0.1.0
	 * @version 1.4.0
	 */
	public static function add_style() {

		// Register CSS.
		wp_register_style(
			'chartist_css',
			plugins_url( '/css/chartist.min.css', STATIFY_FILE ),
			array(),
			self::$_plugin_version
		);
		wp_register_style(
			'chartist_tooltip_css',
			plugins_url( '/css/chartist-plugin-tooltip.min.css', STATIFY_FILE ),
			array(),
			self::$_plugin_version
		);
		wp_register_style(
			'statify',
			plugins_url( '/css/dashboard.min.css', STATIFY_FILE ),
			array(),
			self::$_plugin_version
		);

		// Load CSS.
		wp_enqueue_style( 'chartist_css' );
		wp_enqueue_style( 'chartist_tooltip_css' );
		wp_enqueue_style( 'statify' );
	}

	/**
	 * Print JavaScript
	 *
	 * @since    0.1.0
	 * @version  1.4.0
	 */
	public static function add_js() {

		// Register JS.
		wp_register_script(
			'chartist_js',
			plugins_url( 'js/chartist.min.js', STATIFY_FILE ),
			array(),
			self::$_plugin_version,
			true
		);
		wp_register_script(
			'chartist_tooltip_js',
			plugins_url( 'js/chartist-plugin-tooltip.min.js', STATIFY_FILE ),
			array( 'chartist_js' ),
			self::$_plugin_version,
			true
		);
		wp_register_script(
			'statify_chart_js',
			plugins_url(
				'js/dashboard.min.js',
				STATIFY_FILE
			),
			array( 'jquery', 'chartist_tooltip_js' ),
			self::$_plugin_version,
			true
		);

		// Localize strings.
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
	public static function print_backview() {

		// Capability check.
		if ( ! current_user_can( 'edit_dashboard' ) ) {
			return;
		}

		// Update plugin options.
		if ( ! empty( $_POST['statify'] ) ) {
			check_admin_referer( 'statify-dashboard' );

			self::_save_widget_options();
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
	private static function _save_widget_options() {
		// Check the nonce field from the dashboard form.
		if ( ! check_admin_referer( 'statify-dashboard' ) ) {
			return;
		}

		// We only do a partial update, so initialize with current values.
		$options = Statify::$_options;

		// Parse numeric "limit" value.
		if ( isset( $_POST['statify']['limit'] ) && (int) $_POST['statify']['limit'] > 0 ) {
			$options['limit'] = (int) $_POST['statify']['limit'];
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

		// Update values.
		update_option( 'statify', $options );

		// Delete transient.
		delete_transient( 'statify_data' );

		// Clear Cachify cache.
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

		// Get plugin meta.
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

		// Get from cache.
		$data = get_transient( 'statify_data' );
		if ( $data ) {
			return $data;
		}

		// Get from DB.
		$data = self::_select_data();

		// Prepare data.
		if ( ! empty( $data['visits'] ) ) {
			$data['visits'] = array_reverse( $data['visits'] );
		} else {
			$data = null;
		}

		if ( ! empty( $data['target'] ) ) {
			foreach( $data['target'] AS $key => $target ) {
				$title = self::get_title_by_url( $target['url'] );
				$data['target'][ $key ]['title'] = $title;
			}
		}

		// Make cache.
		set_transient(
			'statify_data',
			$data,
			MINUTE_IN_SECONDS * 4
		);

		return $data;
	}

	/**
	 * Getting title by title.
	 * 
	 * @since 1.7.0
	 * 
	 * @param string $url URL to get title from.
	 * 
	 * @return string $title Totle of the given url.
	 */
	private static function get_title_by_url( $url ) {
		if ( '/' === $url ) {
			return __( 'Home Page', 'statify' );
		}

		$post_id = url_to_postid( $url );

		if ( 0 !== $post_id ) {
			return get_the_title( $post_id );
		}

		return esc_url( $url );
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

		// Global.
		global $wpdb;

		// Init values.
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
					"SELECT COUNT(`target`) as `count`, `target` as `url` FROM `$wpdb->statify` " . ( $today ? 'WHERE created = DATE(NOW())' : '' ) . ' GROUP BY `target` ORDER BY `count` DESC LIMIT %d',
					$limit
				),
				ARRAY_A
			),
			'referrer' => $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COUNT(`referrer`) as `count`, `referrer` as `url`, SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(LEADING 'www.' FROM(TRIM(LEADING 'https://' FROM TRIM(LEADING 'http://' FROM TRIM(`referrer`))))), '/', 1), ':', 1) as `host` FROM `$wpdb->statify` WHERE `referrer` != '' " . ( $today ? 'AND created = DATE(NOW())' : '' ) . ' GROUP BY `host` ORDER BY `count` DESC LIMIT %d',
					$limit
				),
				ARRAY_A
			),
		);
	}
}
