/**
 * WooCommerce in a block theme and a classic theme: a hidden featured image
 * is gone from the single product view (the gallery stays, the CSS closes the
 * empty space when there is no gallery), and still in the shop and the cart.
 */
const { test, expect } = require( '@playwright/test' );
const { wpCli, fixtureId, featuredImage, visit } = require( './site' );

for ( const theme of [ 'twentytwentyfive', 'twentytwentyone' ] ) {
	test.describe( theme, () => {
		test.beforeAll( () => wpCli( 'theme', 'activate', theme ) );

		test( 'hides the image of a product without gallery', async ( {
			page,
		} ) => {
			await visit( page, '/product/hidden-product/' );
			await expect( featuredImage( page, 'hidden-product' ) ).toHaveCount(
				0
			);
			// A <style> has no innerText, which toContainText() reads.
			expect(
				await page
					.locator( '#woocommerce-layout-inline-css' )
					.textContent()
			).toContain( '.woocommerce-product-gallery {display: none;}' );
		} );

		test( 'hides the image of a product but keeps its gallery', async ( {
			page,
		} ) => {
			await visit( page, '/product/gallery-product/' );
			await expect(
				featuredImage( page, 'gallery-product' )
			).toHaveCount( 0 );
			await expect(
				featuredImage( page, 'gallery-image' ).first()
			).toBeVisible();
			await expect(
				page.locator( '#woocommerce-layout-inline-css' )
			).toHaveCount( 0 );
		} );

		test( 'shows the image of a product not marked hidden', async ( {
			page,
		} ) => {
			await visit( page, '/product/shown-product/' );
			await expect(
				featuredImage( page, 'shown-product' ).first()
			).toBeVisible();
		} );

		test( 'shows hidden images in the shop', async ( { page } ) => {
			await visit( page, '/shop/' );
			await expect(
				featuredImage( page, 'hidden-product' )
			).toBeVisible();
		} );

		test( 'shows hidden images in the cart', async ( { page } ) => {
			const id = fixtureId( 'product', 'hidden-product' );
			await visit( page, `/?add-to-cart=${ id }` );
			await visit( page, '/cart/' );
			await expect(
				featuredImage( page, 'hidden-product' ).first()
			).toBeVisible();
		} );
	} );
}
