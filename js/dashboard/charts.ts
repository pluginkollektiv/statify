import { FlatSeries } from 'chartist';
import Formats from './formats';
import { msg } from './util';

/**
 * Render daily statistics.
 *
 * @param root Root element.
 * @param data Data from API.
 * @param fmt  Number and date format.
 */
export function renderDaily(
	root: HTMLElement,
	data: DailyStats,
	fmt: Formats
): void {
	const labels = Object.keys(data);
	const values = Object.values(data);
	const usedLabels = new Set();

	render(
		root,
		labels,
		values,
		fmt,
		true,
		(date) => fmt.formatDateYMD(new Date(date)),
		(day) => {
			const date = new Date(day);
			const mon = date.getMonth();
			if (usedLabels.has(mon)) {
				return '';
			}
			usedLabels.add(mon);
			return fmt.formatDateM(date);
		}
	);
}

/**
 * Render monthly statistics.
 *
 * @param root     Root element.
 * @param data     Data from API.
 * @param fmt      Number and date formats.
 * @param showYear Show year in label? (default: true)
 */
export function renderMonthly(
	root: HTMLElement,
	data: MonthlyStats,
	fmt: Formats,
	showYear: boolean = true
): void {
	const values = Object.values(data.visits).flatMap((y) => Object.values(y));

	let labelFunc = (l: string) => l;
	let metaFunc = (l: string) => l;
	let labels = Object.keys(data.visits);
	if (showYear) {
		if (labels.length > 2) {
			labels = labels.flatMap((y) =>
				Object.keys(data.visits[y]).map(
					(m) => `${y}-${m.padStart(2, '0')}-01`
				)
			);
			/**
			 * Interpolate labels by year if we show more than 2 years.
			 *
			 * @param date Date string
			 * @return Year string
			 */
			labelFunc = (date: string): string => {
				const d = new Date(date);
				const m = d.getMonth();
				if (m === 0) {
					return String(d.getFullYear());
				}
				return '';
			};
			metaFunc = (date) => fmt.formatDateYM(new Date(date));
		} else {
			labels = labels.flatMap((y) =>
				Object.keys(data.visits[y]).map((m) =>
					fmt.formatDateYM(new Date(Number(y), Number(m) - 1))
				)
			);
		}
	} else {
		labels = labels.flatMap((y) =>
			Object.keys(data.visits[y]).map((m) =>
				fmt.formatDateM(new Date(Number(y), Number(m) - 1))
			)
		);
	}

	render(root, labels, values, fmt, true, metaFunc, labelFunc);
}

/**
 * Render yearly statistics.
 *
 * @param root    Root element.
 * @param data    Data from API.
 * @param formats Number and date formats.
 */
export function renderYearly(
	root: HTMLElement,
	data: MonthlyStats,
	formats: Formats
): void {
	const labels = Object.keys(data.visits);
	const values = Object.values(data.visits).map((y) =>
		Object.values(y).reduce((a, b) => a + b, 0)
	);

	render(root, labels, values, formats);
}

/**
 * Render statistics chart.
 *
 * @param root                  Root element.
 * @param labels                Labels.
 * @param values                Values.
 * @param fmt                   Number and date formats.
 * @param showAxis              Show X axis? (default: true)
 * @param labelToMetaFunc       Meta data (tooltip label) function. (optional)
 * @param labelInterpolationFnc Label interpolation function. (optional)
 */
export function render(
	root: HTMLElement,
	labels: string[],
	values: number[],
	fmt: Formats,
	showAxis: boolean = true,
	labelToMetaFunc: (s: string) => string = (l) => l,
	labelInterpolationFnc: (s: string) => string = (l) => l
): void {
	// Remove the loading content or existing chart.
	root.innerHTML = '';

	// Adjust display according if there are too many values to display readable.
	let fullWidth = true;
	let pointRadius = 4;
	if (labels.length === 0) {
		msg(root, wp.i18n.__('No data available.', 'statify'));
		return;
	} else if (root.clientWidth < labels.length * 4) {
		// Make chart scrollable, if 2px points are overlapping.
		fullWidth = false;
		pointRadius = 3;
	} else if (root.clientWidth < labels.length * 8) {
		// Shrink datapoints if 4px is overlapping, but 2 is not.
		pointRadius = 2;
	}

	// Determine maximum value for scaling.
	const maxValue = Math.max(...values);

	// Generate dynamic offset roughly depending on the label length
	const axisYOffset = Math.max(String(maxValue).length * 8, 24);

	// Draw chart.
	const chart = new Chartist.LineChart(
		root,
		{
			labels,
			series: [values],
		},
		{
			low: 0,
			showArea: true,
			fullWidth,
			width: fullWidth ? undefined : 5 * labels.length,
			axisX: {
				showGrid: false,
				showLabel: showAxis,
				offset: showAxis ? 30 : 0,
				labelInterpolationFnc,
			},
			axisY: {
				showGrid: true,
				showLabel: true,
				type: Chartist.FixedScaleAxis,
				low: 0,
				high: maxValue + 1,
				ticks: [
					0,
					Math.round((maxValue * 1) / 4),
					Math.round((maxValue * 2) / 4),
					Math.round((maxValue * 3) / 4),
					maxValue,
				],
				offset: axisYOffset,
				labelInterpolationFnc: (v: number) => fmt.formatNumber(v),
			},
			plugins: [
				[
					ChartistPluginTooltip,
					{
						appendToBody: true,
						class: 'statify-chartist-tooltip',
					},
				],
			],
		}
	);

	// Replace default points with hollow circles, add "pageview(s) to value and append date (label) as metadata.
	chart.on('draw', (d) => {
		let circle;
		if ('point' === d.type) {
			circle = new Chartist.Svg(
				'circle',
				{
					cx: d.x,
					cy: d.y,
					r: pointRadius,
					'ct:value': wp.i18n.sprintf(
						/* translators: %s: Number of page views. */
						wp.i18n._n(
							'%s view',
							'%s views',
							(d.value as any)?.y,
							'statify'
						),
						fmt.formatNumber((d.value as any)?.y)
					),
					'ct:meta': labelToMetaFunc(labels[d.index]),
				},
				'ct-point'
			);
			d.element.replace(circle);
		}
	});
}

/**
 * Render bar chart.
 *
 * @param root          Root element.
 * @param labels        Labels.
 * @param values        Values.
 * @param fmt           Number and date formats.
 * @param numericLabels Transform labels into numbers and add legend? (default: false)
 */
export function renderBarChart(
	root: HTMLElement,
	labels: string[],
	values: number[],
	fmt: Formats,
	numericLabels: boolean = false
): void {
	// Remove the loading content.
	root.innerHTML = '';

	if (labels.length === 0) {
		msg(root, wp.i18n.__('No data available.', 'statify'));
		return;
	}

	// Determine maximum value for scaling.
	const maxValue = Math.max(...values);

	// Generate dynamic offset roughly depending on the label length
	const axisYOffset = Math.max(String(maxValue).length * 8, 24);

	let series: number[] | FlatSeries = values;

	if (numericLabels) {
		const container = root.parentElement;
		let legend = container!.querySelector('.statify-legend');
		if (legend) {
			legend.innerHTML = '';
		} else {
			legend = document.createElement('ol');
			legend.classList.add('statify-legend');
			container!.appendChild(legend);
		}

		labels.forEach((l) => {
			const li = document.createElement('li');
			li.textContent = l;
			legend.appendChild(li);
		});

		series = values.map((v, i) => {
			return {
				meta: labels[i],
				value: v,
			};
		});
		labels = labels.map((l, i) => String(i + 1));
	}

	// Draw chart.
	new Chartist.BarChart(
		root,
		{
			labels,
			series: [series],
		},
		{
			low: 0,
			axisX: {
				showGrid: false,
			},
			axisY: {
				showGrid: true,
				showLabel: true,
				low: 0,
				onlyInteger: true,
				offset: axisYOffset,
				labelInterpolationFnc: (v) => fmt.formatNumber(Number(v)),
			},
			plugins: [
				[
					ChartistPluginTooltip,
					{
						appendToBody: true,
						anchorToPoint: true,
						class: 'statify-chartist-tooltip',
						transformTooltipTextFnc(y: number): string {
							return wp.i18n.sprintf(
								/* translators: %s: Number of page views. */
								wp.i18n._n('%s view', '%s views', y, 'statify'),
								fmt.formatNumber(y)
							);
						},
					},
				],
			],
		}
	);
}
