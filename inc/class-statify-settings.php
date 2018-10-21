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
			'statify-global'
		);
		add_settings_field(
			'statify-snippet',
			__( 'Tracking via JavaScript', 'statify' ),
			array( __CLASS__, 'options_snippet' ),
			'statify',
			'statify-global'
		);

		// Dashboard widget settings.
		add_settings_section(
			'statify-dashboard',
			__( 'Dashboard Widget', 'statify' ),
			array( __CLASS__, 'header_dashboard' ),
			'statify'
		);
		add_settings_field(
			'statify-limit',
			__( 'Number of entries in top lists', 'statify' ),
			array( __CLASS__, 'options_limit' ),
			'statify',
			'statify-dashboard'
		);
		add_settings_field(
			'statify-today',
			__( 'Top lists only for today', 'statify' ),
			array( __CLASS__, 'options_today' ),
			'statify',
			'statify-dashboard'
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
			'statify-skip'
		);
		add_settings_field(
			'statify-skip-logged_in',
			__( 'Logged in users', 'statify' ),
			array( __CLASS__, 'options_skip_logged_in' ),
			'statify',
			'statify-skip'
		);
		add_settings_field(
			'statify-skip-feed',
			__( 'Feed', 'statify' ),
			array( __CLASS__, 'options_skip_feed' ),
			'statify',
			'statify-skip'
		);
		add_settings_field(
			'statify-skip-search',
			__( 'Search requests', 'statify' ),
			array( __CLASS__, 'options_skip_search' ),
			'statify',
			'statify-skip'
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
		<label for="statify-days"><?php esc_html_e( 'days', 'statify' ); ?></label>
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
		<input type="checkbox" name="statify[snippet]" id="statify_snippet" value="1" <?php checked( Statify::$_options['snippet'], 1 ); ?>
			title="<?php esc_attr_e( 'Tracking via JavaScript', 'statify' ); ?>">
		(<?php esc_html_e( 'Default', 'statify' ); ?>: <?php esc_html_e( 'No' ); ?>)
		<br>
		<p class="description"><?php esc_html_e( 'This option is strongly recommended if caching is in use.', 'statify' ); ?></p>
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
	 * Option for number of entries in top lists.
	 *
	 * @return void
	 */
	public static function options_limit() {
		?>
		<input id="statify-limit" name="statify[limit]" type="number" min="1" max="100" value="<?php echo esc_attr( Statify::$_options['limit'] ); ?>"
			title="<?php esc_attr_e( 'Number of entries in top lists', 'statify' ); ?>">
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
		<input  id="statify-today" type="checkbox" name="statify[today]" value="1" <?php checked( Statify::$_options['today'], 1 ); ?>
			title="<?php esc_attr_e( 'Entries in top lists only for today', 'statify' ); ?>">
		(<?php esc_html_e( 'Default', 'statify' ); ?>: <?php esc_html_e( 'No' ); ?>)
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
		<input type="checkbox" name="statify[blacklist]" value="1"<?php checked( Statify::$_options['blacklist'] ); ?>
			title="<?php esc_attr_e( 'Skip tracking for referrers listed in the comment blacklist', 'statify' ); ?>">
		(<?php esc_html_e( 'Default', 'statify' ); ?>: <?php esc_html_e( 'No' ); ?>)
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
		<input type="checkbox" name="statify[skip][logged_in]" value="1"<?php checked( Statify::$_options['skip']['logged_in'] ); ?>
			title="<?php esc_attr_e( 'Skip tracking for logged in users', 'statify' ); ?>">
		(<?php esc_html_e( 'Default', 'statify' ); ?>: <?php esc_html_e( 'Yes' ); ?>)
		<br>
		<p class="description"><?php esc_html_e( 'Enabling this option excludes any views of logged-in users from tracking.', 'statify' ); ?></p>
		<?php
	}

	/**
	 * Option to skip tracking for feed access.
	 *
	 * @return void
	 */
	public static function options_skip_feed() {
		?>
		<input type="checkbox" name="statify[skip][feed]" value="1"<?php checked( Statify::$_options['skip']['feed'] ); ?>
			title="<?php esc_attr_e( 'Skip tracking for feed access', 'statify' ); ?>">
		(<?php esc_html_e( 'Default', 'statify' ); ?>: <?php esc_html_e( 'Yes' ); ?>)
		<br>
		<p class="description"><?php esc_html_e( 'Enabling this option excludes all requests to feeds (RSS, Atom, etc.) from tracking.', 'statify' ); ?></p>
		<?php
	}

	/**
	 * Option to skip tracking for search requests.
	 *
	 * @return void
	 */
	public static function options_skip_search() {
		?>
		<input type="checkbox" name="statify[skip][search]" value="1"<?php checked( Statify::$_options['skip']['search'] ); ?>
			title="<?php esc_attr_e( 'Skip tracking for search requests', 'statify' ); ?>">
		(<?php esc_html_e( 'Default', 'statify' ); ?>: <?php esc_html_e( 'Yes' ); ?>)
		<br>
		<p class="description"><?php esc_html_e( 'Enabling this option excludes search result pages from tracking.', 'statify' ); ?></p>
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
		foreach ( array( 'days', 'limit' ) as $o ) {
			$res[ $o ] = Statify::$_options[ $o ];
			if ( isset( $options[ $o ] ) && (int) $options[ $o ] > 0 ) {
				$res[ $o ] = (int) $options[ $o ];
			}
		}
		if ( $res['limit'] > 100 ) {
			$res['limit'] = 100;
		}

		// Get checkbox values.
		foreach ( array( 'today', 'snippet', 'blacklist' ) as $o ) {
			$res[ $o ] = isset( $options[ $o ] ) && 1 === (int) $options[ $o ] ? 1 : 0;
		}
		foreach ( array( 'logged_in', 'feed', 'search' ) as $o ) {
			$res['skip'][ $o ] = isset( $options['skip'][ $o ] ) && 1 === (int) $options['skip'][ $o ] ? 1 : 0;
		}

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
			</form>
		</div>

		<?php
	}

}
