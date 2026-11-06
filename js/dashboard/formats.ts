export default class Formats {
	private readonly number: Intl.NumberFormat;
	private readonly numberPercent: Intl.NumberFormat;
	private readonly dateYMD: Intl.DateTimeFormat;
	private readonly dateYM: Intl.DateTimeFormat;
	private readonly dateM: Intl.DateTimeFormat;

	constructor(lang: string | undefined) {
		this.number = new Intl.NumberFormat(lang);
		this.numberPercent = new Intl.NumberFormat(lang, {
			style: 'percent',
			minimumFractionDigits: 2,
			maximumFractionDigits: 2,
		});
		this.dateYMD = new Intl.DateTimeFormat(lang, {
			year: 'numeric',
			month: '2-digit',
			day: '2-digit',
		});
		this.dateYM = new Intl.DateTimeFormat(lang, {
			year: 'numeric',
			month: 'short',
		});
		this.dateM = new Intl.DateTimeFormat(lang, { month: 'short' });
	}

	public formatNumber(num: number): string {
		return this.number.format(num);
	}

	public formatPercent(num: number): string {
		return this.numberPercent.format(num);
	}

	public formatDateYMD(date: Date): string {
		return this.dateYMD.format(date);
	}

	public formatDateYM(date: Date): string {
		return this.dateYM.format(date);
	}

	public formatDateM(date: Date): string {
		return this.dateM.format(date);
	}
}
