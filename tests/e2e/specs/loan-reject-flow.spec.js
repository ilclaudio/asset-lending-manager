const { test, expect } = require( '@playwright/test' );
const { loginAsOperator } = require( '../support/auth' );

/**
 * Covers scenario (c), rejection half, of the tracked issue "[Medium] Add
 * focused E2E coverage for authenticated loan workflows"
 * (DEV/AGENTS/ISSUES_TODO.md): an operator rejects a pending loan request
 * from the asset detail page and the asset stays available.
 *
 * Uses the `e2e-reject-asset` fixture seeded by
 * `tests/e2e/bin/seed-baseline.php`: an available component with exactly one
 * pending request from `e2e-member-2`, dedicated to this spec. Assertions are
 * structural, not text-based, because `alm-e2e` renders in Italian.
 */
test.describe( 'Loan rejection flow', () => {
	test( 'operator rejects a pending request with a reason and the asset stays available', async ( { page } ) => {
		await loginAsOperator( page );

		const response = await page.goto( '/asset/e2e-reject-asset/', {
			waitUntil: 'domcontentloaded',
		} );
		expect( response ).not.toBeNull();
		expect( response.ok() ).toBeTruthy();

		await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error on this website.' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Fatal error' );

		// Before: available, with one pending request that can be rejected.
		await expect( page.locator( '.almgr-availability.almgr-state-available' ) ).toHaveCount( 1 );
		await page.locator( '.almgr-collapsible--requestlist summary' ).click();
		const rejectButton = page.locator( '.almgr-request-row [data-action="reject"]' );
		await expect( rejectButton ).toHaveCount( 1 );

		// Rejecting opens a modal that requires a reason, then reloads the page.
		await rejectButton.click();
		const form = page.locator( '#almgr-reject-request-form' );
		await expect( form ).toBeVisible();
		await form.locator( '#almgr-rejection-message' ).fill( 'E2E automated rejection reason.' );
		await Promise.all( [
			page.waitForURL( /almgr_status=success/ ),
			form.locator( 'button[type="submit"]' ).click(),
		] );

		await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error on this website.' );

		// After: still available, and the request is gone.
		await expect( page.locator( '.almgr-availability.almgr-state-available' ) ).toHaveCount( 1 );
		await expect( page.locator( '.almgr-availability.almgr-state-on-loan' ) ).toHaveCount( 0 );
		await expect( page.locator( '[data-action="reject"]' ) ).toHaveCount( 0 );
		await expect( page.locator( '.almgr-request-row' ) ).toHaveCount( 0 );
	} );
} );
