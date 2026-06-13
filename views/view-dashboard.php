<?php
/**
 * The dashboard page.
 *
 * @package Statify
 * @since   2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( isset( $_GET['year'] ) ) {
	$selected_year = intval( $_GET['year'] );
} else {
	$selected_year = null;
}
$years = Statify_Evaluation::get_years();

if ( isset( $_GET['post'] ) ) {
	$selected_post = sanitize_text_field( wp_unslash( $_GET['post'] ) );
} else {
	$selected_post = null;
}

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Statify', 'statify' ); ?></h1>

	<?php
	Statify_Evaluation::show_navigation( 'statify_dashboard' );

	$subnav_items = array(
		array(
			'url'     => admin_url( 'index.php?page=statify_dashboard' ),
			'label'   => __( 'Overview', 'statify' ),
			'current' => empty( $selected_year ),
		),
	);
	foreach ( $years as $y ) {
		$subnav_items[] = array(
			'url'     => admin_url( 'index.php?page=statify_dashboard&year=' . $y ),
			'label'   => (string) $y,
			'current' => ( $selected_year === $y ),
		);
	}
	Statify_Evaluation::show_subnavigation( $subnav_items, __( 'Overview and Years', 'statify' ) );
	?>

	<h2><?php esc_html_e( 'Overview', 'statify' ); ?></a></h2>

	<form id="statify-dashboard-controls">
		<input type="hidden" name="page" value="statify_dashboard">
		<?php
		if ( ! empty( $selected_year ) ) {
			echo '<input type="hidden" name="year" value="' . esc_attr( $selected_year ) . '">';
		}
		?>
		<label for="statify-dashboard-post"><?php esc_html_e( 'Post/Page', 'statify' ); ?></label>
		<input id="statify-dashboard-post" name="post" type="text" list="statify-dashboard-posts" value="<?php echo esc_attr( $selected_post ); ?>">
		<datalist id="statify-dashboard-posts">
			<option value=""></option>
		</datalist>
		<button type="submit" class="button-secondary"><?php esc_html_e( 'Select post/page', 'statify' ); ?></button>
	</form>

	<?php if ( ! empty( $selected_year ) ) : ?>
	<section>
		<h3>
			<?php
			printf(
				/* translators: %s is replaced by a year number (e.g. 2023) */
				esc_html__( 'Daily Views %s', 'statify' ),
				esc_html( $selected_year )
			);
			?>
		</h3>

		<div class="statify-chart-container">
			<div id="statify_chart_daily" class="statify-chart" data-year="<?php echo esc_attr( $selected_year ); ?>">
				<span class="spinner is-active" title="<?php esc_html_e( 'loading', 'statify' ); ?>"></span>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<section>
		<h3>
			<?php
			if ( empty( $selected_year ) ) {
				esc_html_e( 'Monthly Views', 'statify' );
			} else {
				printf(
					/* translators: %s is replaced by a year number (e.g. 2023) */
					esc_html__( 'Monthly Views %s', 'statify' ),
					esc_html( $selected_year )
				);
			}
			?>
		</h3>

		<div class="statify-chart-container">
			<div id="statify_chart_monthly" class="statify-chart">
				<span class="spinner is-active" title="<?php esc_html_e( 'loading', 'statify' ); ?>"></span>
			</div>
		</div>
	</section>

	<?php if ( empty( $selected_year ) ) : ?>
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
			<caption class="screen-reader-text"><?php esc_html_e( 'Monthly Views', 'statify' ); ?></caption>
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Year', 'statify' ); ?></th>
					<?php for ( $mon = 1; $mon <= 12; $mon++ ) : ?>
					<th scope="col"><?php echo esc_html( date_i18n( 'M', strtotime( '1970-' . $mon . '-01' ) ) ); ?></th>
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
					<td class="statify-table-sum"><span>&nbsp;</span></td>
				</tr>
			</tbody>
		</table>
	</section>

	<?php else : ?>

	<section>
		<h3>
			<?php
			printf(
				/* translators: %s is replaced by a year number (e.g. 2023) */
				esc_html__( 'Daily Views %s', 'statify' ),
				esc_html( $selected_year )
			);
			?>
		</h3>
		<table id="statify-table-daily" class="wp-list-table widefat striped statify-table">
			<caption class="screen-reader-text">
				<?php
				printf(
					/* translators: %s is replaced by a year number (e.g. 2023) */
					esc_html__( 'Daily Views %s', 'statify' ),
					esc_html( $selected_year )
				);
				?>
			</caption>
			<thead>
			<tr>
				<th><?php esc_html_e( 'Day', 'statify' ); ?></th>
				<?php for ( $mon = 1; $mon <= 12; $mon++ ) : ?>
					<th scope="col"><?php echo esc_html( date_i18n( 'M', strtotime( '0000-' . $mon . '-01' ) ) ); ?></th>
				<?php endfor; ?>
			</tr>
			</thead>
			<tbody>
			<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
			<tr class="placeholder">
				<th scope="col"><?php echo esc_html( $d ); ?></th>
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
			<?php endfor; ?>
			<tr class="placeholder statify-table-sum">
				<th scope="col"><?php esc_html_e( 'Sum', 'statify' ); ?></th>
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
			<tr class="placeholder statify-table-avg">
				<th scope="col"><?php esc_html_e( 'Average', 'statify' ); ?></th>
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
			<tr class="placeholder statify-table-min">
				<th scope="col"><?php esc_html_e( 'Minimum', 'statify' ); ?></th>
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
			<tr class="placeholder statify-table-max">
				<th scope="col"><?php esc_html_e( 'Maximum', 'statify' ); ?></th>
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
	<?php endif; ?>
</div>
