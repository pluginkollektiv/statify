(function () {
    var statifyReq;
    try {
        statifyReq = new XMLHttpRequest();
        statifyReq.open('POST', statify_ajax.url + '?_ajax_nonce=' + statify_ajax.nonce +
            '&action=statify_track', true);
        statifyReq.setRequestHeader('Content-Type', 'application/json;');
        statifyReq.send(
			JSON.stringify(
				{
						'statify_tracking_data': statify_ajax.tracking_data,
						'statify_tracking_meta': statify_ajax.tracking_meta,
        		}
			)
		);
    } catch (e) {
    }
}());
