const { defineConfig } = require( '@playwright/test' );

const baseURL = process.env.ALMGR_E2E_BASE_URL || 'https://alm-e2e.local';

module.exports = defineConfig( {
	testDir: './tests/e2e/specs',
	timeout: 30 * 1000,
	expect: {
		timeout: 5 * 1000,
	},
	use: {
		baseURL,
		headless: true,
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
	},
	reporter: 'list',
} );
