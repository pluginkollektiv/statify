const { describe, it, beforeEach, afterEach } = require('node:test');
const assert = require('node:assert/strict');
const fakeXhr = require('nise').fakeXhr;

// The polyfill installs into global.wp (its root in Node). require() runs the
// module once; a fresh instance for each test is loaded in beforeEach after
// clearing global.wp, so module-level state (middleware list, fetch handler)
// never leaks between tests.
let apiFetch;

function jsonResponse(headers, body) {
	return {
		ok: headers.status >= 200 && headers.status < 300,
		status: headers.status,
		statusText: headers.statusText || '',
		text: () => Promise.resolve(body),
	};
}

describe('Statify API fetch polyfill', () => {
	let xhrMock;
	let requests = [];

	beforeEach(() => {
		global.statifyApiFetchConfig = {
			url: 'https://wp.example.com/wp-json/',
			nonce: '0123456789abcdef',
		};

		// Load a fresh module instance so middleware list and fetch handler
		// (module-level state) start clean for this test.
		apiFetch = require('../../js/api-fetch-polyfill');

		xhrMock = fakeXhr.useFakeXMLHttpRequest();
		requests = [];

		// The polyfill uses fetch() API semantics; stub the global fetch to
		// capture calls and return controlled responses.
		global.fetch = (url, options) => {
			const method = (options.method || 'GET').toUpperCase();
			let body = null;
			if (typeof options.body === 'string') {
				try {
					body = JSON.parse(options.body);
				} catch {
					body = options.body;
				}
			}
			requests.push({ url, method, options, body });

			if (url.includes('/error')) {
				return Promise.resolve(
					jsonResponse(
						{ status: 403, statusText: 'Forbidden' },
						JSON.stringify({
							code: 'rest_forbidden',
							message: 'Sorry.',
							data: { status: 403 },
						})
					)
				);
			}

			if (url.includes('/empty')) {
				return Promise.resolve(
					jsonResponse({ status: 200, statusText: 'OK' }, '')
				);
			}

			return Promise.resolve(
				jsonResponse(
					{ status: 200, statusText: 'OK' },
					JSON.stringify({ ok: true })
				)
			);
		};
	});

	afterEach(() => {
		xhrMock.restore();
		delete global.fetch;
		delete global.statifyApiFetchConfig;

		// Remove the instance the module installed so the next require() (in
		// beforeEach) starts a fresh one with empty module-level state.
		if (global.wp) {
			delete global.wp.apiFetch;
		}
		delete require.cache[require.resolve('../../js/api-fetch-polyfill')];
	});

	it('joins a relative path onto the localized REST root', async () => {
		await apiFetch({ path: '/statify/v1/stats' });

		assert.equal(requests.length, 1);
		assert.equal(
			requests[0].url,
			'https://wp.example.com/wp-json/statify/v1/stats'
		);
	});

	it('handles a path without a leading slash', async () => {
		await apiFetch({ path: 'statify/v1/stats' });

		assert.equal(
			requests[0].url,
			'https://wp.example.com/wp-json/statify/v1/stats'
		);
	});

	it('supports an absolute url option', async () => {
		await apiFetch({ url: 'https://other.example.com/custom' });

		assert.equal(requests[0].url, 'https://other.example.com/custom');
	});

	it('sends POST with JSON body and content type', async () => {
		await apiFetch({
			path: '/statify/v1/reset',
			method: 'POST',
			data: { confirm: true },
		});

		assert.equal(requests.length, 1);
		assert.equal(requests[0].method, 'POST');
		assert.equal(
			requests[0].options.headers['Content-Type'],
			'application/json'
		);
		assert.deepEqual(requests[0].body, { confirm: true });
	});

	it('sends the wp_rest nonce on GET and POST', async () => {
		await apiFetch({ path: '/statify/v1/stats' });
		await apiFetch({ path: '/statify/v1/reset', method: 'POST' });

		assert.equal(
			requests[0].options.headers['X-WP-Nonce'],
			'0123456789abcdef'
		);
		assert.equal(
			requests[1].options.headers['X-WP-Nonce'],
			'0123456789abcdef'
		);
	});

	it('resolves parsed JSON on success', async () => {
		const result = await apiFetch({ path: '/statify/v1/stats' });

		assert.deepEqual(result, { ok: true });
	});

	it('resolves null on an empty response', async () => {
		const result = await apiFetch({ path: '/statify/v1/empty' });

		assert.equal(result, null);
	});

	it('rejects with the REST error shape on failure', async () => {
		await assert.rejects(
			apiFetch({ path: '/statify/v1/error' }),
			(error) => {
				assert.deepEqual(error, {
					code: 'rest_forbidden',
					message: 'Sorry.',
					data: { status: 403 },
				});
				return true;
			}
		);
	});

	it('exposes middleware helpers that run without throwing', async () => {
		const nonce = apiFetch.createNonceMiddleware('secret');
		const root = apiFetch.createRootURLMiddleware({
			url: 'https://wp.example.com/wp-json/',
		});

		assert.equal(typeof nonce, 'function');
		assert.equal(typeof root, 'function');

		// Both should call next() and let a request through. The root middleware
		// sets a url when none is given, the nonce middleware adds the header.
		apiFetch.use(nonce);
		apiFetch.use(root);

		await apiFetch({});

		assert.equal(requests[0].url, 'https://wp.example.com/wp-json/');
		assert.equal(requests[0].options.headers['X-WP-Nonce'], 'secret');
	});
});
