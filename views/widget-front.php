<?php
/**
 * Statify: Widget Frontend View
 *
 * This file contains the viewmodel for the plugin's widget frontend.
 *
 * @package   Statify
 */

// Quit if accessed outside WP context.
class_exists( 'Statify' ) || exit;

$limit       = (int) Statify::$_options['limit'];
$show_totals = (int) Statify::$_options['show_totals'];
?>

	<div id="statify_chart">
		<span class="spinner is-active" title="<?php esc_html_e( 'loading', 'statify' ); ?>"></span>
	</div>

<?php if ( $limit > 0 ) : ?>

	<div class="table referrer">
		<p class="sub"><?php esc_html_e( 'Top referers', 'statify' ); ?></p>
		<table>
			<tbody>
				<?php for ( $i = 0; $i < $limit; $i++ ) { ?>
					<tr class="placeholder">
						<td colspan="2">&nbsp;</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>

	<div class="table target">
		<p class="sub"><?php esc_html_e( 'Top targets', 'statify' ); ?></p>
		<table>
			<tbody>
				<?php for ( $i = 0; $i < $limit; $i++ ) { ?>
					<tr class="placeholder"><td colspan="2">&nbsp;</td></tr>
				<?php } ?>
			</tbody>
		</table>
	</div>

	<div class="table searches">
		<p class="sub"><?php esc_html_e( 'Top searches', 'statify' ); ?></p>
		<table>
			<tbody>
				<?php for ( $i = 0; $i < $limit; $i++ ) { ?>
					<tr class="placeholder"><td colspan="2">&nbsp;</td></tr>
				<?php } ?>
			</tbody>
		</table>
	</div>

<?php endif; if ( $show_totals ) : ?>
	<div class="table total">
		<p class="sub"><?php esc_html_e( 'Totals', 'statify' ); ?></p>
		<table>
			<tbody>
				<tr class="placeholder"><td colspan="2">&nbsp;</td></tr>
				<tr class="placeholder"><td colspan="2">&nbsp;</td></tr>
		</table>
	</div>
<?php endif; ?>

	<div class="statify-refresh-button-wrapper">
		<button type="button" class="button button-primary" id="statify_refresh"><?php esc_html_e( 'Refresh', 'statify' ); ?></button>
	</div>
