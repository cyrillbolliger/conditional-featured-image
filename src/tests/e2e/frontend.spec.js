/**
 * The front end in a block theme and a classic theme: the featured image is
 * gone from the singular view of an entry marked hidden, and still in every
 * list (blog, query loop).
 */
const { test, expect } = require( '@playwright/test' );
const { wpCli, featuredImage, visit } = require( './site' );

for ( const theme of [ 'twentytwentyfive', 'twentytwentyone' ] ) {
	test.describe( theme, () => {
		test.beforeAll( () => wpCli( 'theme', 'activate', theme ) );

		for ( const [ url, slug ] of [
			[ '/hidden-post/', 'hidden-post' ],
			[ '/hidden-page/', 'hidden-page' ],
			[ '/book/hidden-book/', 'hidden-book' ],
		] ) {
			test( `hides the image on ${ url }`, async ( { page } ) => {
				await visit( page, url );
				await expect( featuredImage( page, slug ) ).toHaveCount( 0 );
			} );
		}

		for ( const [ url, slug ] of [
			[ '/shown-post/', 'shown-post' ],
			[ '/shown-page/', 'shown-page' ],
		] ) {
			test( `shows the image on ${ url }`, async ( { page } ) => {
				await visit( page, url );
				await expect( featuredImage( page, slug ) ).toBeVisible();
			} );
		}

		test( 'shows hidden images in the blog list', async ( { page } ) => {
			await visit( page, '/' );
			await expect( featuredImage( page, 'hidden-post' ) ).toBeVisible();
			await expect( featuredImage( page, 'shown-post' ) ).toBeVisible();
		} );

		test( 'shows hidden images in a query loop on a hidden page', async ( {
			page,
		} ) => {
			await visit( page, '/query-loop-page/' );
			await expect( featuredImage( page, 'query-loop-page' ) ).toHaveCount(
				0
			);
			await expect( featuredImage( page, 'hidden-post' ) ).toBeVisible();
			await expect( featuredImage( page, 'shown-post' ) ).toBeVisible();
		} );
	} );
}
