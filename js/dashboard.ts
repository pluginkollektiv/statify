import { FlatSeries } from 'chartist';

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
		referrer: document.querySelector<HTMLTableSectionElement>(
			'#statify_dashboard .table.referrer table tbody'
		),
		target: document.querySelector<HTMLTableSectionElement>(
			'#statify_dashboard .table.target table tbody'
		),
		totals: document.querySelector<HTMLTableSectionElement>(
			'#statify_dashboard .table.total table tbody'
		),
		// Extended Evaluation.
		yearly: document.getElementById(
			'statify-table-yearly'
		) as HTMLTableElement,
		daily: document.getElementById(
			'statify-table-daily'
		) as HTMLTableElement,
		content: document.getElementById(
			'statify-table-posts'
		) as HTMLTableElement,
		referrers: document.getElementById(
			'statify-table-referrer'
		) as HTMLTableElement,
	};
	const controls = {
		// Extended Evaluation.
		postInput: document.getElementById(
			'statify-dashboard-post'
		) as HTMLInputElement,
		postList: document.getElementById(
			'statify-dashboard-posts'
		) as HTMLInputElement,
		dateRangeSelect: document.getElementById(
			'statify-content-daterange'
		) as HTMLSelectElement,
	};

	// Initialize number format.
	const lang = getUserLanguage();
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

	/**
	 * Get user language from WP i18n, converted to BCP-47 notation.
	 *
	 * @return BCP-47 language tag, if valid and supported.
	 */
	function getUserLanguage(): string | undefined {
		let lc = (wp.i18n.getLocaleData()[''] as any)?.lang || '';

		try {
			// Get supported value - automatically sanitizes capitalization if necessary.
			lc = Intl.NumberFormat.supportedLocalesOf(lc.replace('_', '-'))[0];
		} catch {
			// Invalid or unsupported language — let Intl fall back to the browser default.
			lc = undefined;
		}

		return lc;
	}

	/* ------------------------------------------------------------------ */

	// Render available charts and tables.
	if (charts.daily) {
		loadDaily(charts.daily.dataset.year!).then((data) => {
			renderDaily(charts.daily!, data);

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
				renderMonthly(charts.monthly!, data);

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
					charts.monthly!,
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
				const labels = data.map((d) => d.host || '');
				const values = data.map((d) => d.count);
				renderBarChart(charts.referrer, labels, values, true);
			}
		});
	}

	// Augment available controls.
	if (controls.postInput && controls.postList) {
		fetchData<PostStats[]>('posts').then((data) =>
			data.forEach((post) => {
				const opt = document.createElement('option');
				opt.value = post.url;
				opt.textContent = post.title;
				controls.postList!.appendChild(opt);
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
	 * @param refresh Force refresh.
	 */
	function updateDashboard(refresh: boolean): void {
		// Load data from API.
		fetchData<DashboardStats>('stats', refresh ? 'refresh=1' : undefined)
			.then((data) => {
				const labels = Object.keys(data.visits);
				const values = Object.values(data.visits);

				render(charts.dashboard!, labels, values, false, (date) =>
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
					charts.dashboard!,
					wp.i18n.__('Error loading data.', 'statify')
				);
			});
	}

	/**
	 * Load daily statistics.
	 *
	 * @param year Year to load data for.
	 *
	 * @return Data promise from API.
	 */
	function loadDaily(year: string): Promise<DailyStats> {
		return fetchData<DailyStats>('stats/extended', { scope: 'day', year });
	}

	/**
	 * Load monthly statistics.
	 *
	 * @return Data promise from API.
	 */
	function loadMonthly(): Promise<MonthlyStats> {
		const param = { scope: 'month' } as Record<string, string>;
		const post = new URLSearchParams(window.location.search).get('post');
		if (post) {
			param.post = post;
		}
		return fetchData<MonthlyStats>('stats/extended', param);
	}

	/**
	 * Load statistics per post.
	 *
	 * @return Data promise from API.
	 */
	function loadPerPost(): Promise<PostStats[]> {
		const param: Record<string, string> = {};
		const search = new URLSearchParams(window.location.search);
		['type', 'start', 'end'].forEach((p) => {
			const v = search.get(p);
			if (v) {
				param[p] = v;
			}
		});

		return fetchData<PostStats[]>('stats/posts', param);
	}

	/**
	 * Load statistics per referrer.
	 *
	 * @return Data promise from API.
	 */
	function loadPerReferrer(): Promise<TopStats[]> {
		const param: Record<string, string> = {};
		const search = new URLSearchParams(window.location.search);
		['post', 'start', 'end'].forEach((p) => {
			const v = search.get(p);
			if (v) {
				param[p] = v;
			}
		});

		return fetchData<TopStats[]>('stats/referrers', param);
	}

	/**
	 * Render daily statistics.
	 *
	 * @param root Root element.
	 * @param data Data from API.
	 */
	function renderDaily(root: HTMLElement, data: DailyStats): void {
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
	 * @param root     Root element.
	 * @param data     Data from API.
	 * @param showYear Show year in label? (default: true)
	 */
	function renderMonthly(
		root: HTMLElement,
		data: MonthlyStats,
		showYear: boolean = true
	): void {
		const values = Object.values(data.visits).flatMap((y) =>
			Object.values(y)
		);

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
				metaFunc = (date) => dateFormatYM.format(new Date(date));
			} else {
				labels = labels.flatMap((y) =>
					Object.keys(data.visits[y]).map((m) =>
						dateFormatYM.format(new Date(Number(y), Number(m) - 1))
					)
				);
			}
		} else {
			labels = labels.flatMap((y) =>
				Object.keys(data.visits[y]).map((m) =>
					dateFormatM.format(new Date(Number(y), Number(m) - 1))
				)
			);
		}

		render(root, labels, values, true, metaFunc, labelFunc);
	}

	/**
	 * Render yearly statistics.
	 *
	 * @param root Root element.
	 * @param data Data from API.
	 */
	function renderYearly(root: HTMLElement, data: MonthlyStats): void {
		const labels = Object.keys(data.visits);
		const values = Object.values(data.visits).map((y) =>
			Object.values(y).reduce((a, b) => a + b, 0)
		);

		render(root, labels, values);
	}

	/**
	 * Render statistics chart.
	 *
	 * @param root                  Root element.
	 * @param labels                Labels.
	 * @param values                Values.
	 * @param showAxis              Show X axis? (default: true)
	 * @param labelToMetaFunc       Meta data (tooltip label) function. (optional)
	 * @param labelInterpolationFnc Label interpolation function. (optional)
	 */
	function render(
		root: HTMLElement,
		labels: string[],
		values: number[],
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
					labelInterpolationFnc: (v: number) =>
						numberFormat.format(v),
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
							numberFormat.format((d.value as any)?.y)
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
	 * @param numericLabels Transform labels into numbers and add legend? (default: false)
	 */
	function renderBarChart(
		root: HTMLElement,
		labels: string[],
		values: number[],
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
				return { meta: labels[i], value: v };
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
					labelInterpolationFnc: (v) =>
						numberFormat.format(Number(v)),
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
	 * @param table Table element.
	 * @param data  Data to display.
	 */
	function renderTopList(
		table: HTMLTableSectionElement,
		data: TopStats[]
	): void {
		const rows = data.map((r) => {
			const row = document.createElement('tr');
			let col = document.createElement('td');
			col.classList.add('b');
			col.textContent = numberFormat.format(r.count);
			row.appendChild(col);
			col = document.createElement('td');
			col.classList.add('t');
			const label = r.title || r.host || r.url;
			if (/^((https?:)?\/)?\//i.test(r.url)) {
				const link = document.createElement('a');
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
	 * @param table Table element.
	 * @param data  Totals data.
	 */
	function renderTotals(
		table: HTMLTableSectionElement,
		data: TotalStats
	): void {
		const rowToday = document.createElement('tr');
		let col = document.createElement('td');
		col.classList.add('b');
		col.textContent = numberFormat.format(data.today);
		rowToday.appendChild(col);
		col = document.createElement('td');
		col.classList.add('t');
		col.textContent = wp.i18n.__('today', 'statify');
		rowToday.appendChild(col);

		const rowAll = document.createElement('tr');
		col = document.createElement('td');
		col.classList.add('b');
		col.textContent = numberFormat.format(data.alltime);
		rowAll.appendChild(col);
		col = document.createElement('td');
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
	 * @param table Root element.
	 * @param data  Data from API.
	 */
	function renderYearlyTable(
		table: HTMLTableElement,
		data: MonthlyStats
	): void {
		const tbody = table.querySelector('tbody')!;

		tbody.innerHTML = '';

		for (const year in data.visits) {
			const row = document.createElement('tr');
			let col = document.createElement('th');
			let sum = 0;
			col.scope = 'row';
			col.textContent = year;
			row.appendChild(col);

			for (let month = 1; month <= 12; month++) {
				col = document.createElement('td');
				col.classList.add('right');
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

			col = document.createElement('td');
			col.classList.add('statify-table-sum', 'right');
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
	 * @param table Root element.
	 * @param data  Data from API.
	 */
	function renderDailyTable(table: HTMLTableElement, data: DailyStats): void {
		const rows = Array.from(table.querySelectorAll('tbody > tr'));
		const cols = rows.map((row) => Array.from(row.querySelectorAll('td')));
		// let out = cols.slice(0, 31);

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
			cols[d.getDate() - 1][m].dataset.raw = String(count);
			cols[d.getDate() - 1][m].textContent = numberFormat.format(count);
		}

		let out =
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
			if (vls[m] > 0) {
				out[m].dataset.raw = String(s);
				out[m].textContent = numberFormat.format(s);
			} else {
				out[m].dataset.raw = '';
				out[m].textContent = '-';
			}
		}

		out =
			cols[
				rows.findIndex((row) =>
					row.classList.contains('statify-table-max')
				)
			];
		for (const [m, s] of max.entries()) {
			if (vls[m] > 0) {
				out[m].dataset.raw = vls[m];
				out[m].textContent = numberFormat.format(s);
			} else {
				out[m].dataset.raw = '';
				out[m].textContent = '-';
			}
		}

		for (const row of rows) {
			row.classList.remove('placeholder');
		}

		addExportButton(table);
	}

	/**
	 * Render content table.
	 *
	 * @param table Root element.
	 * @param data  Data from API.
	 */
	function renderContentTable(
		table: HTMLTableElement,
		data: PostStats[]
	): void {
		const tbody = table.querySelector('tbody');
		const sumRow =
			table.querySelectorAll<HTMLTableCellElement>('tfoot > tr > td');
		const showType = table.querySelectorAll('thead > tr > th').length > 4;

		const total = data.map((d) => d.count).reduce((a, b) => a + b, 0);

		const rows = data.map((d) => {
			const row = document.createElement('tr');
			let col = document.createElement('td');
			const link = document.createElement('a');
			link.href = d.url;
			link.textContent = d.title;
			col.append(link);
			row.appendChild(col);
			col = document.createElement('td');
			col.textContent = d.url;
			row.appendChild(col);
			if (showType) {
				col = document.createElement('td');
				col.textContent = d.typeName;
				row.appendChild(col);
			}
			col = document.createElement('td');
			col.classList.add('right');
			col.dataset.raw = String(d.count);
			col.textContent = numberFormat.format(d.count);
			row.appendChild(col);
			col = document.createElement('td');
			col.classList.add('right');
			col.dataset.raw = String(
				Math.round((d.count / total) * 10000) / 100
			);
			col.textContent = numberFormatPercent.format(d.count / total);
			row.appendChild(col);

			return row;
		});

		updateTable(tbody!, rows);

		sumRow[sumRow.length - 2].dataset.raw = String(total);
		sumRow[sumRow.length - 2].textContent = numberFormat.format(total);
		sumRow[sumRow.length - 1].dataset.raw = '1';
		sumRow[sumRow.length - 1].textContent = numberFormatPercent.format(1);

		addExportButton(table);
	}

	/**
	 * Render referrers table.
	 *
	 * @param table Root element.
	 * @param data  Data from API.
	 */
	function renderReferrersTable(
		table: HTMLTableElement,
		data: TopStats[]
	): void {
		const tbody = table.querySelector('tbody');
		if (!tbody) {
			return;
		}
		const sumRow = table.querySelectorAll('tfoot > tr > td');

		const total = data.map((d) => d.count).reduce((a, b) => a + b, 0);

		const rows = data.map((d) => {
			const row = document.createElement('tr');
			let col = document.createElement('td');
			const link = document.createElement('a');
			link.href = d.url;
			link.textContent = d.host;
			col.append(link);
			row.appendChild(col);
			col = document.createElement('td');
			col.classList.add('right');
			col.textContent = numberFormat.format(d.count);
			row.appendChild(col);
			col = document.createElement('td');
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
	 * @param table   Target table or table body.
	 * @param newRows New row elements.
	 */
	function updateTable(
		table: HTMLTableElement | HTMLTableSectionElement,
		newRows: HTMLTableRowElement[]
	): void {
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
	 * @param data Daily data.
	 * @return Monthly data.
	 */
	function dailyToMonthly(data: DailyStats): MonthlyStats {
		const monthly = { visits: {} } as MonthlyStats;
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
	 * @param table Table to process
	 */
	function addExportButton(table: HTMLTableElement): void {
		const exportBtn = document.createElement('a');
		let csvUrl: string | null = null;
		exportBtn.classList.add('button');
		exportBtn.href = '#';

		// Generate filename from table caption.
		exportBtn.download =
			statifyDashboard.sitename +
			'-' +
			table.caption?.innerText.replace(/\s+/g, '_') +
			'-export-' +
			new Date()
				.toISOString()
				.replace('T', '_')
				.replace(':', '-')
				.substring(0, 16) +
			'.csv';

		// Generate CSV on demand.
		exportBtn.textContent = wp.i18n.__('Export (CSV)', 'statify');
		exportBtn.addEventListener('click', () => {
			if (csvUrl) {
				URL.revokeObjectURL(csvUrl);
				csvUrl = null;
			}

			const csv = Array.from(table.rows)
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

			csvUrl = URL.createObjectURL(
				new Blob([csv], { type: 'text/csv;charset=utf-8' })
			);
			exportBtn.href = csvUrl;
		});
		table.after(exportBtn);
	}

	/**
	 * Augment date range controls.
	 * Fill start/end based on selected range and revert to "custom" when changed manually.
	 */
	function augmentDateRangeControls() {
		const start = document.getElementById(
			'statify-content-datestart'
		) as HTMLInputElement;
		const end = document.getElementById(
			'statify-content-dateend'
		) as HTMLInputElement;
		if (start && end) {
			const isoDate = (y: number, m: number, d: number): string =>
				new Date(Date.UTC(y, m, d)).toISOString().split('T')[0];

			controls.dateRangeSelect.addEventListener(
				'change',
				(evt: Event) => {
					const now = new Date(),
						y = now.getFullYear(),
						m = now.getMonth(),
						d = now.getDate(),
						day = now.getDay(),
						monday = d - day + (day === 0 ? -6 : 1);

					switch ((evt.target as HTMLSelectElement)?.value) {
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
				}
			);

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
	 * @param path  Relative API path.
	 * @param param Parameters (optional).
	 * @return Response promise.
	 */
	function fetchData<Type>(
		path: string,
		param?: string | Record<string, string> | URLSearchParams
	): Promise<Type> {
		return wp.apiFetch<Type>({
			path: `/statify/v1/${path}${param ? '?' + new URLSearchParams(param) : ''}`,
		});
	}

	/**
	 * Show a text message.
	 *
	 * @param element Element to add the message to.
	 * @param text    Text message.
	 * @private
	 */
	function msg(element: HTMLElement, text: string): void {
		const p = document.createElement('p');
		p.textContent = text;
		element.innerHTML = '';
		element.appendChild(p);
	}
}
