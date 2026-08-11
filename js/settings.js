(function () {
	'use strict';

	function initResetButton() {
		const resetButton = document.getElementById('statify-reset-data');

		if (!resetButton) {
			return;
		}

		resetButton.addEventListener('click', (e) => {
			e.preventDefault();

			const button = e.currentTarget;
			if (button.disabled) {
				return;
			}

			if (
				// eslint-disable-next-line no-alert
				!confirm(
					wp.i18n.__(
						'Are you sure you want to delete all statistics data? This action cannot be undone!',
						'statify'
					)
				)
			) {
				return;
			}

			const originalText = button.textContent;
			button.disabled = true;
			button.textContent = wp.i18n.__('Resetting…', 'statify');

			wp.apiFetch({
				path: '/statify/v1/reset',
				method: 'POST',
			})
				.then((data) => {
					// eslint-disable-next-line no-alert
					alert(data.message);
				})
				.catch((error) => {
					// eslint-disable-next-line no-alert
					alert(wp.i18n.__('Error:', 'statify') + error.message);
				})
				.finally(() => {
					button.disabled = false;
					button.textContent = originalText;
				});
		});
	}

	document.addEventListener('DOMContentLoaded', initResetButton);
})();
