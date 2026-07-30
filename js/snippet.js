( function() {
	var statifyReq;
	var data;
	try {
		statifyReq = new XMLHttpRequest();
		statifyReq.open( 'POST', statifyAjax.url, true );
		statifyReq.setRequestHeader( 'Content-Type', 'application/json' );
		data = {
			referrer: document.referrer,
			target: location.pathname + location.search,
		};
		if ( 'nonce' in statifyAjax ) {
			data.nonce = statifyAjax.nonce;
		}
		statifyReq.send( JSON.stringify( data ) );
	} catch ( e ) {
	}
}() );
