const sinon  = require( 'sinon' );
const expect = require( 'chai' ).expect;

describe( 'Statify Snippet', function() {
	beforeEach( () => {
		global.statify_ajax = {
			url: 'https://wp.example.com/admin-ajax.php',
			nonce: '0123456789',
		};
		global.document = {
			referrer: 'https://referrer.example.com/some/page/',
		};
		global.location = {
			pathname: '/my/page/',
			search: '?arg=value',
		}

		global.XMLHttpRequest = sinon.useFakeXMLHttpRequest();
		this.requests = [];
		global.XMLHttpRequest.onCreate = (xhr) => this.requests.push( xhr );
	} );

	afterEach( () => {
		global.XMLHttpRequest.restore();
	} );

	it( 'should issue a single POST request to the AJAX endpoint', () => {
		require( '../../js/snippet' );

		expect( this.requests ).to.length( 1, 'Unexpected number of requests' );
		expect( this.requests[0].method ).to.equal( 'POST', 'Unexpected method' );
		expect( this.requests[0].url ).to.equal(
			'https://wp.example.com/admin-ajax.php',
			'Unexpected target URL'
		);
		expect( this.requests[0].requestHeaders ).to.deep.equal(
			{ 'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8' },
			'Unexpected request headers'
		);
		expect( this.requests[0].requestBody ).to.equal(
			'_ajax_nonce=0123456789&action=statify_track&statify_referrer=https%3A%2F%2Freferrer.example.com%2Fsome%2Fpage%2F&statify_target=%2Fmy%2Fpage%2F%3Farg%3Dvalue',
			'Unexpected request body'
		);
		expect( this.requests[0].async ).to.equal( true, 'Request should be async' );
	} );
} );
