<?php
/**
 * Statify: Statify_Settings class
 *
 * This file contains the plugin's settings capabilities.
 *
 * @package   Statify
 * @since     1.7
 */

// Quit if accessed directly..
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
			__( 'Tracking method', 'statify' ),
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
		add_settings_field(
			'statify-show-widget-roles',
			__( 'Which user role(s) should see the Statify dashboard widget?', 'statify' ),
			array( __CLASS__, 'options_show_widget_roles' ),
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
			__( 'Disallowed referrers', 'statify' ),
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
		<p>
			<?php self::show_snippet_option( Statify::TRACKING_METHOD_DEFAULT, __( 'Default tracking', 'statify' ) ); ?>
			<br>
			<?php self::show_snippet_option( Statify::TRACKING_METHOD_JAVASCRIPT_WITH_NONCE_CHECK, __( 'JavaScript based tracking with nonce check', 'statify' ) ); ?>
			<br>
			<?php self::show_snippet_option( Statify::TRACKING_METHOD_JAVASCRIPT_WITHOUT_NONCE_CHECK, __( 'JavaScript based tracking without nonce check', 'statify' ) ); ?>
		</p>
		<p class="description">
			<?php esc_html_e( 'JavaScript based tracking is strongly recommended if caching or AMP is in use.', 'statify' ); ?>
			<?php esc_html_e( 'Disable the nonce check if the caching time is longer than the nonce time or you miss views due to 403 Forbidden errors.', 'statify' ); ?>
		</p>
		<?php
	}

	/**
	 * Outputs the input radio for an option.
	 *
	 * @param int    $value the value for the input radio.
	 * @param string $label the label.
	 */
	private static function show_snippet_option( $value, $label ) {
		?>
			<label>
				<input name="statify[snippet]" type="radio" value="<?php echo esc_html( $value ); ?>" <?php checked( Statify::$_options['snippet'], $value ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
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
	 * Option for excluding roles from seeing the dashboard widget.
	 *
	 * @wp-hook array  statify__available_roles
	 *
	 * @return void
	 */
	public static function options_show_widget_roles() {
		$all_roles = apply_filters( 'statify__available_roles', wp_roles()->roles );

		// Backwards compatibility for older statify versions without this option.
		if ( ! isset( Statify::$_options['show_widget_roles'] ) ) {
			// Loop over all roles to find these with the capability edit_dashboard.
			$saved_roles = array();

			foreach ( $all_roles as $role => $role_object ) {
				$capabilities = $role_object['capabilities'];
				if ( in_array( 'edit_dashboard', array_keys( $capabilities ) ) ) {
					$saved_roles[] = $role;
				}
			}
		} else {
			$saved_roles = Statify::$_options['show_widget_roles'];
		}

		self::show_roles_list( 'show-widget-roles', 'show_widget_roles', $all_roles, $saved_roles );
	}

	/**
	 * Outputs the list of input checkboxes of all available roles.
	 *
	 * @param string $input_id the name for the input element id.
	 * @param string $name the name for the input element name attribute.
	 * @param array  $available_roles the list of all available roles to be listes with checkbox.
	 * @param array  $saved_roles the list of all role names that should be checked.
	 *
	 * @return void
	 */
	private static function show_roles_list( $input_id, $name, $available_roles, $saved_roles = array() ) {
		foreach ( $available_roles as $role => $role_object ) {
			?>
			<p><label for="statify-<?php echo esc_html( $input_id ); ?>-<?php echo esc_html( $role ); ?>">
			<?php
			printf(
				'<input id="statify-%1$s-%2$s" type="checkbox" name="statify[%3$s][]" value="%2$s" %4$s>',
				esc_html( $input_id ),
				esc_html( $role ),
				esc_html( $name ),
				checked( in_array( $role, $saved_roles ), true, false )
			);
			echo esc_html( $role_object['name'] );
			?>
			</label></p>
			<?php
		}
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
	 * Option to skip tracking for disallowed referrers.
	 *
	 * @return void
	 */
	public static function options_skip_blacklist() {
		?>
		<input id="statify-skip-referrer" type="checkbox" name="statify[blacklist]" value="1"<?php checked( Statify::$_options['blacklist'] ); ?>>
		(<?php esc_html_e( 'Default', 'statify' ); ?>: <?php esc_html_e( 'No', 'statify' ); ?>)
		<p class="description"><?php esc_html_e( 'Enabling this option excludes any views with referrers listed in the list of "Disallowed Comment Keys" field in the "Discussion" settings.', 'statify' ); ?></p>
		<?php
	}

	/**
	 * Option to skip tracking for logged in uses.
	 *
	 * @return void
	 */
	public static function options_skip_logged_in() {
		?>
		<select id="statify-skip-logged_in" name="statify[skip][logged_in]">
			<option value="0" <?php selected( Statify::$_options['skip']['logged_in'], 0 ); ?>><?php esc_html_e( 'Track all users', 'statify' ); ?></option>
			<option value="1" <?php selected( Statify::$_options['skip']['logged_in'], 1 ); ?>><?php esc_html_e( 'Skip all users', 'statify' ); ?></option>
			<option value="2" <?php selected( Statify::$_options['skip']['logged_in'], 2 ); ?>><?php esc_html_e( 'Skip administrators', 'statify' ); ?></option>
		</select>
		(<?php esc_html_e( 'Default', 'statify' ); ?>: <?php esc_html_e( 'Skip all users', 'statify' ); ?>)
		<p class="description"><?php esc_html_e( 'This option specified whether logged-in users and/or administrators should be excluded from tracking.', 'statify' ); ?></p>
		<?php
	}

	/**
	 * Action to be triggered after Statify options have been saved.
	 * Delete transient data to refresh the dashboard widget and flushes Cachify cache, if the plugin is available and
	 * JS settings have changed.
	 *
	 * @since 1.7.1
	 *
	 * @param array $old_value The old options value.
	 * @param array $value     The updated options value.
	 *
	 * @return void
	 */
	public static function action_update_options( $old_value, $value ) {
		// Delete transient.
		delete_transient( 'statify_data' );

		// Clear Cachify cache, if JS settings have changed.
		if ( $old_value['snippet'] !== $value['snippet'] && has_action( 'cachify_flush_cache' ) ) {
			do_action( 'cachify_flush_cache' );
		}
	}

	/**
	 * Validate and sanitize submitted options.
	 *
	 * @param array $options Original options.
	 *
	 * @wp-hook array  statify__available_roles
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

		if ( isset( $options['snippet'] ) ) {
			$method = (int) $options['snippet'];
			if ( in_array(
				$method,
				array(
					Statify::TRACKING_METHOD_DEFAULT,
					Statify::TRACKING_METHOD_JAVASCRIPT_WITH_NONCE_CHECK,
					Statify::TRACKING_METHOD_JAVASCRIPT_WITHOUT_NONCE_CHECK,
				),
				true
			) ) {
				$res['snippet'] = $method;
			}
		}

		// Get checkbox values.
		foreach ( array( 'today', 'blacklist', 'show_totals' ) as $o ) {
			$res[ $o ] = isset( $options[ $o ] ) && 1 === (int) $options[ $o ] ? 1 : 0;
		}

		if ( isset( $options['skip']['logged_in'] ) ) {
			$skip_logged_in = (int) $options['skip']['logged_in'];
			if ( in_array(
				$skip_logged_in,
				array(
					Statify::SKIP_USERS_NONE,
					Statify::SKIP_USERS_ALL,
					Statify::SKIP_USERS_ADMIN,
				),
				true
			) ) {
				$res['skip']['logged_in'] = $skip_logged_in;
			}
		}

		// Sanitize user roles (preserve NULL, if unset).
		if ( isset( $options['show_widget_roles'] ) ) {
			$available_roles = apply_filters( 'statify__available_roles', wp_roles()->roles );
			$res['show_widget_roles'] = array();
			foreach ( $options['show_widget_roles'] as $saved_role ) {
				if ( in_array( $saved_role, array_keys( $available_roles ), true ) ) {
					array_push( $res['show_widget_roles'], $saved_role );
				}
			}
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
