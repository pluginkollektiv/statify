try {
	jQuery.ajax( {
		type: 'POST',
		url : statify_ajax.url,
		data: {
			_ajax_nonce     : statify_ajax.nonce,
			action          : 'statify_track',
			statify_referrer: encodeURIComponent( document.referrer ),
			statify_target  : encodeURIComponent( location.pathname + location.search )
		}
	} );
} catch ( e ) {
}
