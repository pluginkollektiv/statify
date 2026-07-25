<?php
/**
 * The dashboard page.
 *
 * @package Statify
 * @since   2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// phpcs:disable Universal.WhiteSpace.PrecisionAlignment.Found
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- we use $_GET parameters in various places

$post_types = Statify_Evaluation::get_post_types();
if ( isset( $_GET['type'] ) && in_array( wp_unslash( $_GET['type'] ), $post_types, true ) ) {
	$selected_type = sanitize_text_field( wp_unslash( $_GET['type'] ) );
} else {
	$selected_type = 'popular'; // popular = show most popular content.
}

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

if ( 'popular' === $selected_type ) {
	$section_title = __( 'Most Popular Content', 'statify' );
} else {
	$section_title = get_post_type_object( $selected_type )->labels->name;
}
if ( ! empty( $date_start ) && ! empty( $date_end ) && $date_start !== $date_end ) {
	$section_title = sprintf(
		/* translators: 1: content type (plural), 2: start date, 3: end date */
		__( '%1$s from %2$s to %3$s', 'statify' ),
		$section_title,
		Statify::parse_date( $date_start ),
		Statify::parse_date( $date_end )
	);
} elseif ( ! empty( $date_start ) && $date_start === $date_end ) {
	$section_title = sprintf(
	/* translators: Parameters: 1: content type (plural), 2: date */
		__( '%1$s from %2$s', 'statify' ),
		$section_title,
		Statify::parse_date( $date_start )
	);
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Statify', 'statify' ); ?> - <?php esc_html_e( 'Content', 'statify' ); ?></h1>

	<?php
	Statify_Evaluation::show_navigation( 'content' );

	$subnav_items = array(
		array(
			'url'     => admin_url( 'index.php?page=statify_dashboard&view=content' ),
			'label'   => __( 'Most Popular Content', 'statify' ),
			'current' => ( 'popular' === $selected_type ),
		),
	);
	foreach ( $post_types as $pt ) {
		$subnav_items[] = array(
			'url'     => admin_url( 'index.php?page=statify_dashboard&view=content&type=' . $pt ),
			'label'   => get_post_type_object( $pt )->labels->name,
			'current' => ( $selected_type === $pt ),
		);
	}
	Statify_Evaluation::show_subnavigation( $subnav_items, __( 'Popular Content and Post Types', 'statify' ) );
	?>

	<form id="statify-dashboard-controls">
		<input type="hidden" name="page" value="statify_dashboard">
		<input type="hidden" name="view" value="content">
		<?php
		if ( 'popular' !== $selected_type ) {
			echo '<input type="hidden" name="type" value="' . esc_attr( $selected_type ) . '">';
		}
		?>
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
	</form>

	<?php if ( 'popular' === $selected_type ) : ?>

	<section>
		<h2><?php esc_html_e( 'Most Popular Content', 'statify' ); ?></h2>
		<div class="statify-chart-container">
			<div id="statify_chart_content" class="statify-chart">
				<span class="spinner is-active" title="<?php esc_html_e( 'loading', 'statify' ); ?>"></span>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<section>
		<h2><?php echo esc_html( $section_title ); ?></h2>

		<table id="statify-table-posts" class="wp-list-table widefat striped statify-table">
			<caption class="screen-reader-text">
				<?php esc_html_e( 'Views per page/post', 'statify' ); ?>
			</caption>
			<thead>
			<tr>
				<th scope="col">
					<?php
					if ( 'popular' === $selected_type ) {
						esc_html_e( 'Post/Page', 'statify' );
					} else {
						echo esc_html( get_post_type_object( $selected_type )->labels->name );
					}
					?>
				</th>
				<th scope="col"><?php esc_html_e( 'URL', 'statify' ); ?></th>
				<?php if ( 'popular' === $selected_type ) : ?>
				<th scope="col"><?php esc_html_e( 'Post Type', 'statify' ); ?></th>
				<?php endif; ?>
				<th scope="col"><?php esc_html_e( 'Views', 'statify' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Proportion', 'statify' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php for ( $d = 1; $d <= 3; $d++ ) : ?>
			<tr class="placeholder">
				<td><span>&nbsp;</span></td>
				<td><span>&nbsp;</span></td>
				<?php if ( 'popular' === $selected_type ) : ?>
				<td><span>&nbsp;</span></td>
				<?php endif; ?>
				<td><span>&nbsp;</span></td>
				<td><span>&nbsp;</span></td>
			</tr>
			<?php endfor; ?>
			</tbody>
			<tfoot>
			<tr>
				<th scope="row"><?php esc_html_e( 'Sum', 'statify' ); ?></th>
				<td></td>
				<?php if ( 'popular' === $selected_type ) : ?>
				<td></td>
				<?php endif; ?>
				<td class="right"></td>
				<td class="right"></td>
			</tr>
			</tfoot>
		</table>
	</section>
</div>
