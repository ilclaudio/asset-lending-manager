const { test, expect } = require( '@playwright/test' );
const { loginAsOperator, loginAsMember } = require( '../support/auth' );

/**
 * Covers the cooperative "Return asset" action on the asset detail page: an
 * operator returns an on-loan asset and it becomes available again.
 *
 * Uses the `e2e-on-loan-asset` fixture seeded by
 * `tests/e2e/bin/seed-baseline.php` (on loan to `e2e-member-1`). The baseline
 * runs with warehouse management off, so the form asks for a location.
 * Assertions are structural, not text-based, because `alm-e2e` renders in
 * Italian.
 *
 * Regression guard: the form used to hang on "Returning..." with no feedback
 * because the submit handler referenced an undefined `locationField` variable.
 */
test.describe( 'Cooperative return flow', () => {
	test( 'a member who is not allowed to return does not see the return form', async ( { page } ) => {
		// Member returns are disabled by default (workflow.member_return_enabled),
		// so even the owner of the on-loan asset gets no return form.
		await loginAsMember( page );

		const response = await page.goto( '/asset/e2e-on-loan-asset/', {
			waitUntil: 'domcontentloaded',
		} );
		expect( response ).not.toBeNull();
		expect( response.ok() ).toBeTruthy();

		await expect( page.locator( '.almgr-availability.almgr-state-on-loan' ) ).toHaveCount( 1 );
		await expect( page.locator( '#almgr-return-asset-form' ) ).toHaveCount( 0 );
	} );

	test( 'operator returns an on-loan asset and it becomes available', async ( { page } ) => {
		await loginAsOperator( page );

		const response = await page.goto( '/asset/e2e-on-loan-asset/', {
			waitUntil: 'domcontentloaded',
		} );
		expect( response ).not.toBeNull();
		expect( response.ok() ).toBeTruthy();

		await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error on this website.' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Fatal error' );

		// Before: on loan, with the return form open.
		await expect( page.locator( '.almgr-availability.almgr-state-on-loan' ) ).toHaveCount( 1 );
		const form = page.locator( '#almgr-return-asset-form' );
		await expect( form ).toBeVisible();

		await form.locator( '#almgr-return-asset-location' ).fill( 'E2E shelf 1' );
		await form.locator( '#almgr-return-asset-notes' ).fill( 'E2E automated return.' );

		// Submitting triggers an AJAX call followed by a full page reload.
		await Promise.all( [
			page.waitForURL( /almgr_status=success/ ),
			form.locator( 'button[type="submit"]' ).click(),
		] );

		await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error on this website.' );

		// After: available, and the return form is gone.
		await expect( page.locator( '.almgr-availability.almgr-state-available' ) ).toHaveCount( 1 );
		await expect( page.locator( '.almgr-availability.almgr-state-on-loan' ) ).toHaveCount( 0 );
		await expect( page.locator( '#almgr-return-asset-form' ) ).toHaveCount( 0 );
	} );
} );
