( function() {
	var statifyReq;
	var data;
	try {
		statifyReq = new XMLHttpRequest();
		statifyReq.open( 'POST', statifyAjax.url, true );
		statifyReq.setRequestHeader( 'Content-Type', 'application/json' );
		data = {
			tracking_data: statifyAjax.tracking_data,
			tracking_meta: statifyAjax.tracking_meta,
		};
		if ( 'nonce' in statifyAjax ) {
			data.nonce = statifyAjax.nonce;
		}
		statifyReq.send( JSON.stringify( data ) );
	} catch ( e ) {
	}
}() );
