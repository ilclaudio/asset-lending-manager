const { test, expect } = require( '@playwright/test' );
const { loginAsMember } = require( '../support/auth' );

/**
 * Covers the first scenario of the tracked issue "[Medium] Add focused E2E
 * coverage for authenticated loan workflows" (DEV/AGENTS/ISSUES_TODO.md):
 * a member submits a loan request from the asset detail page, and a second
 * submission for the same asset is prevented, showing the expected
 * pending/duplicate state instead of the request form.
 *
 * Uses the `e2e-available-component` fixture asset seeded by
 * `tests/e2e/bin/seed-baseline.php` (available, unowned, not a kit) and is
 * reset to that known state by `global-setup.js`/`global-teardown.js` before
 * and after the suite runs (see DEV/TODO/TODO_ResetPlan.md) — this is the
 * first spec in this repository that mutates the database, rather than only
 * reading from it.
 */
test.describe( 'Loan request flow', () => {
	test( 'member submits a loan request and a second attempt is blocked', async ( { page } ) => {
		await loginAsMember( page );

		const response = await page.goto( '/asset/e2e-available-component/', {
			waitUntil: 'domcontentloaded',
		} );
		expect( response ).not.toBeNull();
		expect( response.ok() ).toBeTruthy();

		await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error on this website.' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Fatal error' );

		// Open the "Request loan" disclosure and fill in the form.
		const requestSection = page.locator( '#almgr-loan-request-section' );
		await requestSection.locator( 'summary' ).click();

		const form = page.locator( '#almgr-loan-request-form' );
		await expect( form ).toBeVisible();
		await form.locator( '#almgr-request-message' ).fill( 'E2E automated loan request test message.' );

		// Submitting triggers a client-side AJAX call followed by a full page
		// reload (see assets/js/frontend-assets.js, initLoanRequestForm) — wait
		// for that reload rather than the AJAX response itself.
		await Promise.all( [
			page.waitForURL( /almgr_status=success/ ),
			form.locator( 'button[type="submit"]' ).click(),
		] );

		// The request now exists: the server-rendered page shows the
		// already-requested notice instead of the form, and offers no way to
		// submit a second one. Assert structurally rather than on the notice's
		// text, which is translated (this environment runs in Italian).
		await expect( page.locator( '#almgr-loan-request-form' ) ).toHaveCount( 0 );
		// `<details>` is collapsed on a fresh page render, so the notice is
		// present in the DOM but not necessarily visually visible — check for
		// its existence rather than visibility.
		await expect( page.locator( '#almgr-loan-request-section p.almgr-muted' ) ).toHaveCount( 1 );

		// Re-navigating to the same page (a fresh, unrelated request) confirms
		// the pending state persists and isn't just a one-off post-submit view.
		await page.goto( '/asset/e2e-available-component/', { waitUntil: 'domcontentloaded' } );
		await expect( page.locator( '#almgr-loan-request-form' ) ).toHaveCount( 0 );
		await expect( page.locator( '#almgr-loan-request-section p.almgr-muted' ) ).toHaveCount( 1 );
	} );
} );
