(function (root) {
	'use strict';

	// ClassicPress does not ship a working wp.apiFetch. This tiny, original
	// subset covers the surface Statify uses (path/method/headers/JSON) plus
	// the middleware helpers some core inline scripts still call, so nothing
	// throws. Regular WordPress keeps the core wp-api-fetch and never loads it.

	// Read the localized config lazily so it is always the current value.
	function getConfig() {
		const cfg = root.statifyApiFetchConfig;
		return cfg && typeof cfg === 'object' ? cfg : {};
	}

	// Build the request URL from the localized REST root and a relative path.
	function buildUrl(options) {
		if (options.url) {
			return options.url;
		}

		const rootUrl = getConfig().url || '';
		const base = rootUrl.endsWith('/') ? rootUrl : rootUrl + '/';
		const path = String(options.path || '').replace(/^\/+/, '');
		return base + path;
	}

	// A small middleware chain. Each middleware is called with (options, next).
	// next() runs the next step; the last step performs the fetch.
	const middlewares = [];
	let fetchHandler = null;

	function runMiddlewares(options) {
		let index = 0;

		function next() {
			// Run the next registered middleware, or fall through to the handler.
			const middleware = middlewares[index++];
			if (typeof middleware === 'function') {
				return middleware(options, next);
			}

			const transport = fetchHandler || defaultFetchHandler;
			return transport(options);
		}

		return next();
	}

	function getHeaders(options, hasBody) {
		const headers = {};

		for (const name in options.headers) {
			if (Object.prototype.hasOwnProperty.call(options.headers, name)) {
				headers[name] = options.headers[name];
			}
		}

		// The wp_rest nonce authorizes the request against the REST API. It is
		// the default; a middleware may have set X-WP-Nonce already, in which
		// case that value is kept.
		if (!headers['X-WP-Nonce'] && getConfig().nonce) {
			headers['X-WP-Nonce'] = getConfig().nonce;
		}

		if (hasBody) {
			headers['Content-Type'] = 'application/json';
		}

		return headers;
	}

	function parseError(response, body) {
		let code = 'statify_rest_error';
		let message = String(response.status) + ' ' + response.statusText;
		let data;

		if (body && typeof body === 'object') {
			if (body.code) {
				code = body.code;
			}
			if (body.message) {
				message = body.message;
			}
			if ('data' in body) {
				data = body.data;
			}
		}

		return { code, message, data };
	}

	function defaultFetchHandler(options) {
		const method = (options.method || 'GET').toUpperCase();
		const hasBody =
			typeof options.data !== 'undefined' && options.data !== null;

		const requestOptions = {
			method,
			headers: getHeaders(options, hasBody),
			cache: 'no-store',
		};

		if (hasBody) {
			requestOptions.body = JSON.stringify(options.data);
		}

		return fetch(buildUrl(options), requestOptions).then((response) => {
			return response.text().then((text) => {
				let parsed = null;
				if (text) {
					try {
						parsed = JSON.parse(text);
					} catch {
						parsed = null;
					}
				}

				if (!response.ok) {
					throw parseError(response, parsed);
				}

				return parsed;
			});
		});
	}

	function apiFetch(options) {
		return runMiddlewares(options || {});
	}

	// Middleware factories provided for compatibility with core inline scripts.
	function createNonceMiddleware(nonce) {
		return (options, next) => {
			options = options || {};

			if (nonce) {
				if (
					typeof options.headers !== 'object' ||
					options.headers === null
				) {
					options.headers = {};
				}
				options.headers['X-WP-Nonce'] = nonce;
			}

			return next(options);
		};
	}

	function createRootURLMiddleware(rootConfig) {
		return (options, next) => {
			options = options || {};

			const rootUrl = rootConfig ? rootConfig.url : null;
			if (rootUrl && !options.url && !options.path) {
				options.url = rootUrl;
			}

			return next(options);
		};
	}

	apiFetch.use = (middleware) => {
		middlewares.push(middleware);
	};
	apiFetch.setFetchHandler = (handler) => {
		fetchHandler = handler;
	};
	apiFetch.createNonceMiddleware = createNonceMiddleware;
	apiFetch.createRootURLMiddleware = createRootURLMiddleware;

	// Install onto wp, keeping any existing apiFetch that is already a function
	// (e.g. a leftover ClassicPress object) but filling in the missing statics.
	root.wp = root.wp || {};
	const existingApiFetch = root.wp.apiFetch;

	if (typeof existingApiFetch === 'function') {
		if (typeof existingApiFetch.use !== 'function') {
			existingApiFetch.use = apiFetch.use;
		}
		if (typeof existingApiFetch.setFetchHandler !== 'function') {
			existingApiFetch.setFetchHandler = apiFetch.setFetchHandler;
		}
		if (typeof existingApiFetch.createNonceMiddleware !== 'function') {
			existingApiFetch.createNonceMiddleware =
				apiFetch.createNonceMiddleware;
		}
		if (typeof existingApiFetch.createRootURLMiddleware !== 'function') {
			existingApiFetch.createRootURLMiddleware =
				apiFetch.createRootURLMiddleware;
		}
	} else {
		root.wp.apiFetch = apiFetch;
	}

	// Expose for Node tests; browsers use the wp.apiFetch install above.
	if (typeof module !== 'undefined' && module.exports) {
		module.exports = root.wp.apiFetch;
	}
})(typeof window !== 'undefined' ? window : globalThis);
