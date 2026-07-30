const { describe, it, beforeEach, afterEach } = require('node:test');
const assert = require('node:assert/strict');
const fakeXhr = require('nise').fakeXhr;

describe('Statify Snippet', () => {
	let requests = [];
	let xhrMock;

	beforeEach(() => {
		global.statifyAjax = {
			url: 'https://wp.example.com/wp-json/statify/v1/track',
			nonce: '0123456789',
		};
		global.document = {
			referrer: 'https://referrer.example.com/some/page/',
		};
		global.location = {
			pathname: '/my/page/',
			search: '?arg=value',
		};

		xhrMock = fakeXhr.useFakeXMLHttpRequest();
		requests = [];
		xhrMock.onCreate = (xhr) => requests.push(xhr);
		global.XMLHttpRequest = xhrMock;
	});

	afterEach(() => {
		xhrMock.restore();
		delete require.cache[require.resolve('../../js/snippet')];
	});

	it('should issue a single POST request to the REST endpoint', () => {
		require('../../js/snippet');

		assert.equal(requests.length, 1, 'Unexpected number of requests');
		assert.equal(requests[0].method, 'POST', 'Unexpected method');
		assert.equal(
			requests[0].url,
			'https://wp.example.com/wp-json/statify/v1/track',
			'Unexpected target URL'
		);
		assert.deepEqual(
			requests[0].requestHeaders,
			{
				'Content-Type': 'application/json',
			},
			'Unexpected request headers'
		);
		assert.equal(
			requests[0].requestBody,
			'{"referrer":"https://referrer.example.com/some/page/","target":"/my/page/?arg=value","nonce":"0123456789"}',
			'Unexpected request body'
		);
		assert.equal(requests[0].async, true, 'Request should be async');
	});
});
