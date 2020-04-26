<?php
/**
 * Statify: Widget Backend View
 *
 * This file contains the viewmodel for the plugin's widget backend.
 *
 * @package   Statify
 */

// Quit if accessed outside WP context.
class_exists( 'Statify' ) || exit; ?>

<?php if ( current_user_can( 'manage_options' ) ) : ?>
<p class="meta-links settings-link">
	<a href="<?php echo esc_attr( add_query_arg( array( 'page' => 'statify-settings' ), admin_url( '/options-general.php' ) ) ); ?>"
		title="<?php esc_attr_e( 'Open full settings page', 'statify' ); ?>">
		<span class="dashicons dashicons-admin-generic"></span>
		<?php esc_html_e( 'All Settings', 'statify' ); ?></a>
</p>

<br>
<?php endif; ?>

<h3><?php esc_html_e( 'Widget Settings', 'statify' ); ?></h3>
<fieldset>
	<label for="statify_days_show">
		<input name="statify[days_show]" id="statify_days_show" type="number" min="1"
			   value="<?php echo esc_attr( Statify::$_options['days_show'] ); ?>">
		<?php esc_html_e( 'days', 'statify' ); ?> -
		<?php esc_html_e( 'Period of data display in Dashboard', 'statify' ); ?>
	</label>
	<label for="statify_limit">
		<input name="statify[limit]" id="statify_limit" type="number" min="1" max="100"
			   value="<?php echo esc_attr( Statify::$_options['limit'] ); ?>">
		<?php esc_html_e( 'Number of entries in top lists', 'statify' ); ?>
	</label>
	<label for="statify_today">
		<input type="checkbox" name="statify[today]" id="statify_today" value="1" <?php checked( Statify::$_options['today'], 1 ); ?> />
		<?php esc_html_e( 'Entries in top lists only for today', 'statify' ); ?>
	</label>
	<label for="statify_show_totals">
		<input type="checkbox" name="statify[show_totals]" id="statify_show_totals" value="1" <?php checked( Statify::$_options['show_totals'], 1 ); ?> />
		<?php esc_html_e( 'Show totals', 'statify' ); ?>
	</label>
</fieldset>
<?php wp_nonce_field( 'statify-dashboard' ); ?>

<p class="meta-links">
	<a href="<?php echo esc_url( __( 'https://wordpress.org/plugins/statify/', 'statify' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Documentation', 'statify' ); ?></a>
	&bull; <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TD4AMD2D8EMZW" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Donate', 'statify' ); ?></a>
	&bull; <a href="<?php echo esc_url( __( 'https://wordpress.org/support/plugin/statify', 'statify' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Support', 'statify' ); ?></a>
</p>
