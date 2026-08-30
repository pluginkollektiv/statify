export {};

declare global {
	const wp: {
		i18n: typeof import('@wordpress/i18n');
		apiFetch: typeof import('@wordpress/api-fetch').default;
	};
	const Chartist: typeof import('chartist');
	const ChartistPluginTooltip: typeof import('chartist-plugin-tooltip');
	const statifyDashboard: {
		sitename: string;
	};
}
