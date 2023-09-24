const sinon = require('sinon');
const expect = require('chai').expect;

describe('Statify Snippet', () => {
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

		global.XMLHttpRequest = sinon.useFakeXMLHttpRequest();
		this.requests = [];
		global.XMLHttpRequest.onCreate = (xhr) => this.requests.push(xhr);
	});

	afterEach(() => {
		global.XMLHttpRequest.restore();
	});

	it('should issue a single POST request to the REST endpoint', () => {
		require('../../js/snippet');

		expect(this.requests).to.length(1, 'Unexpected number of requests');
		expect(this.requests[0].method).to.equal('POST', 'Unexpected method');
		expect(this.requests[0].url).to.equal(
			'https://wp.example.com/wp-json/statify/v1/track',
			'Unexpected target URL'
		);
		expect(this.requests[0].requestHeaders).to.deep.equal(
			{
				'Content-Type': 'application/json;charset=utf-8',
			},
			'Unexpected request headers'
		);
		expect(this.requests[0].requestBody).to.equal(
			'{"referrer":"https://referrer.example.com/some/page/","target":"/my/page/?arg=value","nonce":"0123456789"}',
			'Unexpected request body'
		);
		expect(this.requests[0].async).to.equal(
			true,
			'Request should be async'
		);
	});
});
