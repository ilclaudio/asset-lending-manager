/**
 * Playwright global teardown: reset the `alm-e2e` database back to the
 * committed baseline snapshot after the suite finishes.
 *
 * Runs the same import as `global-setup.js` (decision 10 in
 * `DEV/TODO/TODO_ResetPlan.md`), so `alm-e2e` is left in a known, clean state
 * for manual inspection between work sessions rather than in whatever state
 * the last test mutated it to.
 *
 * Skipped when `ALMGR_E2E_SKIP_RESET` is set (see `global-setup.js`).
 */

'use strict';

const path = require( 'path' );
const { runWpCli, getSitePath } = require( './bin/run-wp-cli' );

const BASELINE_SQL = path.join( __dirname, 'fixtures', 'baseline.sql' );

module.exports = async function globalTeardown() {
	if ( process.env.ALMGR_E2E_SKIP_RESET ) {
		console.log( '[global-teardown] ALMGR_E2E_SKIP_RESET is set — skipping database reset.' );
		return;
	}

	const sitePath = getSitePath();
	console.log( '[global-teardown] Restoring baseline snapshot into ' + sitePath + ' ...' );

	const result = runWpCli( [ 'db', 'import', BASELINE_SQL ], { sitePath } );

	if ( 0 !== result.status ) {
		console.error( '[global-teardown] `wp db import` failed (exit code ' + result.status + '). ' +
			'alm-e2e may be left in a mutated state — see output above.' );
		return;
	}

	console.log( '[global-teardown] Database restored to baseline.' );
};
