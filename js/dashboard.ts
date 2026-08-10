import * as api from './dashboard/api';
import * as chart from './dashboard/charts';
import {
	augmentDateRangeControls,
	augmentPostSelect,
} from './dashboard/controls';
import Formats from './dashboard/formats';
import { dailyToMonthly, msg } from './dashboard/util';
import * as table from './dashboard/tables';

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

	// Initialize number and date format.
	const fmt = new Formats(getUserLanguage());

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
		api.loadDaily(charts.daily.dataset.year!).then((data) => {
			chart.renderDaily(charts.daily!, data, fmt);

			if (charts.monthly) {
				chart.renderMonthly(
					charts.monthly,
					dailyToMonthly(data),
					fmt,
					false
				);
			}

			if (tables.daily) {
				table.renderDaily(tables.daily, data, fmt);
			}
		});
	} else if (charts.monthly) {
		api.loadMonthly()
			.then((data) => {
				chart.renderMonthly(charts.monthly!, data, fmt);

				if (charts.yearly) {
					chart.renderYearly(charts.yearly, data, fmt);
				}

				if (tables.yearly) {
					table.renderYearly(tables.yearly, data, fmt);
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
		api.loadPerPost().then((data) => {
			table.renderContent(tables.content, data, fmt);
			if (charts.content) {
				// Limit number of records.
				data = data.slice(0, 24);
				const labels = data.map((d) => d.title);
				const values = data.map((d) => d.count);
				chart.renderBarChart(charts.content, labels, values, fmt, true);
			}
		});
	} else if (tables.referrers) {
		api.loadPerReferrer().then((data) => {
			table.renderReferrers(tables.referrers, data, fmt);
			if (charts.referrer) {
				// Limit number of records.
				data = data.slice(0, 24);
				const labels = data.map((d) => d.host || '');
				const values = data.map((d) => d.count);
				chart.renderBarChart(
					charts.referrer,
					labels,
					values,
					fmt,
					true
				);
			}
		});
	}

	// Augment available controls.
	if (controls.postInput && controls.postList) {
		augmentPostSelect(controls.postList);
	}

	if (controls.dateRangeSelect) {
		augmentDateRangeControls(controls.dateRangeSelect);
	}

	/* ------------------------------------------------------------------ */

	/**
	 * Update the dashboard widget
	 *
	 * @param refresh Force refresh.
	 */
	function updateDashboard(refresh: boolean): void {
		// Load data from API.
		api.loadDashboard(refresh)
			.then((data) => {
				const labels = Object.keys(data.visits);
				const values = Object.values(data.visits);

				chart.render(
					charts.dashboard!,
					labels,
					values,
					fmt,
					false,
					(date) => fmt.formatDateYMD(new Date(date))
				);

				// Render top lists.
				if (tables.referrer) {
					table.renderTopList(tables.referrer, data.referrer, fmt);
				}
				if (tables.target) {
					table.renderTopList(tables.target, data.target, fmt);
				}

				if (tables.totals) {
					table.renderTotals(tables.totals, data.totals, fmt);
				}
			})
			.catch(() => {
				msg(
					charts.dashboard!,
					wp.i18n.__('Error loading data.', 'statify')
				);
			});
	}

	// Abort if config or target element is not present.
	if (charts.dashboard) {
		// Initial update.
		updateDashboard(false);
	}
}
