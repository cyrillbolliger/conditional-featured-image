/**
 * The checkbox in the Classic Editor's featured image meta box: it shows the
 * stored flag, and saving it hides the image on the front end. The Classic
 * Editor is active only during this file.
 */
const { test, expect } = require( '@playwright/test' );
const { LOGIN } = require( './global-setup' );
const {
	wpCli,
	fixtureId,
	setHideFlag,
	featuredImage,
	visit,
} = require( './site' );

test.use( { storageState: LOGIN } );

test.beforeAll( () => wpCli( 'plugin', 'activate', 'classic-editor' ) );
test.afterAll( () => wpCli( 'plugin', 'deactivate', 'classic-editor' ) );

const openEditor = async ( page, id ) => {
	await visit( page, `/wp-admin/post.php?post=${ id }&action=edit` );
	return page.locator( '#postimagediv #cybocfi_hide_featured_image' );
};

test( 'shows the stored flag', async ( { page } ) => {
	const checkbox = await openEditor(
		page,
		fixtureId( 'post', 'hidden-post' )
	);
	await expect( checkbox ).toBeChecked();
} );

test.describe( 'saving the checkbox', () => {
	const slug = 'classic-editor-post';
	let id;

	test.beforeAll( () => {
		id = fixtureId( 'post', slug );
	} );
	test.afterAll( () => setHideFlag( id, false ) );

	test( 'hides the image on the front end', async ( { page } ) => {
		await visit( page, `/${ slug }/` );
		await expect( featuredImage( page, slug ) ).toBeVisible();

		const checkbox = await openEditor( page, id );
		await expect( checkbox ).not.toBeChecked();
		await checkbox.check();
		await page.click( '#publish' );
		await page.waitForURL( /message=1/ );
		await expect( checkbox ).toBeChecked();

		await visit( page, `/${ slug }/` );
		await expect( featuredImage( page, slug ) ).toHaveCount( 0 );
	} );
} );
