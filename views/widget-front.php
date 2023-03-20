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
<<<<<<< HEAD
		<p class="sub">
			<?php esc_html_e( 'Top targets', 'statify' ); ?>
		</p>

		<div>
			<table>
				<?php foreach ( (array) $stats['target'] as $target ) { ?>
					<tr>
						<td class="b">
							<?php echo (int) $target['count']; ?>
						</td>
						<td class="t">
							<a href="<?php echo esc_url( home_url( $target['url'] ) ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( Statify_Dashboard::parse_target( $target['url'] ) ); ?>
							</a>
						</td>
					</tr>
				<?php } ?>
			</table>
		</div>
	</div>
<?php } ?>

<?php if ( ! empty( $stats['searches'] ) ) { ?>
	<div class="table searches">
		<p class="sub">
			<?php esc_html_e( 'Top searches', 'statify' ); ?>
		</p>

		<div>
			<table>
				<?php foreach ( (array) $stats['searches'] as $target ) { ?>
					<tr>
						<td class="b">
							<?php echo (int) $target['count']; ?>
						</td>
						<td class="t">
							<a href="<?php echo esc_url( home_url( $target['url'] ) ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( Statify_Dashboard::parse_target( $target['url'] ) ); ?>
							</a>
						</td>
					</tr>
=======
		<p class="sub"><?php esc_html_e( 'Top targets', 'statify' ); ?></p>
		<table>
			<tbody>
				<?php for ( $i = 0; $i < $limit; $i++ ) { ?>
					<tr class="placeholder"><td colspan="2">&nbsp;</td></tr>
>>>>>>> develop
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

	<button type="button" class="button button-primary" id="statify_refresh"><?php esc_html_e( 'Refresh', 'statify' ); ?></button>
