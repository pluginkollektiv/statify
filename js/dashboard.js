(function () {
	// Initialize.
	var labels = [];
	var data = [];
	var statify_data_table = jQuery('#statify_chart_data');

	// Abort if no data is present.
	if (!statify_data_table.length) {
		return;
	}

	// Collect data from hidden table.
	jQuery('th', statify_data_table).each(function () {
		labels.push(jQuery(this).text());
	});

	jQuery('td', statify_data_table).each(function () {
		data.push(jQuery(this).text());
	});

	// Determine maximum value for scaling.
	var maxValue = Math.max.apply(Math, data);

	// Draw chart.
	var chart = new Chartist.Line('#statify_chart', {
		labels: labels,
		series: [
			data
		]
	}, {
		low      : 0,
		showArea : true,
		fullWidth: true,
		axisX    : {
			showGrid : false,
			showLabel: false,
			offset   : 0
		},
		axisY    : {
			showGrid : false,
			showLabel: true,
			type     : Chartist.FixedScaleAxis,
			low      : 0,
			high     : maxValue + 1,
			ticks    : [maxValue],
			offset   : 15
		},
		plugins  : [
			Chartist.plugins.tooltip({
				appendToBody: true,
				class       : 'statify-chartist-tooltip'
			})
		]
	});

	var pointRadius = 4;
	if (data.length > 365) pointRadius = 0;
	else if (data.length > 180) pointRadius = 1;
	else if (data.length > 90) pointRadius = 2;

	// Replace default points with hollow circles, add "pageview(s) to value and append date (label) as meta data.
	chart.on('draw', function (data) {
		if ('point' === data.type) {
			var circle = new Chartist.Svg('circle', {
				cx: [data.x],
				cy: [data.y],
				r: [pointRadius],
				'ct:value': data.value.y + ' ' + (data.value.y > 1 ? statify_translations.pageviews : statify_translations.pageview),
				'ct:meta': labels[data.index]
			}, 'ct-point');
			data.element.replace(circle);
		}
	});

})();
