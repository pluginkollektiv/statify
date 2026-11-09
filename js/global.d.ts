export {};

declare global {
	const wp: {
		i18n: typeof import('@wordpress/i18n');
		apiFetch: typeof import('@wordpress/api-fetch').default;
	};
	const statifyDashboard: {
		sitename: string;
	};
}
