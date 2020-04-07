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
	<div id="statify-js-snippet"
		 style="display:none"
		 data-home-url="<?php echo esc_url( home_url( '/', 'relative' ) ); ?>">
	</div>
	<script type="text/javascript"
			src="<?php echo esc_attr( plugins_url( 'js/snippet.js', STATIFY_FILE ) ); ?>">
	</script>

<?php /* Markup space */ ?>
