type DashboardStats = {
	referrer: TopStats[];
	target: TopStats[];
	totals: TotalStats;
	visits: DailyStats;
};

type TopStats = {
	count: number;
	host: ?string;
	url: string;
	title: ?string;
};

type TotalStats = {
	alltime: number;
	since: string;
	today: number;
};

type MonthlyStats = {
	visits: { [key: string]: DailyStats };
};

type DailyStats = { [key: string]: number };

type PostStats = {
	count: number;
	title: string;
	type: string;
	typeName: string;
	url: string;
};

type ResetResponse = {
	success: boolean;
	message: string;
};
