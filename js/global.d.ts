export {};

declare global {
	const wp: {
		i18n: typeof import('@wordpress/i18n');
		apiFetch: typeof import('@wordpress/api-fetch').default;
	};
	const Chartist: typeof import('chartist');
	const ChartistPluginTooltip: typeof import('@stklcode/chartist-plugin-tooltips').default;
	const statifyDashboard: {
		sitename: string;
	};
}
