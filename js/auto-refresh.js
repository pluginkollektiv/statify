/**
 * Auto-refresh supervisor.
 *
 * Runs a periodic callback and exposes start/stop hooks. The callback may
 * return a Promise; rejections are swallowed so the next tick still fires.
 *
 * Exposed as a global `statifyAutoRefresh` in the browser and as
 * `module.exports` under node:test.
 * @param root
 * @param factory
 */
(function (root, factory) {
	const api = factory();
	if (typeof module !== 'undefined' && module.exports) {
		module.exports = api;
	} else {
		root.statifyAutoRefresh = api;
	}
})(typeof self !== 'undefined' ? self : this, function () {
	/**
	 * Create an auto-refresh supervisor.
	 *
	 * @param {Object}   options            Configuration.
	 * @param {number}   options.intervalMs Interval in milliseconds.
	 * @param {Function} options.refresh    Callback. May return a Promise.
	 * @return {{start: Function, stop: Function}} Supervisor instance.
	 */
	function createAutoRefresh(options) {
		const intervalMs = options.intervalMs;
		const refresh = options.refresh;
		let handle = null;

		function tick() {
			Promise.resolve()
				.then(refresh)
				.catch(function () {
					/* keep the timer alive on error */
				});
		}

		function start() {
			stop();
			handle = setInterval(tick, intervalMs);
		}

		function stop() {
			if (handle !== null) {
				clearInterval(handle);
				handle = null;
			}
		}

		return { start, stop };
	}

	return { createAutoRefresh };
});
