const { defineConfig } = require( '@playwright/test' );

const baseURL = process.env.ALMGR_E2E_BASE_URL || 'https://alm-e2e.local';

module.exports = defineConfig( {
	testDir: './tests/e2e/specs',
	timeout: 30 * 1000,
	expect: {
		timeout: 5 * 1000,
	},
	// Single worker: the suite mutates a shared `alm-e2e` database, so tests
	// must not run concurrently against it (see DEV/TODO/TODO_ResetPlan.md, Fase 4).
	workers: 1,
	globalSetup: require.resolve( './tests/e2e/global-setup.js' ),
	globalTeardown: require.resolve( './tests/e2e/global-teardown.js' ),
	use: {
		baseURL,
		headless: true,
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
	},
	reporter: 'list',
} );
