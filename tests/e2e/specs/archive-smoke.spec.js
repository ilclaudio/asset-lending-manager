const { test, expect } = require( '@playwright/test' );

test.describe( 'Asset archive smoke test', () => {
	test( 'asset archive page renders without a fatal frontend failure', async ( { page } ) => {
		const response = await page.goto( '/asset/', {
			waitUntil: 'domcontentloaded',
		} );

		expect( response ).not.toBeNull();
		expect( response.ok() ).toBeTruthy();

		await expect( page.locator( 'body' ) ).toBeVisible();
		await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error on this website.' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Fatal error' );
	} );
} );
