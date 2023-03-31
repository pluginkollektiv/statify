<?php
/**
 * The dashboard page.
 *
 * @package Statify
 * @since   2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Statify', 'statify' ); ?></h1>

	<h2><?php esc_html_e( 'Overview', 'statify' ); ?></a></h2>

	<section>
		<h3><?php esc_html_e( 'Monthly Views', 'statify' ); ?></h3>

		<div class="statify-chart-container">
			<div id="statify_chart_monthly" class="statify-chart">
				<span class="spinner is-active" title="<?php esc_html_e( 'loading', 'statify' ); ?>"></span>
			</div>
		</div>
	</section>

	<section>
		<h3><?php esc_html_e( 'Yearly Views', 'statify' ); ?></h3>

		<div class="statify-chart-container">
			<div id="statify_chart_yearly" class="statify-chart">
				<span class="spinner is-active" title="<?php esc_html_e( 'loading', 'statify' ); ?>"></span>
			</div>
		</div>
	</section>

	<section>
		<h3><?php esc_html_e( 'Monthly / Yearly Views', 'statify' ); ?></h3>
		<table id="statify-table-yearly" class="wp-list-table widefat striped statify-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Year', 'statify' ); ?></th>
					<?php for ( $mon = 1; $mon <= 12; $mon++ ) : ?>
					<th scope="col"><?php echo esc_html( date_i18n( 'M', strtotime( '0000-' . $mon . '-01' ) ) ); ?></th>
					<?php endfor; ?>
					<th scope="col" class="right sum"><?php esc_html_e( 'Sum', 'statify' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr class="placeholder">
					<td><span>&nbsp;</span></td>
					<td><span>&nbsp;</span></td>
					<td><span>&nbsp;</span></td>
					<td><span>&nbsp;</span></td>
					<td><span>&nbsp;</span></td>
					<td><span>&nbsp;</span></td>
					<td><span>&nbsp;</span></td>
					<td><span>&nbsp;</span></td>
					<td><span>&nbsp;</span></td>
					<td><span>&nbsp;</span></td>
					<td><span>&nbsp;</span></td>
					<td><span>&nbsp;</span></td>
					<td><span>&nbsp;</span></td>
					<td><span>&nbsp;</span></td>
				</tr>
			</tbody>
		</table>
	</section>
</div>
