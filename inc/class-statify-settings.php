<?php
/**
 * Statify: Statify_Settings class
 *
 * This file contains the plugin's settings capabilities.
 *
 * @package   Statify
 * @since     1.7
 */

// Quit ic accessed directly..
defined( 'ABSPATH' ) || exit;

/**
 * Class Statify_Settings
 *
 * @since 1.7
 */
class Statify_Settings {

	/**
	 * Registers all options using the WP Settings API.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting( 'statify', 'statify', array( __CLASS__, 'sanitize_options' ) );

		// Global settings.
		add_settings_section(
			'statify-global',
			__( 'Global settings', 'statify' ),
			null,
			'statify'
		);
		add_settings_field(
			'statify-days',
			__( 'Period of data saving', 'statify' ),
			array( __CLASS__, 'options_days' ),
			'statify',
			'statify-global',
			array( 'label_for' => 'statify-days' )
		);
		add_settings_field(
			'statify-snippet',
			__( 'Tracking via JavaScript', 'statify' ),
			array( __CLASS__, 'options_snippet' ),
			'statify',
			'statify-global',
			array( 'label_for' => 'statify-snippet' )
		);

		// Dashboard widget settings.
		add_settings_section(
			'statify-dashboard',
			__( 'Dashboard Widget', 'statify' ),
			array( __CLASS__, 'header_dashboard' ),
			'statify'
		);
		add_settings_field(
			'statify-days_show',
			__( 'Period of data display in Dashboard', 'statify' ),
			array( __CLASS__, 'options_days_show' ),
			'statify',
			'statify-dashboard',
			array( 'label_for' => 'statify-days-show' )
		);
		add_settings_field(
			'statify-limit',
			__( 'Number of entries in top lists', 'statify' ),
			array( __CLASS__, 'options_limit' ),
			'statify',
			'statify-dashboard',
			array( 'label_for' => 'statify-limit' )
		);
		add_settings_field(
			'statify-today',
			__( 'Top lists only for today', 'statify' ),
			array( __CLASS__, 'options_today' ),
			'statify',
			'statify-dashboard',
			array( 'label_for' => 'statify-today' )
		);
		add_settings_field(
			'statify-show-totals',
			__( 'Show totals', 'statify' ),
			array( __CLASS__, 'options_show_totals' ),
			'statify',
			'statify-dashboard',
			array( 'label_for' => 'statify-show-totals' )
		);

		// Exclusion settings.
		add_settings_section(
			'statify-skip',
			__( 'Skip tracking for ...', 'statify' ),
			array( __CLASS__, 'header_skip' ),
			'statify'
		);
		add_settings_field(
			'statify-skip-referrer',
			__( 'Blacklisted referrers', 'statify' ),
			array( __CLASS__, 'options_skip_blacklist' ),
			'statify',
			'statify-skip',
			array( 'label_for' => 'statify-skip-referrer' )
		);
		add_settings_field(
			'statify-skip-logged_in',
			__( 'Logged in users', 'statify' ),
			array( __CLASS__, 'options_skip_logged_in' ),
			'statify',
			'statify-skip',
			array( 'label_for' => 'statify-skip-logged_in' )
		);
	}

	/**
	 * Option for data collection period.
	 *
	 * @return void
	 */
	public static function options_days() {
		?>
		<input id="statify-days" name="statify[days]" type="number" min="1" value="<?php echo esc_attr( Statify::$_options['days'] ); ?>">
		<?php esc_html_e( 'days', 'statify' ); ?>
		(<?php esc_html_e( 'Default', 'statify' ); ?>: 14)
		<?php
	}

	/**
	 * Option for tracking via JS.
	 *
	 * @return void
	 */
	public static function options_snippet() {
		?>
		<input id="statify-snippet" type="checkbox" name="statify[snippet]" value="1" <?php checked( Statify::$_options['snippet'], 1 ); ?>>
		(<?php esc_html_e( 'Default', 'statify' ); ?>: <?php esc_html_e( 'No', 'statify' ); ?>)
		<br>
		<p class="description"><?php esc_html_e( 'This option is strongly recommended if caching or AMP is in use.', 'statify' ); ?></p>
		<?php
	}

	/**
	 * Section header for "Dashboard Widget" section.
	 *
	 * @return void
	 */
	public static function header_dashboard() {
		?>
		<p>
			<?php esc_html_e( 'The following options affect the admin dashboard widget.', 'statify' ); ?>
		</p>
		<?php
	}

	/**
	 * Option for data display period.
	 *
	 * @return void
	 */
	public static function options_days_show() {
		?>
		<input id="statify-days-show" name="statify[days_show]" type="number" min="1" value="<?php echo esc_attr( Statify::$_options['days_show'] ); ?>">
		<?php esc_html_e( 'days', 'statify' ); ?>
		(<?php esc_html_e( 'Default', 'statify' ); ?>: 14)
		<?php
	}

	/**
	 * Option for number of entries in top lists.
	 *
	 * @return void
	 */
	public static function options_limit() {
		?>
		<input id="statify-limit" name="statify[limit]" type="number" min="1" max="100" value="<?php echo esc_attr( Statify::$_options['limit'] ); ?>">
		(<?php esc_html_e( 'Default', 'statify' ); ?>: 3)
		<?php
	}

	/**
	 * Option for number of entries in top lists.
	 *
	 * @return void
	 */
	public static function options_today() {
		?>
		<input  id="statify-today" type="checkbox" name="statify[today]" value="1" <?php checked( Statify::$_options['today'], 1 ); ?>>
		(<?php esc_html_e( 'Default', 'statify' ); ?>: <?php esc_html_e( 'No', 'statify' ); ?>)
		<?php
	}

	/**
	 * Option for showing visit totals.
	 *
	 * @return void
	 */
	public static function options_show_totals() {
		?>
		<input  id="statify-show-totals" type="checkbox" name="statify[show_totals]" value="1" <?php checked( Statify::$_options['show_totals'], 1 ); ?>>
		(<?php esc_html_e( 'Default', 'statify' ); ?>: <?php esc_html_e( 'No', 'statify' ); ?>)
		<?php
	}

	/**
	 * Section header for "Skip tracking for..." section.
	 *
	 * @return void
	 */
	public static function header_skip() {
		?>
		<p>
			<?php echo wp_kses( __( 'The following options define cases in which a view will <strong>not</strong> be tracked.', 'statify' ), array( 'strong' => array() ) ); ?>
		</p>
		<?php
	}

	/**
	 * Option to skip tracking for blacklisted referrers.
	 *
	 * @return void
	 */
	public static function options_skip_blacklist() {
		?>
		<input id="statify-skip-referrer" type="checkbox" name="statify[blacklist]" value="1"<?php checked( Statify::$_options['blacklist'] ); ?>>
		(<?php esc_html_e( 'Default', 'statify' ); ?>: <?php esc_html_e( 'No', 'statify' ); ?>)
		<br>
		<p class="description"><?php esc_html_e( 'Enabling this option excludes any views with referrers listed in the comment blacklist', 'statify' ); ?>.</p>
		<?php
	}

	/**
	 * Option to skip tracking for logged in uses.
	 *
	 * @return void
	 */
	public static function options_skip_logged_in() {
		?>
		<input id="statify-skip-logged_in" type="checkbox" name="statify[skip][logged_in]" value="1"<?php checked( Statify::$_options['skip']['logged_in'] ); ?>>
		(<?php esc_html_e( 'Default', 'statify' ); ?>: <?php esc_html_e( 'Yes', 'statify' ); ?>)
		<br>
		<p class="description"><?php esc_html_e( 'Enabling this option excludes any views of logged-in users from tracking.', 'statify' ); ?></p>
		<?php
	}

	/**
	 * Validate and sanitize submitted options.
	 *
	 * @param array $options Original options.
	 *
	 * @return array Validated and sanitized options.
	 */
	public static function sanitize_options( $options ) {

		// Sanitize numeric values.
		$res = array();
		foreach ( array( 'days', 'days_show', 'limit' ) as $o ) {
			$res[ $o ] = Statify::$_options[ $o ];
			if ( isset( $options[ $o ] ) && (int) $options[ $o ] > 0 ) {
				$res[ $o ] = (int) $options[ $o ];
			}
		}
		if ( $res['limit'] > 100 ) {
			$res['limit'] = 100;
		}

		// Get checkbox values.
		foreach ( array( 'today', 'snippet', 'blacklist', 'show_totals' ) as $o ) {
			$res[ $o ] = isset( $options[ $o ] ) && 1 === (int) $options[ $o ] ? 1 : 0;
		}
		$res['skip']['logged_in'] = isset( $options['skip']['logged_in'] ) && 1 === (int) $options['skip']['logged_in'] ? 1 : 0;

		return $res;
	}

	/**
	 * Creates a menu entry in the settings menu.
	 *
	 * @return void
	 */
	public static function add_admin_menu() {
		add_options_page(
			__( 'Statify', 'statify' ),
			__( 'Statify', 'statify' ),
			'manage_options',
			'statify-settings',
			array( __CLASS__, 'create_settings_page' )
		);
	}

	/**
	 * Creates the settings pages.
	 *
	 * @return void
	 */
	public static function create_settings_page() {
		?>

		<div class="wrap">
			<h1><?php esc_html_e( 'Statify Settings', 'statify' ); ?></h1>

			<form id="statify-settings" method="post" action="options.php">
				<?php
				settings_fields( 'statify' );
				do_settings_sections( 'statify' );
				submit_button();
				?>
				<p class="alignright">
					<a href="<?php echo esc_url( __( 'https://wordpress.org/plugins/statify/', 'statify' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Documentation', 'statify' ); ?></a>
					&bull; <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TD4AMD2D8EMZW" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Donate', 'statify' ); ?></a>
					&bull; <a href="<?php echo esc_url( __( 'https://wordpress.org/support/plugin/statify', 'statify' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Support', 'statify' ); ?></a>
				</p>

			</form>
		</div>

		<?php
	}

}
