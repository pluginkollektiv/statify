<?php
/**
 * Test taxonomy evaluation methods.
 *
 * @package Statify
 */

// phpcs:disable Squiz.Commenting.FileComment.Missing
/**
 * Test taxonomy evaluation methods.
 */
class Test_Evaluation_Taxonomies extends WP_UnitTestCase {
	/**
	 * Set up test data.
	 */
	use Statify_Test_Support;

	// phpcs:disable Squiz.Commenting.FunctionComment.Missing

	public function set_up() {
		parent::set_up();
		Statify_Install::init();
	}

	public function test_get_views_for_taxonomies_counts_terms(): void {
		$category = self::factory()->term->create(
			array(
				'taxonomy' => 'category',
				'name'     => 'News',
			)
		);
		$post_id  = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		wp_set_post_categories( $post_id, array( $category ) );
		$url = get_permalink( $post_id );
		$this->insert_test_data( '2023-03-25', '', $url, 3 );

		$data = Statify_Evaluation::get_views_for_taxonomies();

		self::assertCount( 1, $data );
		self::assertSame( 'News', $data[0]['name'] );
		self::assertSame( 3, $data[0]['count'] );
	}

	public function test_get_views_for_taxonomies_attributes_multiple_terms(): void {
		$first   = self::factory()->term->create(
			array(
				'taxonomy' => 'category',
				'name'     => 'Alpha',
			)
		);
		$second  = self::factory()->term->create(
			array(
				'taxonomy' => 'category',
				'name'     => 'Beta',
			)
		);
		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		wp_set_post_categories( $post_id, array( $first, $second ) );
		$this->insert_test_data( '2023-03-25', '', get_permalink( $post_id ), 2 );

		$data   = Statify_Evaluation::get_views_for_taxonomies();
		$counts = array_column( $data, 'count', 'name' );

		self::assertSame( 2, $counts['Alpha'] );
		self::assertSame( 2, $counts['Beta'] );
	}

	public function test_get_views_for_taxonomies_filters_dates_and_invalid_urls(): void {
		$term    = self::factory()->term->create(
			array(
				'taxonomy' => 'post_tag',
				'name'     => 'Tag',
			)
		);
		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		wp_set_post_tags( $post_id, array( $term ) );
		$url = get_permalink( $post_id );
		$this->insert_test_data( '2023-03-25', '', $url, 2 );
		$this->insert_test_data( '2023-03-26', '', $url, 3 );
		$this->insert_test_data( '2023-03-26', '', '/missing/', 5 );

		$data = Statify_Evaluation::get_views_for_taxonomies( 'post_tag', '2023-03-26', '2023-03-26' );

		self::assertCount( 1, $data );
		self::assertSame( 3, $data[0]['count'] );
	}

	public function test_get_taxonomies_returns_supported_taxonomies(): void {
		self::assertSame( array( 'category', 'post_tag' ), array_keys( Statify_Evaluation::get_taxonomies() ) );
	}
}
