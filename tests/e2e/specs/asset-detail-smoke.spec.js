const { test, expect } = require( '@playwright/test' );

test.describe( 'Asset detail smoke test', () => {
	test( 'asset detail page renders without a fatal frontend failure', async ( { page } ) => {
		// Start from the archive to find a real asset link without hardcoding a slug.
		const archiveResponse = await page.goto( '/asset/', {
			waitUntil: 'domcontentloaded',
		} );

		expect( archiveResponse ).not.toBeNull();
		expect( archiveResponse.ok() ).toBeTruthy();

		// Confirm the asset list rendered and contains at least one card.
		const assetList = page.locator( '.almgr-asset-list' );
		await expect( assetList ).toBeVisible();

		const firstLink = assetList.locator( '.almgr-asset-link' ).first();
		await expect( firstLink ).toBeVisible();

		// Follow the first asset link and capture the navigation response.
		const [ detailResponse ] = await Promise.all( [
			page.waitForResponse( ( r ) => r.status() === 200 || r.status() >= 400 ),
			firstLink.click(),
		] );

		expect( detailResponse.ok() ).toBeTruthy();

		// Verify the detail template rendered.
		await expect( page.locator( 'article.almgr-asset-view' ) ).toBeVisible();
		await expect( page.locator( 'h1.almgr-asset-title' ) ).toBeVisible();

		const title = await page.locator( 'h1.almgr-asset-title' ).textContent();
		expect( title?.trim().length ).toBeGreaterThan( 0 );

		// No WordPress fatal errors.
		await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error on this website.' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Fatal error' );
	} );
} );
