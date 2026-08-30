/**
 * Load statistics for the dashboard widget.
 *
 * @param refresh Force fresh data?
 */
export function loadDashboard(
	refresh: boolean = false
): Promise<DashboardStats> {
	return fetchData<DashboardStats>('stats', refresh ? 'refresh=1' : null);
}

/**
 * Load daily statistics.
 *
 * @param year Year to load data for.
 *
 * @return Data promise from API.
 */
export function loadDaily(year: string): Promise<DailyStats> {
	return fetchData<DailyStats>('stats/extended', { scope: 'day', year });
}

/**
 * Load monthly statistics.
 *
 * @return Data promise from API.
 */
export function loadMonthly(): Promise<MonthlyStats> {
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
export function loadPerPost(): Promise<PostStats[]> {
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
export function loadPerReferrer(): Promise<TopStats[]> {
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
 * Load statistics for posts.
 *
 * @return Data promise from API.
 */
export function loadPosts(): Promise<PostStats[]> {
	return fetchData<PostStats[]>('posts');
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
	param: string | Record<string, string> | null = null
): Promise<Type> {
	return wp.apiFetch<Type>({
		path: `/statify/v1/${path}${param ? '?' + new URLSearchParams(param) : ''}`,
	});
}
