/**
 * The checkbox in the block editor's featured image panel: it shows the
 * stored flag, and saving it hides the image on the front end. Also for a
 * post type registered by CPT UI, and for post types registered in code
 * without `custom-fields` support. The default of a new post
 * (`cybocfi_hide_by_default` filter, set by tests/env/hide-by-default.php).
 * And the editor script's dependencies and version.
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
	await visit(
		page,
		id
			? `/wp-admin/post.php?post=${ id }&action=edit`
			: '/wp-admin/post-new.php'
	);
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

// The global React is there only because wp-element depends on it: the
// plugin's editor script must not use it (#82).
test( 'shows the checkbox without the global React', async ( { page } ) => {
	let wrapped = false;
	await page.route(
		/\/plugins\/conditional-featured-image\/build\/index\.js/,
		async ( route ) => {
			const script = await ( await route.fetch() ).text();
			// Not the original headers: their length and encoding are those
			// of the compressed original.
			await route.fulfill( {
				contentType: 'text/javascript',
				body: `( function ( React ) {\n${ script }\n} )();`,
			} );
			wrapped = true;
		}
	);
	const checkbox = await openEditor(
		page,
		fixtureId( 'post', 'hidden-post' )
	);
	await expect( checkbox ).toBeChecked();
	expect( wrapped ).toBe( true );
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

// Registered by tests/env/post-types.php; the plugin adds the support.
test.describe( 'a post type without custom-fields support', () => {
	test( 'saves the checkbox', async ( { page } ) => {
		const id = wpCli(
			'post',
			'create',
			'--post_type=cybocfi_note',
			'--post_title=Note',
			'--post_status=publish',
			'--porcelain'
		);
		try {
			const checkbox = await openEditor( page, id );
			await expect( checkbox ).not.toBeChecked();
			await checkbox.check();
			await page
				.getByRole( 'region', { name: 'Editor top bar' } )
				.getByRole( 'button', { name: 'Save', exact: true } )
				.click();
			await expect(
				page.getByRole( 'button', { name: 'Dismiss this notice' } )
			).toContainText( 'updated.' );
			// `post meta get` fails if the post has none
			const flag = wpCli(
				'eval',
				`echo get_post_meta( ${ id }, 'cybocfi_hide_featured_image', true );`
			);
			expect( flag ).toBe( 'yes' );
		} finally {
			wpCli( 'post', 'delete', id, '--force' );
		}
	} );

	// Only where the checkbox shows: cybocfi_memo has no featured image.
	test( 'adds the support only for post types with a featured image', () => {
		const supports = JSON.parse(
			wpCli(
				'eval',
				`echo json_encode( array_map(
					function ( $post_type ) {
						return post_type_supports( $post_type, 'custom-fields' );
					},
					array( 'cybocfi_note', 'cybocfi_memo' )
				) );`
			)
		);
		expect( supports ).toEqual( [ true, false ] );
	} );

	// Core registers it on init, for the post types that have the support
	// by then.
	test( 'keeps the footnotes meta', () => {
		const exists = wpCli(
			'eval',
			`echo json_encode( registered_meta_key_exists( 'post', 'footnotes', 'cybocfi_note' ) );`
		);
		expect( exists ).toBe( 'true' );
	} );
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

test.describe( 'the default', () => {
	// '', 'on' or 'late' (tests/env/hide-by-default.php)
	const setHideByDefault = ( mode ) =>
		wpCli( 'option', 'update', 'cybocfi_test_hide_by_default', mode );

	// Also before: an interrupted run leaves the option set.
	test.beforeAll( () => setHideByDefault( '' ) );
	test.afterAll( () =>
		wpCli( 'option', 'delete', 'cybocfi_test_hide_by_default' )
	);

	test( 'leaves a new post shown when off', async ( { page } ) => {
		setHideByDefault( '' );
		const checkbox = await openEditor( page );
		await expect( checkbox ).not.toBeChecked();
	} );

	// An edit of meta the REST API lacks would make the editor ask to save.
	// tests/env/post-types.php registers both post types without
	// custom-fields support: the plugin adds it to cybocfi_note only.
	for ( const postType of [ 'cybocfi_note', 'cybocfi_memo' ] ) {
		test( `leaves a new ${ postType } unchanged when off`, async ( {
			page,
		} ) => {
			setHideByDefault( '' );
			await visit(
				page,
				`/wp-admin/post-new.php?post_type=${ postType }`
			);
			await expect(
				page.getByRole( 'region', { name: 'Editor top bar' } )
			).toBeVisible();
			// Leaves the editor script's effect time to run.
			const isDirty = await page.evaluate( async () => {
				await new Promise( ( resolve ) =>
					requestAnimationFrame( () => requestAnimationFrame( resolve ) )
				);
				return wp.data.select( 'core/editor' ).isEditedPostDirty();
			} );
			expect( isDirty ).toBe( false );
		} );
	}

	test( 'hides a new post when on', async ( { page } ) => {
		setHideByDefault( 'on' );
		const checkbox = await openEditor( page );
		await expect( checkbox ).toBeChecked();
	} );

	test( 'hides a new post when the filter comes late', async ( {
		page,
	} ) => {
		setHideByDefault( 'late' );
		const checkbox = await openEditor( page );
		await expect( checkbox ).toBeChecked();
	} );

	// The default is no edit of the user's: undo must not revert it.
	test( 'keeps the default on undo', async ( { page } ) => {
		setHideByDefault( 'late' );
		const checkbox = await openEditor( page );
		await expect( checkbox ).toBeChecked();
		const hasUndo = () =>
			page.evaluate( () =>
				wp.data.select( 'core/editor' ).hasEditorUndo()
			);
		const undo = () =>
			page.evaluate( () => wp.data.dispatch( 'core/editor' ).undo() );
		expect( await hasUndo() ).toBe( false );
		await undo();
		await expect( checkbox ).toBeChecked();

		await checkbox.uncheck();
		expect( await hasUndo() ).toBe( true );
		await undo();
		await expect( checkbox ).toBeChecked();
	} );

	test( 'keeps the saved value of an existing post', async ( { page } ) => {
		setHideByDefault( 'late' );
		const checkbox = await openEditor(
			page,
			fixtureId( 'post', 'shown-post' )
		);
		await expect( checkbox ).not.toBeChecked();
	} );

	// The featured image panel never mounts while the sidebar is closed.
	test.describe( 'with the sidebar closed', () => {
		// The Admin's editor preferences, as the editor stores them. Also
		// without the welcome guide: openEditor() doesn't run here.
		test.beforeAll( () =>
			wpCli(
				'user',
				'meta',
				'update',
				'admin',
				'wp_persisted_preferences',
				JSON.stringify( {
					core: { isComplementaryAreaVisible: false },
					'core/edit-post': { welcomeGuide: false },
				} ),
				'--format=json'
			)
		);
		// The editor's defaults; openEditor() sets what the other tests need.
		test.afterAll( () =>
			wpCli(
				'user',
				'meta',
				'delete',
				'admin',
				'wp_persisted_preferences'
			)
		);

		test( 'hides a new post when the filter comes late', async ( {
			page,
		} ) => {
			setHideByDefault( 'late' );
			await visit( page, '/wp-admin/post-new.php' );
			// Saving right away could beat the editor script; a user is slower.
			await page.waitForFunction(
				() =>
					window.wp?.data
						?.select( 'core/editor' )
						?.getEditedPostAttribute( 'meta' )
						?.cybocfi_hide_featured_image === 'yes'
			);
			const id = await page.evaluate( () =>
				wp.data.select( 'core/editor' ).getCurrentPostId()
			);
			try {
				await page.evaluate( () => {
					const editor = wp.data.dispatch( 'core/editor' );
					editor.editPost( { title: 'Late default' } );
					return editor.savePost();
				} );
				await expect( page.getByLabel( LABEL ) ).toHaveCount( 0 );
				// `post meta get` fails if the post has none
				const flag = wpCli(
					'eval',
					`echo get_post_meta( ${ id }, 'cybocfi_hide_featured_image', true );`
				);
				expect( flag ).toBe( 'yes' );
			} finally {
				wpCli( 'post', 'delete', id, '--force' );
			}
		} );
	} );

	// The panel unmounts while the sidebar shows the block tab.
	test( 'keeps the choice on a new post', async ( { page } ) => {
		setHideByDefault( 'late' );
		const checkbox = await openEditor( page );
		await expect( checkbox ).toBeChecked();
		await checkbox.uncheck();
		await page.evaluate( () =>
			wp.data
				.dispatch( 'core/edit-post' )
				.openGeneralSidebar( 'edit-post/block' )
		);
		await expect( checkbox ).toHaveCount( 0 );
		await page.evaluate( () =>
			wp.data
				.dispatch( 'core/edit-post' )
				.openGeneralSidebar( 'edit-post/document' )
		);
		await expect( checkbox ).not.toBeChecked();
	} );
} );
