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

// Get stats.
$stats = Statify_Dashboard::get_stats(); ?>

	<div id="statify_chart">
		<?php if ( empty( $stats['visits'] ) ) { ?>
			<p>
				<?php esc_html_e( 'No data available.', 'statify' ); ?>
			</p>
		<?php } else { ?>
			<table id="statify_chart_data">
				<?php foreach ( (array) $stats['visits'] as $visit ) { ?>
					<tr>
						<th><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $visit['date'] ) ) ); ?></th>
						<td><?php echo (int) $visit['count']; ?></td>
					</tr>
				<?php } ?>
			</table>
		<?php } ?>
	</div>


<?php if ( ! empty( $stats['target'] ) ) { ?>
	<div class="table target">
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
								<?php echo esc_html( $target['url'] ); ?>
							</a>
						</td>
					</tr>
				<?php } ?>
			</table>
		</div>
	</div>
<?php } ?>


<?php if ( ! empty( $stats['referrer'] ) ) { ?>
	<div class="table referrer">
		<p class="sub">
			<?php esc_html_e( 'Top referers', 'statify' ); ?>
		</p>

		<div>
			<table>
				<?php foreach ( (array) $stats['referrer'] as $referrer ) { ?>
					<tr>
						<td class="b">
							<?php echo (int) $referrer['count']; ?>
						</td>
						<td class="t">
							<a href="<?php echo esc_url( $referrer['url'] ); ?>" target="_blank"  rel="noopener noreferrer">
								<?php echo esc_html( $referrer['host'] ); ?>
							</a>
						</td>
					</tr>
				<?php } ?>
			</table>
		</div>
	</div>
<?php } ?>
