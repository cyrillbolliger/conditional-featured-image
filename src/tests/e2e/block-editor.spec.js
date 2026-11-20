/**
 * The checkbox in the block editor's featured image panel: it shows the
 * stored flag, and saving it hides the image on the front end. Also for a
 * post type registered by CPT UI. And the editor script's dependencies and
 * version.
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

const LABEL =
	'Display featured image in post lists only, hide on singular views.';

test.use( { storageState: LOGIN } );

const openEditor = async ( page, id ) => {
	await visit( page, `/wp-admin/post.php?post=${ id }&action=edit` );
	await page.waitForFunction( () =>
		window.wp?.data?.select( 'core/editor' )?.getCurrentPostId()
	);
	await page.evaluate( () => {
		wp.data
			.dispatch( 'core/preferences' )
			.set( 'core/edit-post', 'welcomeGuide', false );
		wp.data.dispatch( 'core/edit-post' ).openGeneralSidebar(
			'edit-post/document'
		);
	} );
	return page.getByLabel( LABEL );
};

test( 'shows the stored flag', async ( { page } ) => {
	const checkbox = await openEditor(
		page,
		fixtureId( 'post', 'hidden-post' )
	);
	await expect( checkbox ).toBeChecked();
} );

test.describe( 'saving the checkbox', () => {
	const slug = 'block-editor-post';
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
		await page
			.getByRole( 'region', { name: 'Editor top bar' } )
			.getByRole( 'button', { name: 'Save', exact: true } )
			.click();
		await expect(
			page.getByRole( 'button', { name: 'Dismiss this notice' } )
		).toContainText( 'Post updated.' );

		await visit( page, `/${ slug }/` );
		await expect( featuredImage( page, slug ) ).toHaveCount( 0 );
	} );
} );

test( 'shows the checkbox for a CPT UI post type', async ( { page } ) => {
	const checkbox = await openEditor(
		page,
		fixtureId( 'book', 'hidden-book' )
	);
	await expect( checkbox ).toBeChecked();
} );

// The browser can't tell a missing dependency that another script loads
// first, so this asks WordPress what it registered for a block editor screen.
test( 'enqueues the script as the asset file says', () => {
	const { script, asset } = JSON.parse(
		wpCli(
			'eval',
			`require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
			require_once ABSPATH . 'wp-admin/includes/screen.php';
			$screen = WP_Screen::get( 'post' );
			$screen->is_block_editor( true );
			$screen->set_current_screen();
			do_action( 'enqueue_block_editor_assets' );
			$script = wp_scripts()->registered['cybocfi-script'];
			echo json_encode( array(
				'script' => array( 'deps' => $script->deps, 'ver' => $script->ver ),
				'asset'  => require CYBOCFI_PLUGIN_PATH . '/build/index.asset.php',
			) );`
		)
	);
	expect( script ).toEqual( {
		deps: asset.dependencies,
		ver: asset.version,
	} );
} );
