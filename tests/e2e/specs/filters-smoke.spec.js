const { test, expect } = require( '@playwright/test' );

test.describe( 'Asset list filter smoke tests', () => {

	test( 'filter by type renders without a fatal frontend failure', async ( { page } ) => {
		// 'telescope' is a default term seeded by the plugin on activation.
		const response = await page.goto( '/asset/?almgr_type=telescope', {
			waitUntil: 'domcontentloaded',
		} );

		expect( response ).not.toBeNull();
		expect( response.ok() ).toBeTruthy();

		// The select must reflect the active filter — proves PHP read the URL param.
		await expect( page.locator( '#almgr_filter_type' ) ).toHaveValue( 'telescope' );

		// Results section and form are still rendered (not a 404 or error page).
		await expect( page.locator( '#almgr_asset_search_results' ) ).toBeVisible();
		await expect( page.locator( '.almgr-asset-search-form' ) ).toBeVisible();

		await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error on this website.' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Fatal error' );
	} );

	test( 'filter by structure renders without a fatal frontend failure', async ( { page } ) => {
		// 'kit' is a default term seeded by the plugin on activation.
		const response = await page.goto( '/asset/?almgr_structure=kit', {
			waitUntil: 'domcontentloaded',
		} );

		expect( response ).not.toBeNull();
		expect( response.ok() ).toBeTruthy();

		// The select must reflect the active filter.
		await expect( page.locator( '#almgr_filter_structure' ) ).toHaveValue( 'kit' );

		await expect( page.locator( '#almgr_asset_search_results' ) ).toBeVisible();
		await expect( page.locator( '.almgr-asset-search-form' ) ).toBeVisible();

		await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error on this website.' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Fatal error' );
	} );

	test( 'text search renders without a fatal frontend failure', async ( { page } ) => {
		// Read the first asset title from the archive to get a search term that
		// is guaranteed to match at least one result, without hardcoding data.
		await page.goto( '/asset/', { waitUntil: 'domcontentloaded' } );

		const firstTitle = await page
			.locator( '.almgr-asset-list .almgr-asset-title' )
			.first()
			.textContent();

		// Use only the first word to keep the query short.
		const searchTerm = ( firstTitle ?? '' ).trim().split( ' ' )[ 0 ];

		expect( searchTerm.length ).toBeGreaterThan( 0 );

		// Navigate with the search param.
		const response = await page.goto(
			`/asset/?s=${ encodeURIComponent( searchTerm ) }`,
			{ waitUntil: 'domcontentloaded' }
		);

		expect( response ).not.toBeNull();
		expect( response.ok() ).toBeTruthy();

		// The search input must reflect the submitted term.
		await expect( page.locator( '#almgr-search-input' ) ).toHaveValue( searchTerm );

		// At least one result should appear since the term came from a real asset title.
		await expect( page.locator( 'p.almgr-asset-search-count' ) ).toBeVisible();

		await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error on this website.' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Fatal error' );
	} );

} );
