(function () {
	// Initialize.
	var labels = [];
	var data_total = [];
	var data_mobile = [];
	var statify_data_table = jQuery('#statify_chart_data');

	// Abort if no data is present.
	if (!statify_data_table.length) {
		return;
	}

	// Collect data from hidden table.
	jQuery('th', statify_data_table).each(function () {
		labels.push(jQuery(this).text());
	});

	jQuery('td.total', statify_data_table).each(function () {
		data_total.push(jQuery(this).text());
	});

	jQuery('td.mobile', statify_data_table).each(function () {
		data_mobile.push(jQuery(this).text());
	});

	// Determine maximum value for scaling.
	var maxValue = Math.max.apply(Math, data_total);

	// Draw chart.
	var chart = new Chartist.Line('#statify_chart', {
		labels: labels,
		series: [
			data_total,
			data_mobile
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
	if (data_total.length > 365) pointRadius = 0;
	else if (data_total.length > 180) pointRadius = 1;
	else if (data_total.length > 90) pointRadius = 2;

	// Replace default points with hollow circles, add "pageview(s) to value and append date (label) as meta data.
	chart.on('draw', function (data_total) {
		if ('point' === data_total.type) {
			var circle = new Chartist.Svg('circle', {
				cx: [data_total.x],
				cy: [data_total.y],
				r: [pointRadius],
				'ct:value': data_total.value.y + ' ' + (data_total.value.y > 1 ? statify_translations.pageviews : statify_translations.pageview),
				'ct:meta': labels[data_total.index]
			}, 'ct-point');
			data_total.element.replace(circle);
		}
	});

})();
