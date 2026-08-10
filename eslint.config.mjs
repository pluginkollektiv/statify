import wordpress from '@wordpress/eslint-plugin';
import globals from 'globals';

export default [
	...wordpress.configs.recommended.map((config) => ({
		...config,
		files: ['js/*.ts', 'js/**/*.ts', 'tests/js/*.test.ts'],
	})),
	...wordpress.configs.recommended.map((config) => ({
		...config,
		files: ['tests/js/*.test.js'],
	})),
	...wordpress.configs.es5.map((config) => ({
		...config,
		files: ['js/snippet.js'],
		rules: {
			'no-unused-vars': 'off',
		},
	})),
	{
		languageOptions: {
			globals: {
				...globals.browser,
				statifyAjax: 'readonly',
			},
		},
	},
];
