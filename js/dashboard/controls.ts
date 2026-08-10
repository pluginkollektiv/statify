import * as api from './api';

/**
 * Augment post selector.
 *
 * @param postSelect Select element to append post options.
 */
export function augmentPostSelect(postSelect: HTMLInputElement): void {
	api.loadPosts().then((data) =>
		data.forEach((post) => {
			const opt = document.createElement('option');
			opt.value = post.url;
			opt.textContent = post.title;
			postSelect.appendChild(opt);
		})
	);
}

/**
 * Augment date range controls.
 * Fill start/end based on selected range and revert to "custom" when changed manually.
 *
 * @param dateRangeSelect Date range select element.
 */
export function augmentDateRangeControls(
	dateRangeSelect: HTMLSelectElement
): void {
	const start = document.getElementById(
		'statify-content-datestart'
	) as HTMLInputElement;
	const end = document.getElementById(
		'statify-content-dateend'
	) as HTMLInputElement;
	if (start && end) {
		const isoDate = (y: number, m: number, d: number): string =>
			new Date(Date.UTC(y, m, d)).toISOString().split('T')[0];

		dateRangeSelect.addEventListener('change', (evt: Event) => {
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
		});

		start.addEventListener('change', () => {
			dateRangeSelect.value = 'custom';
		});
		end.addEventListener('change', () => {
			dateRangeSelect.value = 'custom';
		});
	}
}
