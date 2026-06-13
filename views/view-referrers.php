<?php
/**
 * The dashboard page.
 *
 * @package Statify
 * @since   2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( isset( $_GET['range'] ) ) {
	$date_range = sanitize_text_field( wp_unslash( $_GET['range'] ) );
} else {
	$date_range = '';
}
if ( isset( $_GET['start'] ) ) {
	$date_start = sanitize_text_field( wp_unslash( $_GET['start'] ) );
} else {
	$date_start = '';
}
if ( isset( $_GET['end'] ) ) {
	$date_end = sanitize_text_field( wp_unslash( $_GET['end'] ) );
} else {
	$date_end = '';
}
if ( isset( $_GET['post'] ) ) {
	$selected_post = sanitize_text_field( wp_unslash( $_GET['post'] ) );
} else {
	$selected_post = '';
}

$selected_post_title = Statify_Evaluation::post_title( $selected_post );
if ( ! empty( $date_start ) && ! empty( $date_end ) && $date_start !== $date_end ) {
	$section_title = sprintf(
		/* translators: 1: start date, 2: end date, 3: post title or "all posts" */
		__( 'Referrers from other websites from %1$s to %2$s for %3$s', 'statify' ),
		Statify::parse_date( $date_start ),
		Statify::parse_date( $date_end ),
		$selected_post_title
	);
} elseif ( ! empty( $date_start ) && $date_start === $date_end ) {
	$section_title = sprintf(
		/* translators: 1: date, 2: post title or "all posts" */
		__( 'Referrers from other websites from %1$s for %2$s', 'statify' ),
		Statify::parse_date( $date_start ),
		$selected_post_title
	);
} else {
	$section_title = sprintf(
		/* translators: %s is replaced by a post title or "all posts" */
		__( 'Referrers from other websites for %s', 'statify' ),
		$selected_post_title
	);
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Statify', 'statify' ); ?> - <?php esc_html_e( 'Referrers from other websites', 'statify' ); ?></h1>

	<?php Statify_Evaluation::show_navigation( 'referrers' ); ?>

	<form id="statify-dashboard-controls">
		<input type="hidden" name="page" value="statify_dashboard">
		<input type="hidden" name="view" value="referrers">
		<label for="statify-content-daterange"><?php esc_html_e( 'Date range', 'statify' ); ?></label>
		<select id="statify-content-daterange" name="range">
			<option value=""><?php esc_html_e( 'default (all the time)', 'statify' ); ?></option>
			<option value="lastYear" <?php selected( $date_range, 'lastYear' ); ?>><?php esc_html_e( 'last year', 'statify' ); ?></option>
			<option value="lastWeek" <?php selected( $date_range, 'lastWeek' ); ?>><?php esc_html_e( 'last week', 'statify' ); ?></option>
			<option value="yesterday" <?php selected( $date_range, 'yesterday' ); ?>><?php esc_html_e( 'yesterday', 'statify' ); ?></option>
			<option value="today" <?php selected( $date_range, 'today' ); ?>><?php esc_html_e( 'today', 'statify' ); ?></option>
			<option value="thisWeek" <?php selected( $date_range, 'thisWeek' ); ?>><?php esc_html_e( 'this week', 'statify' ); ?></option>
			<option value="last28days" <?php selected( $date_range, 'last28days' ); ?>><?php esc_html_e( 'last 28 days', 'statify' ); ?></option>
			<option value="lastMonth" <?php selected( $date_range, 'lastMonth' ); ?>><?php esc_html_e( 'last month', 'statify' ); ?></option>
			<option value="thisMonth" <?php selected( $date_range, 'thisMonth' ); ?>><?php esc_html_e( 'this month', 'statify' ); ?></option>
			<option value="thisYear" <?php selected( $date_range, 'thisYear' ); ?>><?php esc_html_e( 'this year', 'statify' ); ?></option>
			<option value="1stQuarter" <?php selected( $date_range, '1stQuarter' ); ?>><?php esc_html_e( '1st quarter', 'statify' ); ?></option>
			<option value="2ndQuarter" <?php selected( $date_range, '2ndQuarter' ); ?>><?php esc_html_e( '2nd quarter', 'statify' ); ?></option>
			<option value="3rdQuarter" <?php selected( $date_range, '3rdQuarter' ); ?>><?php esc_html_e( '3rd quarter', 'statify' ); ?></option>
			<option value="4thQuarter" <?php selected( $date_range, '4thQuarter' ); ?>><?php esc_html_e( '4th quarter', 'statify' ); ?></option>
			<option value="custom" <?php selected( $date_range, 'custom' ); ?>><?php esc_html_e( 'custom', 'statify' ); ?></option>
		</select>
		<label for="statify-content-datestart">Start date</label>
		<input id="statify-content-datestart" name="start" type="date" value="<?php echo esc_attr( $date_start ); ?>">
		<label for="statify-content-dateend">End date</label>
		<input id="statify-content-dateend" name="end" type="date" value="<?php echo esc_attr( $date_end ); ?>">
		<button type="submit" class="button-secondary"><?php esc_html_e( 'Select date period', 'statify' ); ?></button>

		<br>

		<label for="statify-dashboard-post"><?php esc_html_e( 'Post/Page', 'statify' ); ?></label>
		<input id="statify-dashboard-post" name="post" type="text" list="statify-dashboard-posts" value="<?php echo esc_attr( $selected_post ); ?>">
		<datalist id="statify-dashboard-posts">
			<option value=""></option>
		</datalist>
		<button type="submit" class="button-secondary"><?php esc_html_e( 'Select post/page', 'statify' ); ?></button>
	</form>

	<section>
		<div class="statify-chart-container">
			<div id="statify_chart_referrer" class="statify-chart">
				<span class="spinner is-active" title="<?php esc_html_e( 'loading', 'statify' ); ?>"></span>
			</div>
		</div>
	</section>

	<section>
		<h2><?php echo esc_html( $section_title ); ?></h2>

		<table id="statify-table-referrer" class="wp-list-table widefat striped statify-table">
			<caption class="screen-reader-text">
				<?php esc_html_e( 'Views per referrer', 'statify' ); ?>
			</caption>
			<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Referring Domain', 'statify' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Views', 'statify' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Proportion', 'statify' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php for ( $d = 1; $d <= 3; $d++ ) : ?>
			<tr class="placeholder">
				<td><span>&nbsp;</span></td>
				<td><span>&nbsp;</span></td>
				<td><span>&nbsp;</span></td>
			</tr>
			<?php endfor; ?>
			</tbody>
			<tfoot>
			<tr>
				<th scope="row"><?php esc_html_e( 'Sum', 'statify' ); ?></th>
				<td class="right"></td>
				<td class="right"></td>
			</tr>
			</tfoot>
		</table>
	</section>
</div>
