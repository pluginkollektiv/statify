try {
	const statifyReq = new XMLHttpRequest();
	statifyReq.open(
		'GET',
		document.getElementById('statify-js-snippet').getAttribute('data-home-url')
		+ '?statify_referrer=' + encodeURIComponent(document.referrer)
		+ '&statify_target=' + encodeURIComponent(location.pathname + location.search)
	);
	statifyReq.send(null);
} catch (e) {
}
