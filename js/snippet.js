( function() {
	var statifyReq;
	try {
		statifyReq = new XMLHttpRequest();
		statifyReq.open( 'POST', statify_ajax.url, true );
		statifyReq.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded;' );
		statifyReq.send(
			'_ajax_nonce=' + statify_ajax.nonce +
			'&action=statify_track' +
			'&statify_referrer=' + encodeURIComponent( document.referrer ) +
			'&statify_target=' + encodeURIComponent( location.pathname + location.search )
		);
	} catch ( e ) {
	}
}() );
