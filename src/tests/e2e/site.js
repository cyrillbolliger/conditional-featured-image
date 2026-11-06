/**
 * Helpers for the specs: WP-CLI on the wp-env site and the fixtures'
 * featured images (tests/fixtures/load.php).
 */
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );
const { expect } = require( '@playwright/test' );

const root = path.resolve( __dirname, '../..' );

// `wpCli( 'option', 'get', 'home' )` returns the trimmed output.
const wpCli = ( ...args ) =>
	execFileSync( 'npx', [ 'wp-env', 'run', 'cli', 'wp', '--', ...args ], {
		cwd: root,
		// wp-env reports on stderr; stdout is WP-CLI's alone.
		stdio: [ 'ignore', 'pipe', 'pipe' ],
	} )
		.toString()
		.trim();

const fixtureId = ( postType, slug ) =>
	wpCli(
		'post',
		'list',
		`--post_type=${ postType }`,
		`--name=${ slug }`,
		'--field=ID'
	);

const setHideFlag = ( id, hidden ) =>
	wpCli(
		'post',
		'meta',
		'update',
		id,
		'cybocfi_hide_featured_image',
		hidden ? 'yes' : ''
	);

// Every fixture has its own image file, named after its slug.
const featuredImage = ( page, slug ) =>
	page.locator( `img[src*="/uploads/"][src*="/${ slug }"]` );

// Opens the page and fails on PHP errors, warnings and notices the plugin's
// files raise (WP_DEBUG_DISPLAY prints them into the page).
const visit = async ( page, url ) => {
	const response = await page.goto( url );
	expect( response.status() ).toBe( 200 );
	expect( await page.content() ).not.toMatch(
		/(Fatal error|Warning|Notice|Deprecated)<\/b>:.*plugins\/conditional-featured-image\//
	);
};

module.exports = { wpCli, fixtureId, setHideFlag, featuredImage, visit };
