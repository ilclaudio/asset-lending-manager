/**
 * Reusable login helper for authenticated E2E specs.
 *
 * Credentials default to the baseline fixture seeded by
 * `tests/e2e/bin/seed-baseline.php` (see `DEV/TODO/TODO_ResetPlan.md`, Fase 2),
 * and can be overridden per environment via:
 *   - `ALMGR_E2E_OPERATOR_USER` / `ALMGR_E2E_OPERATOR_PASS`
 *   - `ALMGR_E2E_MEMBER_USER` / `ALMGR_E2E_MEMBER_PASS`
 *
 * Overriding the env vars only changes who a spec logs in as; it does not
 * regenerate `tests/e2e/fixtures/baseline.sql`. If you change the actual
 * fixture credentials, you must regenerate the baseline dump too (see
 * `tests/e2e/README.md`, "Regenerating the baseline").
 *
 * Usage in a spec:
 *
 *   const { loginAsMember } = require( '../support/auth' );
 *
 *   test( 'member sees their own assets', async ( { page } ) => {
 *     await loginAsMember( page );
 *     await page.goto( '/my-assets/' );
 *     // ...
 *   } );
 */

'use strict';

/**
 * @return {{username: string, password: string}}
 */
function getOperatorCredentials() {
	return {
		username: process.env.ALMGR_E2E_OPERATOR_USER || 'e2e-operator',
		password: process.env.ALMGR_E2E_OPERATOR_PASS || 'e2e-operator-pw',
	};
}

/**
 * Credentials for the baseline's first member (`e2e-member-1`), the one that
 * already owns the on-loan fixture asset. The second member
 * (`e2e-member-2`, no env var override provided) exists specifically for the
 * concurrent-request-cancellation scenario and is referenced directly by its
 * fixed username where needed.
 *
 * @return {{username: string, password: string}}
 */
function getMemberCredentials() {
	return {
		username: process.env.ALMGR_E2E_MEMBER_USER || 'e2e-member-1',
		password: process.env.ALMGR_E2E_MEMBER_PASS || 'e2e-member-1-pw',
	};
}

/**
 * Log in through the real `wp-login.php` form.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @param {{username: string, password: string}} credentials Login credentials.
 * @return {Promise<void>}
 */
async function loginAs( page, credentials ) {
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', credentials.username );
	await page.fill( '#user_pass', credentials.password );
	await Promise.all( [
		page.waitForNavigation(),
		page.click( '#wp-submit' ),
	] );
}

/**
 * Log in as the baseline operator (`e2e-operator`).
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<void>}
 */
async function loginAsOperator( page ) {
	await loginAs( page, getOperatorCredentials() );
}

/**
 * Log in as the baseline's first member (`e2e-member-1`).
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<void>}
 */
async function loginAsMember( page ) {
	await loginAs( page, getMemberCredentials() );
}

module.exports = {
	getOperatorCredentials,
	getMemberCredentials,
	loginAs,
	loginAsOperator,
	loginAsMember,
};
