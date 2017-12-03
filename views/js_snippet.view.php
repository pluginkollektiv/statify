<?php
/** Quit */
class_exists( 'Statify' ) || exit; ?>

	<!-- Stats by http://statify.de -->
	<script type="text/javascript">
		const statifyReq = new XMLHttpRequest();
		statifyReq.open(
			'GET',
			'<?php echo esc_url( home_url( '/', 'relative' ) ); ?>'
			+ '?statify_referrer=' + encodeURIComponent(document.referrer)
			+ '&statify_target=' + encodeURIComponent(location.pathname + location.search)
		);
		statifyReq.send( null );
	</script>

<?php /** Markup space */ ?>
