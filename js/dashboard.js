{
	// Initialize DOM elements.
	const charts = {
		// Dashboard Widget.
		dashboard: document.getElementById('statify_chart'),
		// Extended Evaluation.
		daily: document.getElementById('statify_chart_daily'),
		monthly: document.getElementById('statify_chart_monthly'),
		yearly: document.getElementById('statify_chart_yearly'),
		content: document.getElementById('statify_chart_content'),
		referrer: document.getElementById('statify_chart_referrer'),
	};
	const tables = {
		// Dashboard Widget.
		referrer: document.querySelector(
			'#statify_dashboard .table.referrer table tbody'
		),
		target: document.querySelector(
			'#statify_dashboard .table.target table tbody'
		),
		totals: document.querySelector(
			'#statify_dashboard .table.total table tbody'
		),
		// Extended Evaluation.
		yearly: document.getElementById('statify-table-yearly'),
		daily: document.getElementById('statify-table-daily'),
		content: document.getElementById('statify-table-posts'),
		referrers: document.getElementById('statify-table-referrer'),
	};
	const controls = {
		// Extended Evaluation.
		postInput: document.getElementById('statify-dashboard-post'),
		postList: document.getElementById('statify-dashboard-posts'),
		dateRangeSelect: document.getElementById('statify-content-daterange'),
	};

	// Initialize number format.
	const rawLocale = wp.i18n.getLocaleData()['']?.lang || 'en';
	const lang = (() => {
		const normalized = String(rawLocale).replace(/_/g, '-');
		try {
			return Intl.getCanonicalLocales(normalized)?.[0] || 'en';
		} catch (_) {
			return normalized.split(/[-_]/)[0] || 'en';
		}
	})();
	const numberFormat = new Intl.NumberFormat(lang);
	const numberFormatPercent = new Intl.NumberFormat(lang, {
		style: 'percent',
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	});
	const dateFormatYMD = new Intl.DateTimeFormat(lang, {
		year: 'numeric',
		month: '2-digit',
		day: '2-digit',
	});
	const dateFormatYM = new Intl.DateTimeFormat(lang, {
		year: 'numeric',
		month: 'short',
	});
	const dateFormatM = new Intl.DateTimeFormat(lang, { month: 'short' });

	/* ------------------------------------------------------------------ */

	// Render available charts and tables.
	if (charts.daily) {
		loadDaily(charts.daily.dataset.year).then((data) => {
			renderDaily(charts.daily, data);

			if (charts.monthly) {
				renderMonthly(charts.monthly, dailyToMonthly(data), false);
			}

			if (tables.daily) {
				renderDailyTable(tables.daily, data);
			}
		});
	} else if (charts.monthly) {
		loadMonthly()
			.then((data) => {
				renderMonthly(charts.monthly, data);

				if (charts.yearly) {
					renderYearly(charts.yearly, data);
				}

				if (tables.yearly) {
					renderYearlyTable(tables.yearly, data);
				}
			})
			.catch(() => {
				// Failed to load.
				msg(
					charts.monthly,
					wp.i18n.__('Error loading data.', 'statify')
				);
			});
	} else if (tables.content) {
		loadPerPost().then((data) => {
			renderContentTable(tables.content, data);
			if (charts.content) {
				// Limit number of records.
				data = data.slice(0, 24);
				const labels = data.map((d) => d.title);
				const values = data.map((d) => d.count);
				renderBarChart(charts.content, labels, values, true);
			}
		});
	} else if (tables.referrers) {
		loadPerReferrer().then((data) => {
			renderReferrersTable(tables.referrers, data);
			if (charts.referrer) {
				// Limit number of records.
				data = data.slice(0, 24);
				const labels = data.map((d) => d.host);
				const values = data.map((d) => d.count);
				renderBarChart(charts.referrer, labels, values, true);
			}
		});
	}

	// Augment available controls.
	if (controls.postInput && controls.postList) {
		fetchData('posts').then((data) =>
			data.forEach((post) => {
				const opt = document.createElement('option');
				opt.value = post.url;
				opt.textContent = post.title;
				controls.postList.appendChild(opt);
			})
		);
	}

	if (controls.dateRangeSelect) {
		augmentDateRangeControls();
	}

	/* ------------------------------------------------------------------ */

	/**
	 * Update the dashboard widget
	 *
	 * @param {boolean} refresh Force refresh.
	 */
	function updateDashboard(refresh) {
		// Load data from API.
		fetchData('stats', refresh ? 'refresh=1' : null)
			.then((data) => {
				const labels = Object.keys(data.visits);
				const values = Object.values(data.visits);

				render(charts.dashboard, labels, values, false, (date) =>
					dateFormatYMD.format(new Date(date))
				);

				// Render top lists.
				if (tables.referrer) {
					renderTopList(tables.referrer, data.referrer);
				}
				if (tables.target) {
					renderTopList(tables.target, data.target);
				}

				if (tables.totals) {
					renderTotals(tables.totals, data.totals);
				}
			})
			.catch(() => {
				msg(
					charts.dashboard,
					wp.i18n.__('Error loading data.', 'statify')
				);
			});
	}

	/**
	 * Load daily statistics.
	 *
	 * @param {number} year Year to load data for.
	 *
	 * @return {Promise<{[key: string]: number}>} Data promise from API.
	 */
	function loadDaily(year) {
		return fetchData(
			'stats/extended',
			new URLSearchParams({ scope: 'day', year })
		);
	}

	/**
	 * Load monthly statistics.
	 *
	 * @return {Promise<{visits: {[key: string]: {[key: string]: number}}}>} Data promise from API.
	 */
	function loadMonthly() {
		const param = { scope: 'month' };
		const post = new URLSearchParams(window.location.search).get('post');
		if (post) {
			param.post = post;
		}
		return fetchData('stats/extended', param);
	}

	/**
	 * Load statistics per post.
	 *
	 * @return {Promise<Array<{count: number, title: string, type: string, typeName: string, url: string}>>} Data promise from API.
	 */
	function loadPerPost() {
		const param = new URLSearchParams();
		const search = new URLSearchParams(window.location.search);
		['type', 'start', 'end'].forEach((p) => {
			const v = search.get(p);
			if (v) {
				param.set(p, v);
			}
		});

		return fetchData('stats/posts', param);
	}

	/**
	 * Load statistics per referrer.
	 *
	 * @return {Promise<Array<{count: number, host: string, url: string}>>} Data promise from API.
	 */
	function loadPerReferrer() {
		const param = new URLSearchParams();
		const search = new URLSearchParams(window.location.search);
		['post', 'start', 'end'].forEach((p) => {
			const v = search.get(p);
			if (v) {
				param.set(p, v);
			}
		});

		return fetchData('stats/referrers', param);
	}

	/**
	 * Render daily statistics.
	 *
	 * @param {HTMLElement}             root Root element.
	 * @param {{[key: string]: number}} data Data from API.
	 */
	function renderDaily(root, data) {
		const labels = Object.keys(data);
		const values = Object.values(data);
		const usedLabels = new Set();

		render(
			root,
			labels,
			values,
			true,
			(date) => dateFormatYMD.format(new Date(date)),
			(day) => {
				const date = new Date(day);
				const mon = date.getMonth();
				if (usedLabels.has(mon)) {
					return '';
				}
				usedLabels.add(mon);
				return dateFormatM.format(date);
			}
		);
	}

	/**
	 * Render monthly statistics.
	 *
	 * @param {HTMLElement}                                        root     Root element.
	 * @param {{visits: {[key: string]: {[key: string]: number}}}} data     Data from API.
	 * @param {boolean}                                            showYear Show year in label? (default: true)
	 */
	function renderMonthly(root, data, showYear = true) {
		const values = Object.values(data.visits).flatMap((y) =>
			Object.values(y)
		);

		let labelFunc = (l) => l;
		let metaFunc = (l) => l;
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
				 * @param {string} date Date string
				 * @return {string} Year string
				 */
				labelFunc = (date) => {
					const d = new Date(date);
					const m = d.getMonth();
					if (m === 0) {
						return String(d.getFullYear());
					}
					return '';
				};
				metaFunc = (date) => dateFormatYM.format(new Date(date));
			} else {
				labels = labels.flatMap((y) =>
					Object.keys(data.visits[y]).map((m) =>
						dateFormatYM.format(new Date(y, m - 1))
					)
				);
			}
		} else {
			labels = labels.flatMap((y) =>
				Object.keys(data.visits[y]).map((m) =>
					dateFormatM.format(new Date(y, m - 1))
				)
			);
		}

		render(root, labels, values, true, metaFunc, labelFunc);
	}

	/**
	 * Render yearly statistics.
	 *
	 * @param {HTMLElement}                                        root Root element.
	 * @param {{visits: {[key: string]: {[key: string]: number}}}} data Data from API.
	 */
	function renderYearly(root, data) {
		const labels = Object.keys(data.visits);
		const values = Object.values(data.visits).map((y) =>
			Object.values(y).reduce((a, b) => a + b, 0)
		);

		render(root, labels, values);
	}

	/**
	 * Render statistics chart.
	 *
	 * @param {HTMLElement}             root                  Root element.
	 * @param {string[]}                labels                Labels.
	 * @param {number[]}                values                Values.
	 * @param {boolean}                 showAxis              Show X axis? (default: true)
	 * @param {function(string):string} labelToMetaFunc       Meta data (tooltip label) function. (optional)
	 * @param {function(string):string} labelInterpolationFnc Label interpolation function. (optional)
	 */
	function render(
		root,
		labels,
		values,
		showAxis = true,
		labelToMetaFunc = (l) => l,
		labelInterpolationFnc = (l) => l
	) {
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
					offset: 30,
					labelInterpolationFnc: (v) => numberFormat.format(v),
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
						cx: [d.x],
						cy: [d.y],
						r: [pointRadius],
						'ct:value': wp.i18n.sprintf(
							/* translators: %s: Number of page views. */
							wp.i18n._n(
								'%s view',
								'%s views',
								d.value.y,
								'statify'
							),
							numberFormat.format(d.value.y)
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
	 * @param {HTMLElement} root          Root element.
	 * @param {string[]}    labels        Labels.
	 * @param {number[]}    values        Values.
	 * @param {boolean}     numericLabels Transform labels into numbers and add legend? (default: false)
	 */
	function renderBarChart(root, labels, values, numericLabels = false) {
		// Remove the loading content.
		root.innerHTML = '';

		if (labels.length === 0) {
			msg(root, wp.i18n.__('No data available.', 'statify'));
			return;
		}

		if (numericLabels) {
			const container = root.parentElement;
			let legend = container.querySelector('.statify-legend');
			if (legend) {
				legend.innerHTML = '';
			} else {
				legend = document.createElement('OL');
				legend.classList.add('statify-legend');
				container.appendChild(legend);
			}

			labels.forEach((l) => {
				const li = document.createElement('LI');
				li.textContent = l;
				legend.appendChild(li);
			});

			values = values.map((v, i) => {
				return { meta: labels[i], value: v };
			});
			labels = labels.map((l, i) => i + 1);
		}

		// Draw chart.
		new Chartist.BarChart(
			root,
			{
				labels,
				series: [values],
			},
			{
				low: 0,
				showArea: true,
				axisX: {
					showGrid: false,
				},
				axisY: {
					showGrid: true,
					showLabel: true,
					low: 0,
					onlyInteger: true,
					labelInterpolationFnc: (v) => numberFormat.format(v),
				},
				plugins: [
					[
						ChartistPluginTooltip,
						{
							appendToBody: true,
							anchorToPoint: true,
							class: 'statify-chartist-tooltip',
							transformTooltipTextFnc(y) {
								return wp.i18n.sprintf(
									/* translators: %s: Number of page views. */
									wp.i18n._n(
										'%s view',
										'%s views',
										y,
										'statify'
									),
									numberFormat.format(y)
								);
							},
						},
					],
				],
			}
		);
	}

	/**
	 * Render top list table.
	 *
	 * @param {HTMLTableElement}                                              table Table element.
	 * @param {{count: number, url: string, host: ?string, title: ?string}[]} data  Data to display.
	 */
	function renderTopList(table, data) {
		const rows = data.map((r) => {
			const row = document.createElement('TR');
			let col = document.createElement('TD');
			col.classList.add('b');
			col.textContent = numberFormat.format(r.count);
			row.appendChild(col);
			col = document.createElement('TD');
			col.classList.add('t');
			const label = r.title || r.host || r.url;
			if (/^((https?:)?\/)?\//i.test(r.url)) {
				const link = document.createElement('A');
				link.href = r.url;
				link.target = '_blank';
				link.rel = 'noopener noreferrer';
				link.textContent = label;
				col.appendChild(link);
			} else {
				col.textContent = label;
			}
			row.appendChild(col);
			return row;
		});

		updateTable(table, rows);
	}

	/**
	 * Render totals table.
	 *
	 * @param {HTMLTableElement}                                table Table element.
	 * @param {{alltime: number, since: string, today: number}} data  Totals data.
	 */
	function renderTotals(table, data) {
		const rowToday = document.createElement('TR');
		let col = document.createElement('TD');
		col.classList.add('b');
		col.textContent = numberFormat.format(data.today);
		rowToday.appendChild(col);
		col = document.createElement('TD');
		col.classList.add('t');
		col.textContent = wp.i18n.__('today', 'statify');
		rowToday.appendChild(col);

		const rowAll = document.createElement('TR');
		col = document.createElement('TD');
		col.classList.add('b');
		col.textContent = numberFormat.format(data.alltime);
		rowAll.appendChild(col);
		col = document.createElement('TD');
		col.classList.add('t');
		col.textContent = wp.i18n.sprintf(
			/* translators: %s: Date. */
			wp.i18n.__('since %s', 'statify'),
			dateFormatYMD.format(new Date(data.since))
		);
		rowAll.appendChild(col);

		updateTable(table, [rowToday, rowAll]);
	}

	/**
	 * Render yearly table.
	 *
	 * @param {HTMLElement} table Root element.
	 * @param {any}         data  Data from API.
	 */
	function renderYearlyTable(table, data) {
		const tbody = table.querySelector('tbody');

		tbody.innerHTML = '';

		for (const year in data.visits) {
			const row = document.createElement('TR');
			let col = document.createElement('TH');
			let sum = 0;
			col.scope = 'row';
			col.textContent = year;
			row.appendChild(col);

			for (let month = 1; month <= 12; month++) {
				col = document.createElement('TD');
				if (month in data.visits[year]) {
					col.dataset.raw = String(data.visits[year][month]);
					col.textContent = numberFormat.format(
						data.visits[year][month]
					);
				} else {
					col.dataset.raw = '';
					col.textContent = '-';
				}
				row.appendChild(col);
				sum += data.visits[year][month] || 0;
			}

			col = document.createElement('TD');
			col.classList.add('statify-table-sum');
			col.dataset.raw = String(sum);
			col.textContent = numberFormat.format(sum);
			row.appendChild(col);

			tbody.insertBefore(row, tbody.firstChild);
		}

		addExportButton(table);
	}

	/**
	 * Render yearly table.
	 *
	 * @param {HTMLTableElement} table Root element.
	 * @param {any}              data  Data from API.
	 */
	function renderDailyTable(table, data) {
		const rows = Array.from(table.querySelectorAll('tbody > tr'));
		const cols = rows.map((row) => Array.from(row.querySelectorAll('td')));
		let out = cols.slice(0, 31);

		const sum = new Array(12).fill(0);
		const vls = new Array(12).fill(0);
		const min = new Array(12).fill(Number.MAX_SAFE_INTEGER);
		const max = new Array(12).fill(0);

		for (const [day, count] of Object.entries(data)) {
			const d = new Date(day);
			const m = d.getMonth();
			sum[m] += count;
			++vls[m];
			min[m] = Math.min(min[m], count);
			max[m] = Math.max(max[m], count);
			out[d.getDate() - 1][m].dataset.raw = String(count);
			out[d.getDate() - 1][m].textContent = numberFormat.format(count);
		}

		out =
			cols[
				rows.findIndex((row) =>
					row.classList.contains('statify-table-sum')
				)
			];
		const avg =
			cols[
				rows.findIndex((row) =>
					row.classList.contains('statify-table-avg')
				)
			];
		for (const [m, s] of sum.entries()) {
			if (vls[m] > 0) {
				out[m].dataset.raw = String(s);
				out[m].textContent = numberFormat.format(s);
				avg[m].dataset.raw = String(Math.round(s / vls[m]));
				avg[m].textContent = numberFormat.format(
					Math.round(s / vls[m])
				);
			} else {
				out[m].dataset.raw = '';
				out[m].textContent = '-';
				avg[m].dataset.raw = '';
				avg[m].textContent = '-';
			}
		}

		out =
			cols[
				rows.findIndex((row) =>
					row.classList.contains('statify-table-min')
				)
			];
		for (const [m, s] of min.entries()) {
			out[m].dataset.raw = String(s);
			out[m].textContent = vls[m] > 0 ? numberFormat.format(s) : '-';
		}

		out =
			cols[
				rows.findIndex((row) =>
					row.classList.contains('statify-table-max')
				)
			];
		for (const [m, s] of max.entries()) {
			out[m].dataset.raw = vls[m];
			out[m].textContent = vls[m] > 0 ? numberFormat.format(s) : '-';
		}

		for (const row of rows) {
			row.classList.remove('placeholder');
		}

		addExportButton(table);
	}

	/**
	 * Render content table.
	 *
	 * @param {HTMLTableElement}                                                                   table Root element.
	 * @param {Array<{count: number, title: string, type: string, typeName: string, url: string}>} data  Data from API.
	 */
	function renderContentTable(table, data) {
		const tbody = table.querySelector('tbody');
		const sumRow = table.querySelectorAll('tfoot > tr > td');
		const showType = table.querySelectorAll('thead > tr > th').length > 4;

		const total = data.map((d) => d.count).reduce((a, b) => a + b, 0);

		const rows = data.map((d) => {
			const row = document.createElement('TR');
			let col = document.createElement('TD');
			const link = document.createElement('A');
			link.href = d.url;
			link.textContent = d.title;
			col.append(link);
			row.appendChild(col);
			col = document.createElement('TD');
			col.textContent = d.url;
			row.appendChild(col);
			if (showType) {
				col = document.createElement('TD');
				col.textContent = d.typeName;
				row.appendChild(col);
			}
			col = document.createElement('TD');
			col.classList.add('right');
			col.dataset.raw = String(d.count);
			col.textContent = numberFormat.format(d.count);
			row.appendChild(col);
			col = document.createElement('TD');
			col.classList.add('right');
			col.dataset.raw = String(
				Math.round((d.count / total) * 10000) / 100
			);
			col.textContent = numberFormatPercent.format(d.count / total);
			row.appendChild(col);

			return row;
		});

		updateTable(tbody, rows);

		sumRow[sumRow.length - 2].dataset.raw = String(total);
		sumRow[sumRow.length - 2].textContent = numberFormat.format(total);
		sumRow[sumRow.length - 1].dataset.raw = '1';
		sumRow[sumRow.length - 1].textContent = numberFormatPercent.format(1);

		addExportButton(table);
	}

	/**
	 * Render referrers table.
	 *
	 * @param {HTMLTableElement}                                  table Root element.
	 * @param {Array<{count: number, host: string, url: string}>} data  Data from API.
	 */
	function renderReferrersTable(table, data) {
		const tbody = table.querySelector('tbody');
		const sumRow = table.querySelectorAll('tfoot > tr > td');

		const total = data.map((d) => d.count).reduce((a, b) => a + b, 0);

		const rows = data.map((d) => {
			const row = document.createElement('TR');
			let col = document.createElement('TD');
			const link = document.createElement('A');
			link.href = d.url;
			link.textContent = d.host;
			col.append(link);
			row.appendChild(col);
			col = document.createElement('TD');
			col.classList.add('right');
			col.textContent = numberFormat.format(d.count);
			row.appendChild(col);
			col = document.createElement('TD');
			col.classList.add('right');
			col.textContent = numberFormatPercent.format(d.count / total);
			row.appendChild(col);
			return row;
		});

		updateTable(tbody, rows);

		sumRow[sumRow.length - 2].textContent = numberFormat.format(total);
		sumRow[sumRow.length - 1].textContent = numberFormatPercent.format(1);

		addExportButton(table);
	}

	/**
	 * Replace or append table rows.
	 *
	 * @param {HTMLTableElement|HTMLTableSectionElement} table   Target table or table body.
	 * @param {HTMLTableRowElement[]}                    newRows New row elements.
	 */
	function updateTable(table, newRows) {
		const existingRows = table.querySelectorAll('tr');

		// Replace existing rows
		newRows.forEach((newRow, idx) => {
			if (idx < existingRows.length) {
				table.replaceChild(newRow, existingRows[idx]);
			} else {
				table.appendChild(newRow);
			}
		});

		// Remove excess rows
		for (let i = newRows.length; i < existingRows.length; i++) {
			existingRows[i].remove();
		}
	}

	/**
	 * Convert daily to monthly data.
	 *
	 * @param {{[key: string]: number}} data Daily data.
	 * @return {{visits: {[key: string]: {[key: string]: number}}}} Monthly data.
	 */
	function dailyToMonthly(data) {
		const monthly = { visits: {} };
		for (const [day, count] of Object.entries(data)) {
			const date = new Date(day);
			const year = date.getFullYear();
			const month = date.getMonth() + 1;

			if (!(year in monthly.visits)) {
				monthly.visits[year] = {};
			}
			monthly.visits[year][month] =
				count + (monthly.visits[year][month] || 0);
		}
		return monthly;
	}

	// Abort if config or target element is not present.
	if (charts.dashboard) {
		// Initial update.
		updateDashboard(false);
	}

	/**
	 * Add a CSV export button to a table.
	 *
	 * @param {HTMLTableElement} table Table to process
	 */
	function addExportButton(table) {
		const exportBtn = document.createElement('a');
		exportBtn.classList.add('button');
		exportBtn.href = '#';

		// Generate filename from table caption.
		exportBtn.download =
			statifyDashboard.sitename +
			'-' +
			table.caption.innerText.replaceAll(/\s+/g, '_') +
			'-export-' +
			new Date()
				.toISOString()
				.replace('T', '_')
				.replaceAll(':', '-')
				.substring(0, 16) +
			'.csv';

		// Generate CSV on demand.
		exportBtn.textContent = wp.i18n.__('Export (CSV)', 'statify');
		exportBtn.addEventListener('click', () => {
			exportBtn.href =
				'data:text/csv;charset=utf-8,' +
				Array.from(table.rows)
					.map((row) =>
						Array.from(row.cells)
							.map(
								(col) =>
									col.dataset.raw ??
									`"${col.innerText.replaceAll('"', '""')}"`
							)
							.join(',')
					)
					.join('\r\n');
		});
		table.after(exportBtn);
	}

	/**
	 * Augment date range controls.
	 * Fill start/end based on selected range and revert to "custom" when changed manually.
	 */
	function augmentDateRangeControls() {
		const start = document.getElementById('statify-content-datestart');
		const end = document.getElementById('statify-content-dateend');
		if (start && end) {
			const isoDate = (y, m, d) =>
				new Date(Date.UTC(y, m, d)).toISOString().split('T')[0];

			controls.dateRangeSelect.addEventListener('change', (evt) => {
				const now = new Date(),
					y = now.getFullYear(),
					m = now.getMonth(),
					d = now.getDate(),
					day = now.getDay(),
					monday = d - day + (day === 0 ? -6 : 1);

				switch (evt.target.value) {
					case '':
						start.value = '';
						end.value = '';
						break;
					case 'lastYear':
						start.value = isoDate(y - 1, 0, 1);
						end.value = isoDate(y - 1, 11, 31);
						break;
					case 'lastWeek':
						start.value = isoDate(y, m, monday - 7);
						end.value = isoDate(y, m, monday - 1);
						break;
					case 'yesterday':
						start.value = isoDate(y, m, d - 1);
						end.value = isoDate(y, m, d - 1);
						break;
					case 'today':
						start.value = isoDate(y, m, d);
						end.value = isoDate(y, m, d);
						break;
					case 'thisWeek':
						start.value = isoDate(y, m, monday);
						end.value = isoDate(y, m, monday + 6);
						break;
					case 'last28days':
						start.value = isoDate(y, m, d - 27);
						end.value = isoDate(y, m, d);
						break;
					case 'lastMonth':
						start.value = isoDate(y, m - 1, 1);
						end.value = isoDate(y, m, 0);
						break;
					case 'thisMonth':
						start.value = isoDate(y, m, 1);
						end.value = isoDate(y, m + 1, 0);
						break;
					case '1stQuarter':
						start.value = isoDate(y, 0, 1);
						end.value = isoDate(y, 2, 31);
						break;
					case '2ndQuarter':
						start.value = isoDate(y, 3, 1);
						end.value = isoDate(y, 5, 30);
						break;
					case '3rdQuarter':
						start.value = isoDate(y, 6, 1);
						end.value = isoDate(y, 8, 30);
						break;
					case '4thQuarter':
						start.value = isoDate(y, 9, 1);
						end.value = isoDate(y, 11, 31);
						break;
					case 'thisYear':
						start.value = isoDate(y, 0, 1);
						end.value = isoDate(y, 11, 31);
						break;
				}
			});

			start.addEventListener('change', () => {
				controls.dateRangeSelect.value = 'custom';
			});
			end.addEventListener('change', () => {
				controls.dateRangeSelect.value = 'custom';
			});
		}
	}

	/**
	 * Fetch data from Statify API.
	 *
	 * @param {string}                                             path  Relative API path.
	 * @param {string|Record<string, string>|URLSearchParams|null} param Parameters (optional).
	 * @return {Promise<any>} Response promise.
	 */
	function fetchData(path, param = undefined) {
		return wp.apiFetch({
			path: `/statify/v1/${path}${param ? '?' + new URLSearchParams(param) : ''}`,
		});
	}

	/**
	 * Show a text message.
	 *
	 * @param {HTMLElement} element Element to add the message to.
	 * @param {string}      text    Text message.
	 * @private
	 */
	function msg(element, text) {
		const p = document.createElement('p');
		p.textContent = text;
		element.innerHTML = '';
		element.appendChild(p);
	}
}
