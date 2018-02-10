<?php
/**
 * Statify: JavaScript Snippet View
 *
 * This file contains the template for the plugin's JS snippet.
 *
 * @package   Statify
 */

class_exists( 'Statify' ) || exit; ?>

	<!-- Stats by http://statify.de -->
	<script
		id="statify-js-snippet"
		data-home-url="<?php echo esc_url( home_url( '/', 'relative' ) ); ?>"
		type="text/javascript"
		src="<?php echo plugins_url( 'js/snippet.js', STATIFY_FILE ); ?>">
	</script>

<?php /* Markup space */ ?>
