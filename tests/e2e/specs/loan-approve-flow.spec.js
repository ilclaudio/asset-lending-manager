const { test, expect } = require( '@playwright/test' );
const { loginAsOperator } = require( '../support/auth' );

/**
 * Covers scenario (c), approval half, of the tracked issue "[Medium] Add
 * focused E2E coverage for authenticated loan workflows"
 * (DEV/AGENTS/ISSUES_TODO.md): an operator approves a pending loan request
 * from the asset detail page and the resulting state is shown.
 *
 * Uses the `e2e-approve-asset` fixture seeded by
 * `tests/e2e/bin/seed-baseline.php`: an available component with exactly one
 * pending request from `e2e-member-2`. The asset is dedicated to this spec so
 * the order in which specs run does not matter. Assertions are structural
 * (classes, element presence), not text-based, because `alm-e2e` renders in
 * Italian.
 */
test.describe( 'Loan approval flow', () => {
	test( 'operator approves a pending request and the asset goes on loan', async ( { page } ) => {
		await loginAsOperator( page );

		const response = await page.goto( '/asset/e2e-approve-asset/', {
			waitUntil: 'domcontentloaded',
		} );
		expect( response ).not.toBeNull();
		expect( response.ok() ).toBeTruthy();

		await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error on this website.' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Fatal error' );

		// Before: available, with one pending request that can be approved.
		await expect( page.locator( '.almgr-availability.almgr-state-available' ) ).toHaveCount( 1 );
		await page.locator( '.almgr-collapsible--requestlist summary' ).click();
		const approveButton = page.locator( '.almgr-request-row [data-action="approve"]' );
		await expect( approveButton ).toHaveCount( 1 );

		// Approving asks for confirmation in a modal, then reloads the page.
		await approveButton.click();
		const confirmModal = page.locator( '.almgr-confirm-modal.active' );
		await expect( confirmModal ).toBeVisible();
		await Promise.all( [
			page.waitForURL( /almgr_status=success/ ),
			confirmModal.locator( '.almgr-button--primary' ).click(),
		] );

		await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error on this website.' );

		// After: on loan, and no pending request is left to act on.
		await expect( page.locator( '.almgr-availability.almgr-state-on-loan' ) ).toHaveCount( 1 );
		await expect( page.locator( '.almgr-availability.almgr-state-available' ) ).toHaveCount( 0 );
		await expect( page.locator( '[data-action="approve"]' ) ).toHaveCount( 0 );
		await expect( page.locator( '.almgr-request-row' ) ).toHaveCount( 0 );
	} );
} );
