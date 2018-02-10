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

<fieldset>
	<label for="statify_days">
		<input name="statify[days]" id="statify_days" type="number" min="1"
			   value="<?php echo esc_attr( Statify::$_options['days'] ); ?>">
		<?php esc_html_e( 'days', 'statify' ); ?> -
		<?php esc_html_e( 'Period of data saving', 'statify' ); ?>
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
	<label for="statify_snippet">
		<input type="checkbox" name="statify[snippet]" id="statify_snippet" value="1" <?php checked( Statify::$_options['snippet'], 1 ); ?> />
		<?php esc_html_e( 'Page tracking via JavaScript', 'statify' ); ?>
		<small>(<?php esc_html_e( 'recommended if caching is in use', 'statify' ); ?>)</small>
	</label>
	<label for="statify_blacklist">
		<input type="checkbox" name="statify[blacklist]" id="statify_blacklist" value="1" <?php checked( Statify::$_options['blacklist'], 1 ); ?> />
		<?php esc_html_e( 'Skip tracking for referrers listed in the comment blacklist', 'statify' ); ?>
	</label>
</fieldset>
<?php wp_nonce_field( 'statify-dashboard' ); ?>

<p class="meta-links">
	<a href="<?php esc_html_e( 'https://wordpress.org/plugins/statify/', 'statify' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Documentation', 'statify' ); ?></a>
	&bull; <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TD4AMD2D8EMZW" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Donate', 'statify' ); ?></a>
	&bull; <a href="https://wordpress.org/support/plugin/statify" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Support', 'statify' ); ?></a>
</p>
