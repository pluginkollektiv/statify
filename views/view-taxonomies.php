<?php
/**
 * Statify taxonomy evaluation page.
 *
 * @package Statify
 * @since   2.0.3
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- we use $_GET parameters in various places

$taxonomies = Statify_Evaluation::get_taxonomies();
$selected   = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : 'category';
if ( ! isset( $taxonomies[ $selected ] ) ) {
	$selected = 'category';
}
$date_range = isset( $_GET['range'] ) ? sanitize_key( wp_unslash( $_GET['range'] ) ) : '';
$start      = isset( $_GET['start'] ) ? sanitize_text_field( wp_unslash( $_GET['start'] ) ) : '';
$end        = isset( $_GET['end'] ) ? sanitize_text_field( wp_unslash( $_GET['end'] ) ) : '';

// phpcs:enable WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Statify', 'statify' ); ?> - <?php esc_html_e( 'Taxonomies', 'statify' ); ?></h1>
	<?php Statify_Evaluation::show_navigation( 'taxonomies' ); ?>
	<?php
	$items = array();
	foreach ( $taxonomies as $taxonomy_slug => $label ) {
		$items[] = array(
			'url'     => admin_url( 'index.php?page=statify_dashboard&view=taxonomies&taxonomy=' . $taxonomy_slug ),
			'label'   => $label,
			'current' => $selected === $taxonomy_slug,
		);
	}
	Statify_Evaluation::show_subnavigation( $items, __( 'Taxonomies', 'statify' ) );
	?>
	<form id="statify-dashboard-controls">
		<input type="hidden" name="page" value="statify_dashboard">
		<input type="hidden" name="view" value="taxonomies">
		<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $selected ); ?>">
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
		<label for="statify-content-datestart"><?php esc_html_e( 'Start date', 'statify' ); ?></label>
		<input id="statify-content-datestart" name="start" type="date" value="<?php echo esc_attr( $start ); ?>">
		<label for="statify-content-dateend"><?php esc_html_e( 'End date', 'statify' ); ?></label>
		<input id="statify-content-dateend" name="end" type="date" value="<?php echo esc_attr( $end ); ?>">
		<button type="submit" class="button-secondary"><?php esc_html_e( 'Select date period', 'statify' ); ?></button>
	</form>
	<section>
		<h2><?php echo esc_html( $taxonomies[ $selected ] ); ?></h2>
		<table id="statify-table-taxonomies" class="wp-list-table widefat striped statify-table">
			<caption class="screen-reader-text"><?php esc_html_e( 'Views per taxonomy term', 'statify' ); ?></caption>
			<thead><tr><th scope="col"><?php esc_html_e( 'Term', 'statify' ); ?></th><th scope="col"><?php esc_html_e( 'Slug', 'statify' ); ?></th><th scope="col"><?php esc_html_e( 'Views', 'statify' ); ?></th><th scope="col"><?php esc_html_e( 'Proportion', 'statify' ); ?></th></tr></thead>
			<tbody>
			<?php
			for ( $i = 0; $i < 3; $i++ ) :
				?>
				<tr class="placeholder"><td><span>&nbsp;</span></td><td><span>&nbsp;</span></td><td><span>&nbsp;</span></td><td><span>&nbsp;</span></td></tr><?php endfor; ?></tbody>
			<tfoot><tr><th scope="row"><?php esc_html_e( 'Sum', 'statify' ); ?></th><td></td><td class="right"></td><td class="right"></td></tr></tfoot>
		</table>
	</section>
</div>
