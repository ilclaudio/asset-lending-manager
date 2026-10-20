const { test, expect } = require( '@playwright/test' );
const { loginAsOperator, loginAsMember } = require( '../support/auth' );

/**
 * Covers the direct-assignment E2E scenario of the tracked issue
 * "[Medium] Add focused E2E coverage for authenticated loan workflows"
 * (DEV/AGENTS/ISSUES_TODO.md): an operator assigns an asset directly to a
 * member from the asset detail page, and the asset goes on loan.
 *
 * Uses the `e2e-assign-asset` fixture seeded by
 * `tests/e2e/bin/seed-baseline.php`: an available, unowned component dedicated
 * to this spec. The assignee is `e2e-member-1`. Assertions are structural, not
 * text-based, because `alm-e2e` renders in Italian.
 */
test.describe( 'Direct assignment flow', () => {
	test( 'a member cannot see the direct assignment form', async ( { page } ) => {
		await loginAsMember( page );

		const response = await page.goto( '/asset/e2e-assign-asset/', {
			waitUntil: 'domcontentloaded',
		} );
		expect( response ).not.toBeNull();
		expect( response.ok() ).toBeTruthy();

		await expect( page.locator( '#almgr-direct-assign-form' ) ).toHaveCount( 0 );
		await expect( page.locator( '.almgr-collapsible--directassign' ) ).toHaveCount( 0 );
	} );

	test( 'operator assigns an available asset to a member and it goes on loan', async ( { page } ) => {
		await loginAsOperator( page );

		const response = await page.goto( '/asset/e2e-assign-asset/', {
			waitUntil: 'domcontentloaded',
		} );
		expect( response ).not.toBeNull();
		expect( response.ok() ).toBeTruthy();

		await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error on this website.' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Fatal error' );

		// Before: available.
		await expect( page.locator( '.almgr-availability.almgr-state-available' ) ).toHaveCount( 1 );

		// Open the direct assignment section and pick the assignee through the user autocomplete.
		await page.locator( '.almgr-collapsible--directassign summary' ).click();
		const form = page.locator( '#almgr-direct-assign-form' );
		await expect( form ).toBeVisible();

		await form.locator( '#almgr-direct-assign-user-input' ).fill( 'e2e-member-1' );
		const suggestion = page.locator( '#almgr-user-autocomplete-dropdown .almgr-autocomplete-item' ).first();
		await expect( suggestion ).toBeVisible();
		await suggestion.click();
		await expect( form.locator( '#almgr-direct-assign-user-id' ) ).not.toHaveValue( '' );

		await form.locator( '#almgr-direct-assign-reason' ).fill( 'E2E automated direct assignment.' );

		// Submitting triggers an AJAX call followed by a full page reload.
		await Promise.all( [
			page.waitForURL( /almgr_status=success/ ),
			form.locator( 'button[type="submit"]' ).click(),
		] );

		await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error on this website.' );

		// After: on loan.
		await expect( page.locator( '.almgr-availability.almgr-state-on-loan' ) ).toHaveCount( 1 );
		await expect( page.locator( '.almgr-availability.almgr-state-available' ) ).toHaveCount( 0 );
	} );
} );
