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
$refresh = isset( $_POST['statify-fresh'] ) && check_admin_referer( 'statify-dashboard-refresh' );
$stats = Statify_Dashboard::get_stats( $refresh ); ?>

	<div id="statify_chart">
		<?php if ( empty( $stats['visits'] ) ) { ?>
			<p>
				<?php esc_html_e( 'No data available.', 'statify' ); ?>
			</p>
		<?php } else { ?>
			<table id="statify_chart_data">
				<?php foreach ( (array) $stats['visits'] as $visit ) { ?>
					<tr>
						<th><?php echo esc_html( Statify::parse_date( $visit['date'] ) ); ?></th>
						<td><?php echo (int) $visit['count']; ?></td>
					</tr>
				<?php } ?>
			</table>
		<?php } ?>
	</div>


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

<?php if ( ! empty( $stats['visit_totals'] ) ) { ?>
	<div class="table total">
		<p class="sub">
			<?php esc_html_e( 'Totals', 'statify' ); ?>
		</p>
		<div>
			<table>
				<tr>
					<td class="b">
						<?php echo (int) $stats['visit_totals']['today']; ?>
					</td>
					<td class="t">
						<?php esc_html_e( 'today', 'statify' ); ?>
					</td>
				</tr>
				<tr>
					<td class="b">
						<?php echo (int) $stats['visit_totals']['since_beginning']['count']; ?>
					</td>
					<td class="t">
						<?php esc_html_e( 'since', 'statify' ); ?>
						<?php echo esc_html( Statify::parse_date( $stats['visit_totals']['since_beginning']['date'] ) ); ?>
					</td>
				</tr>
			</table>
		</div>
	</div>
<?php } ?>

<form method="post">
	<?php wp_nonce_field( 'statify-dashboard-refresh' ); ?>
	<button type="submit" class="button button-primary" name="statify-fresh"><?php esc_html_e( 'Refresh', 'statify' ); ?></button>
</form>
