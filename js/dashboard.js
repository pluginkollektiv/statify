( function() {
	// Initialize.
	var labels = [];
	var data = [];
	var statifyDataTable = jQuery( '#statify_chart_data' );
	var maxValue;
	var chart;
	var pointRadius;

	// Abort if no data is present.
	if ( ! statifyDataTable.length ) {
		return;
	}

	// Collect data from hidden table.
	jQuery( 'th', statifyDataTable ).each( function() {
		labels.push( jQuery( this ).text() );
	} );

	jQuery( 'td', statifyDataTable ).each( function() {
		data.push( jQuery( this ).text() );
	} );

	// Determine maximum value for scaling.
	maxValue = Math.max.apply( Math, data );

	// Draw chart.
	chart = new Chartist.Line( '#statify_chart', {
		labels: labels,
		series: [
			data,
		],
	}, {
		low: 0,
		showArea: true,
		fullWidth: ( data.length < 90 ),
		width: ( data.length < 90 ? null : 5 * data.length ),
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

	pointRadius = 2;

	// Replace default points with hollow circles, add "pageview(s) to value and append date (label) as meta data.
	chart.on( 'draw', function( d ) {
		var circle;
		if ( 'point' === d.type ) {
			circle = new Chartist.Svg( 'circle', {
				cx: [ d.x ],
				cy: [ d.y ],
				r: [ pointRadius ],
				'ct:value': d.value.y + ' ' + ( d.value.y > 1 ? statify_translations.pageviews : statify_translations.pageview ),
				'ct:meta': labels[d.index],
			}, 'ct-point' );
			d.element.replace( circle );
		}
	} );
}() );
