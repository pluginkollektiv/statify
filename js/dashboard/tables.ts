import Formats from './formats';

/**
 * Render top list table.
 *
 * @param table Table element.
 * @param data  Data to display.
 * @param fmt   Number and date format.
 */
export function renderTopList(
	table: HTMLTableSectionElement,
	data: TopStats[],
	fmt: Formats
): void {
	const rows = data.map((r) => {
		const row = document.createElement('tr');
		let col = document.createElement('td');
		col.classList.add('b');
		col.textContent = fmt.formatNumber(r.count);
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
 * @param fmt   Number and date format.
 */
export function renderTotals(
	table: HTMLTableSectionElement,
	data: TotalStats,
	fmt: Formats
): void {
	const rowToday = document.createElement('tr');
	let col = document.createElement('td');
	col.classList.add('b');
	col.textContent = fmt.formatNumber(data.today);
	rowToday.appendChild(col);
	col = document.createElement('td');
	col.classList.add('t');
	col.textContent = wp.i18n.__('today', 'statify');
	rowToday.appendChild(col);

	const rowAll = document.createElement('tr');
	col = document.createElement('td');
	col.classList.add('b');
	col.textContent = fmt.formatNumber(data.alltime);
	rowAll.appendChild(col);
	col = document.createElement('td');
	col.classList.add('t');
	col.textContent = wp.i18n.sprintf(
		/* translators: %s: Date. */
		wp.i18n.__('since %s', 'statify'),
		fmt.formatDateYMD(new Date(data.since))
	);
	rowAll.appendChild(col);

	updateTable(table, [rowToday, rowAll]);
}

/**
 * Render yearly table.
 *
 * @param table Root element.
 * @param data  Data from API.
 * @param fmt   Number and date format.
 */
export function renderYearly(
	table: HTMLTableElement,
	data: MonthlyStats,
	fmt: Formats
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
				col.textContent = fmt.formatNumber(data.visits[year][month]);
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
		col.textContent = fmt.formatNumber(sum);
		row.appendChild(col);

		tbody.insertBefore(row, tbody.firstChild);
	}

	addExportButton(table);
}

/**
 * Render daily table.
 *
 * @param table Root element.
 * @param data  Data from API.
 * @param fmt   Number and date format.
 */
export function renderDaily(
	table: HTMLTableElement,
	data: DailyStats,
	fmt: Formats
): void {
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
		cols[d.getDate() - 1][m].textContent = fmt.formatNumber(count);
	}

	let out =
		cols[
			rows.findIndex((row) => row.classList.contains('statify-table-sum'))
		];
	const avg =
		cols[
			rows.findIndex((row) => row.classList.contains('statify-table-avg'))
		];
	for (const [m, s] of sum.entries()) {
		if (vls[m] > 0) {
			out[m].dataset.raw = String(s);
			out[m].textContent = fmt.formatNumber(s);
			avg[m].dataset.raw = String(Math.round(s / vls[m]));
			avg[m].textContent = fmt.formatNumber(Math.round(s / vls[m]));
		} else {
			out[m].dataset.raw = '';
			out[m].textContent = '-';
			avg[m].dataset.raw = '';
			avg[m].textContent = '-';
		}
	}

	out =
		cols[
			rows.findIndex((row) => row.classList.contains('statify-table-min'))
		];
	for (const [m, s] of min.entries()) {
		out[m].dataset.raw = String(s);
		out[m].textContent = vls[m] > 0 ? fmt.formatNumber(s) : '-';
	}

	out =
		cols[
			rows.findIndex((row) => row.classList.contains('statify-table-max'))
		];
	for (const [m, s] of max.entries()) {
		out[m].dataset.raw = vls[m];
		out[m].textContent = vls[m] > 0 ? fmt.formatNumber(s) : '-';
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
 * @param fmt   Number and date format.
 */
export function renderContent(
	table: HTMLTableElement,
	data: PostStats[],
	fmt: Formats
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
		col.textContent = fmt.formatNumber(d.count);
		row.appendChild(col);
		col = document.createElement('td');
		col.classList.add('right');
		col.dataset.raw = String(Math.round((d.count / total) * 10000) / 100);
		col.textContent = fmt.formatPercent(d.count / total);
		row.appendChild(col);

		return row;
	});

	updateTable(tbody!, rows);

	sumRow[sumRow.length - 2].dataset.raw = String(total);
	sumRow[sumRow.length - 2].textContent = fmt.formatNumber(total);
	sumRow[sumRow.length - 1].dataset.raw = '1';
	sumRow[sumRow.length - 1].textContent = fmt.formatPercent(1);

	addExportButton(table);
}

/**
 * Render referrers table.
 *
 * @param table Root element.
 * @param data  Data from API.
 * @param fmt   Number and date format.
 */
export function renderReferrers(
	table: HTMLTableElement,
	data: TopStats[],
	fmt: Formats
): void {
	const tbody = table.querySelector<HTMLTableSectionElement>('tbody');
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
		col.textContent = fmt.formatNumber(d.count);
		row.appendChild(col);
		col = document.createElement('td');
		col.classList.add('right');
		col.textContent = fmt.formatPercent(d.count / total);
		row.appendChild(col);
		return row;
	});

	updateTable(tbody, rows);

	sumRow[sumRow.length - 2].textContent = fmt.formatNumber(total);
	sumRow[sumRow.length - 1].textContent = fmt.formatPercent(1);

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
