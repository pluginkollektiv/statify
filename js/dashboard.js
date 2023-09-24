{
	// Initialize
	const chartElem = document.getElementById('statify_chart');
	const referrerTable = document.querySelector(
		'#statify_dashboard .table.referrer table tbody'
	);
	const targetTable = document.querySelector(
		'#statify_dashboard .table.target table tbody'
	);
	const totalsTable = document.querySelector(
		'#statify_dashboard .table.total table tbody'
	);
	const refreshBtn = document.getElementById('statify_refresh');

	/**
	 * Update the dashboard widget
	 *
	 * @param {boolean} refresh Force refresh.
	 */
	function updateDashboard(refresh) {
		// Disable refresh button.
		if (refreshBtn) {
			refreshBtn.disabled = true;
		}

		// Load data from API.
		wp.apiFetch({
			path: '/statify/v1/stats' + (refresh ? '?refresh=1' : ''),
		})
			.then((data) => {
				const labels = Object.keys(data.visits);
				const values = Object.values(data.visits);

				render(chartElem, labels, values);

				// Render top lists.
				if (referrerTable) {
					renderTopList(referrerTable, data.referrer);
				}
				if (targetTable) {
					renderTopList(targetTable, data.target);
				}

				if (totalsTable) {
					renderTotals(totalsTable, data.totals);
				}

				// Re-enable refresh button.
				if (refreshBtn) {
					refreshBtn.disabled = false;
				}
			})
			.catch(() => {
				// Failed to load.
				chartElem.innerHTML =
					'<p>' + statifyDashboard.i18n.error + '</p>';
			});
	}

	/**
	 * Render statistics chart.
	 *
	 * @param {HTMLElement} root   Root element.
	 * @param {string[]}    labels Labels.
	 * @param {number[]}    values Values.
	 */
	function render(root, labels, values) {
		// Remove the loading content.
		root.innerHTML = '';

		// Adjust display according if there are too many values to display readable.
		let fullWidth = true;
		let pointRadius = 4;
		if (labels.length === 0) {
			root.innerHTML = '<p>' + statifyDashboard.i18n.nodata + '</p>';
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
						Math.round((maxValue * 1) / 4),
						Math.round((maxValue * 2) / 4),
						Math.round((maxValue * 3) / 4),
						maxValue,
					],
					offset: 30,
				},
				plugins: [
					Chartist.plugins.tooltip({
						appendToBody: true,
						class: 'statify-chartist-tooltip',
					}),
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
						'ct:value':
							d.value.y +
							' ' +
							(d.value.y > 1
								? statifyDashboard.i18n.pageviews
								: statifyDashboard.i18n.pageview),
						'ct:meta': labels[d.index],
					},
					'ct-point'
				);
				d.element.replace(circle);
			}
		});
	}

	/**
	 * Render top list table.
	 *
	 * @param {HTMLTableElement}               table Table element.
	 * @param {{count: number, url: string}[]} data  Data to display.
	 */
	function renderTopList(table, data) {
		// Get pre-existing rows.
		const rows = table.querySelectorAll('tr');

		// Update or append rows.
		data.forEach((r, idx) => {
			const row = document.createElement('TR');
			row.innerHTML =
				'<td class="b">' +
				r.count +
				'</td>' +
				'<td class="t"><a href="' +
				r.url +
				'" target="_blank"  rel="noopener noreferrer">' +
				r.host +
				'</td>';
			if (rows.length > idx) {
				table.replaceChild(row, rows[idx]);
			} else {
				table.appendChild(row);
			}
		});

		// Remove excess rows.
		for (let i = data.length; i < rows.length; i++) {
			table.removeChild(rows[i]);
		}
	}

	/**
	 * Render totals table.
	 *
	 * @param {HTMLTableElement}                                table Table element.
	 * @param {{alltime: number, since: string, today: number}} data  Totals data.
	 */
	function renderTotals(table, data) {
		const rows = table.querySelectorAll('tr');
		let row = document.createElement('TR');
		row.innerHTML =
			'<td class="b">' +
			data.today +
			'</td>' +
			'<td class="t">' +
			statifyDashboard.i18n.today +
			'</td>';
		if (rows.length > 0) {
			table.replaceChild(row, rows[0]);
		} else {
			table.appendChild(row);
		}
		row = document.createElement('TR');
		row.innerHTML =
			'<td class="b">' +
			data.alltime +
			'</td>' +
			'<td class="t">' +
			statifyDashboard.i18n.since +
			' ' +
			data.since +
			'</td>';
		if (rows.length > 1) {
			table.replaceChild(row, rows[1]);
		} else {
			table.appendChild(row);
		}
		for (let i = 2; i < rows.length; i++) {
			table.removeChild(rows[i]);
		}
	}

	// Abort if config or target element is not present.
	if (typeof statifyDashboard !== 'undefined' && chartElem) {
		// Bind update function to "refresh" button.
		if (refreshBtn) {
			refreshBtn.addEventListener('click', (evt) => {
				evt.preventDefault();
				updateDashboard(true);

				return false;
			});
		}

		// Initial update.
		updateDashboard(false);
	}
}
