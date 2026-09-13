#!/usr/bin/env node
/**
 * Cross-platform wrapper for the interactive debug variants
 * (`npm run test:e2e:headed`, `npm run test:e2e:ui`).
 *
 * Sets `ALMGR_E2E_SKIP_RESET` so `global-setup.js`/`global-teardown.js` do not
 * re-import the baseline database on every interactive launch (decision 11 in
 * `DEV/TODO/TODO_ResetPlan.md`), then forwards all CLI arguments to
 * `playwright test`. A plain npm script cannot set an environment variable
 * portably across PowerShell/cmd.exe/POSIX shells without an extra
 * dependency (`cross-env`); this script avoids adding one.
 *
 * Usage (via package.json): node tests/e2e/bin/run-playwright-debug.js --headed
 */

'use strict';

const { spawnSync } = require( 'child_process' );

const extraArgs = process.argv.slice( 2 );

const result = spawnSync( 'npx', [ 'playwright', 'test' ].concat( extraArgs ), {
	stdio: 'inherit',
	shell: process.platform === 'win32',
	env: Object.assign( {}, process.env, { ALMGR_E2E_SKIP_RESET: '1' } ),
} );

process.exitCode = null === result.status ? 1 : result.status;
