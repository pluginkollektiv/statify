( function() {
	// Initialize.
	var chartElem = document.getElementById( 'statify_chart' );
	var referrerTable = document.querySelector( '#statify_dashboard .table.referrer table tbody' );
	var targetTable = document.querySelector( '#statify_dashboard .table.target table tbody' );
	var totalsTable = document.querySelector( '#statify_dashboard .table.total table tbody' );
	var refreshBtn = document.getElementById( 'statify_refresh' );

	// Abort if config or target element is not present.
	if ( typeof statifyDashboard === 'undefined' || typeof chartElem === 'undefined' ) {
		return;
	}

	/**
	 * Update the dashboard widget
	 *
	 * @param {boolean} refresh Force refresh.
	 */
	function updateDashboard( refresh ) {
		// Disable refresh button.
		if ( refreshBtn ) {
			refreshBtn.disabled = true;
		}

		// Load data from API.
		wp.apiFetch( { path: '/statify/v1/stats' + ( refresh ? '?refresh=1' : '' ) } ).then( function( data ) {
			var labels = Object.keys( data.visits );
			var values = Object.values( data.visits );
			// Determine maximum value for scaling.
			var maxValue = Math.max.apply( Math, values );
			var fullWidth = true;
			var pointRadius = 4;
			var chart;
			var rows;
			var row;
			var i;

			// Remove the loading content.
			chartElem.innerHTML = '';

			// Adjust display according if there are too many values to display readable.
			if ( labels.length === 0 ) {
				chartElem.innerHTML = '<p>' + statifyDashboard.i18n.nodata + '</p>';
				return;
			} else if ( chartElem.clientWidth < labels.length * 4 ) {
				// Make chart scrollable, if 2px points are overlapping.
				fullWidth = false;
				pointRadius = 3;
			} else if ( chartElem.clientWidth < labels.length * 8 ) {
				// Shrink datapoints if 4px is overlapping, but 2 is not.
				pointRadius = 2;
			}

			// Draw chart.
			chart = new Chartist.LineChart( '#statify_chart', {
				labels: labels,
				series: [
					values,
				],
			}, {
				low: 0,
				showArea: true,
				fullWidth: fullWidth,
				width: ( fullWidth ? undefined : 5 * data.length ),
				axisX: {
					showGrid: false,
					showLabel: false,
					offset: 0,
				},
				axisY: {
					showGrid: true,
					showLabel: true,
					type: Chartist.FixedScaleAxis,
					low: 0,
					high: maxValue + 1,
					ticks: [
						0,
						Math.round( maxValue * 1 / 4 ),
						Math.round( maxValue * 2 / 4 ),
						Math.round( maxValue * 3 / 4 ),
						maxValue,
					],
					offset: 30,
				},
				plugins: [
					Chartist.plugins.tooltip( {
						appendToBody: true,
						class: 'statify-chartist-tooltip',
					} ),
				],
			} );

			// Replace default points with hollow circles, add "pageview(s) to value and append date (label) as metadata.
			chart.on( 'draw', function( d ) {
				var circle;
				if ( 'point' === d.type ) {
					circle = new Chartist.Svg( 'circle', {
						cx: [ d.x ],
						cy: [ d.y ],
						r: [ pointRadius ],
						'ct:value': d.value.y + ' ' + ( d.value.y > 1 ? statifyDashboard.i18n.pageviews : statifyDashboard.i18n.pageview ),
						'ct:meta': labels[d.index],
					}, 'ct-point' );
					d.element.replace( circle );
				}
			} );

			// Render top lists.
			if ( referrerTable ) {
				// Get pre-existing rows.
				rows = referrerTable.querySelectorAll( 'tr' );

				// Update or append rows.
				data.referrer.forEach( function( r, idx ) {
					row = document.createElement( 'TR' );
					row.innerHTML = '<td class="b">' + r.count + '</td>' +
						'<td class="t"><a href="' + r.url + '" target="_blank"  rel="noopener noreferrer">' + r.host + '</td>';
					if ( rows.length > idx ) {
						referrerTable.replaceChild( row, rows[idx] );
					} else {
						referrerTable.appendChild( row );
					}
				} );

				// Remove excess rows.
				for ( i = data.referrer.length; i < rows.length; i++ ) {
					referrerTable.removeChild( rows[i] );
				}
			}

			if ( targetTable ) {
				rows = targetTable.querySelectorAll( 'tr' );

				data.target.forEach( function( r, idx ) {
					row = document.createElement( 'TR' );
					row.innerHTML = '<td class="b">' + r.count + '</td>' +
						'<td class="t"><a href="' + r.url + '" target="_blank"  rel="noopener noreferrer">' + r.url + '</td>';
					if ( rows.length > idx ) {
						targetTable.replaceChild( row, rows[idx] );
					} else {
						targetTable.appendChild( row );
					}
				} );
				for ( i = data.target.length; i < rows.length; i++ ) {
					targetTable.removeChild( rows[i] );
				}
			}

			if ( totalsTable ) {
				rows = totalsTable.querySelectorAll( 'tr' );
				row = document.createElement( 'TR' );
				row.innerHTML = '<td class="b">' + data.totals.today + '</td>' +
					'<td class="t">' + statifyDashboard.i18n.today + '</td>';
				if ( rows.length > 0 ) {
					totalsTable.replaceChild( row, rows[0] );
				} else {
					totalsTable.appendChild( row );
				}
				row = document.createElement( 'TR' );
				row.innerHTML = '<td class="b">' + data.totals.alltime + '</td>' +
					'<td class="t">' + statifyDashboard.i18n.since + ' ' + data.totals.since + '</td>';
				if ( rows.length > 1 ) {
					totalsTable.replaceChild( row, rows[1] );
				} else {
					totalsTable.appendChild( row );
				}
				for ( i = 2; i < rows.length; i++ ) {
					totalsTable.removeChild( rows[i] );
				}
			}

			// Re-enable refresh button.
			if ( refreshBtn ) {
				refreshBtn.disabled = false;
			}
		} ).catch( function() {
			// Failed to load.
			chartElem.innerHTML = '<p>' + statifyDashboard.i18n.error + '</p>';
		} );
	}

	// Bind update function to "refresh" button.
	if ( refreshBtn ) {
		refreshBtn.addEventListener( 'click', function( evt ) {
			evt.preventDefault();
			updateDashboard( true );

			return false;
		} );
	}

	// Initial update.
	updateDashboard( false );
}() );
