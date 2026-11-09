/**
 * Convert daily to monthly data.
 *
 * @param data Daily data.
 * @return Monthly data.
 */
export function dailyToMonthly(data: DailyStats): MonthlyStats {
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

/**
 * Show a text message.
 *
 * @param element Element to add the message to.
 * @param text    Text message.
 * @private
 */
export function msg(element: HTMLElement, text: string): void {
	const p = document.createElement('p');
	p.textContent = text;
	element.innerHTML = '';
	element.appendChild(p);
}
