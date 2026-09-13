/**
 * Playwright global setup: reset the `alm-e2e` database to the committed
 * baseline snapshot before the suite runs.
 *
 * See `DEV/TODO/TODO_ResetPlan.md` (Fase 4) and `tests/e2e/README.md`,
 * "Database reset" section, for the full design.
 *
 * Skipped when `ALMGR_E2E_SKIP_RESET` is set — used by the `:headed`/`:ui`
 * debug variants (via `tests/e2e/bin/run-playwright-debug.js`) so interactive
 * debugging does not re-import the database on every launch.
 */

'use strict';

const path = require( 'path' );
const { runWpCli, getSitePath } = require( './bin/run-wp-cli' );

const BASELINE_SQL = path.join( __dirname, 'fixtures', 'baseline.sql' );

module.exports = async function globalSetup() {
	if ( process.env.ALMGR_E2E_SKIP_RESET ) {
		console.log( '[global-setup] ALMGR_E2E_SKIP_RESET is set — skipping database reset.' );
		return;
	}

	const sitePath = getSitePath();
	console.log( '[global-setup] Importing baseline snapshot into ' + sitePath + ' ...' );

	const result = runWpCli( [ 'db', 'import', BASELINE_SQL ], { sitePath } );

	if ( 0 !== result.status ) {
		throw new Error( '[global-setup] `wp db import` failed (exit code ' + result.status + '). See output above.' );
	}

	console.log( '[global-setup] Database reset to baseline.' );
};
