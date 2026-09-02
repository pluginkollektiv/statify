/**
 * Tests for the auto-refresh supervisor.
 *
 * @package Statify
 */

const { describe, it } = require( 'node:test' );
const assert                   = require( 'node:assert/strict' );

const { createAutoRefresh } = require( '../../js/auto-refresh.js' );

const tick = ( ms ) => new Promise( ( resolve ) => setTimeout( resolve, ms ) );

describe( 'Statify Auto-Refresh', () => {
	it( 'start() calls refresh on every interval tick', async () => {
		let calls = 0;
		const sup = createAutoRefresh(
			{
				intervalMs: 10,
				refresh: () => {
					calls++;
				},
			}
		);

		sup.start();
		assert.equal( calls, 0, 'refresh not called before first tick' );

		await tick( 35 );
		sup.stop();
		assert.ok(
			calls >= 2,
			'refresh fired at least twice (' + calls + ')'
		);
	} );

	it( 'stop() prevents further refresh calls', async () => {
		let calls   = 0;
		let stopped = 0;
		const sup   = createAutoRefresh(
			{
				intervalMs: 10,
				refresh: () => {
					calls++;
				},
			}
		);

		sup.start();
		await tick( 25 );
		sup.stop();
		stopped = calls;
		await tick( 25 );
		assert.equal( calls, stopped, 'no calls after stop()' );
	} );

	it( 'a rejected refresh does not throw and does not stop the timer', async () => {
		let calls = 0;
		const sup = createAutoRefresh(
			{
				intervalMs: 10,
				refresh: () => {
					calls++;
					if ( calls === 1 ) {
						return Promise.reject( new Error( 'boom' ) );
					}
					return undefined;
				},
			}
		);

		sup.start();
		await tick( 35 );
		sup.stop();
		assert.ok(
			calls >= 2,
			'timer survived rejection (' + calls + ' calls)'
		);
	} );

	it( 'start() resets a previous timer', async () => {
		let calls = 0;
		const sup = createAutoRefresh(
			{
				intervalMs: 10,
				refresh: () => {
					calls++;
				},
			}
		);

		sup.start();
		sup.start();
		sup.start();
		await tick( 25 );
		sup.stop();
		assert.ok(
			calls >= 1 && calls <= 4,
			'single active timer (' + calls + ' calls)'
		);
	} );
} );
