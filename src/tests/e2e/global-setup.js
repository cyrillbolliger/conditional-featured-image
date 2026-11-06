/**
 * Logs in as the Admin once per run and saves the session to `LOGIN`, for
 * the editor specs. The rest run logged out.
 */
const path = require( 'path' );
const { chromium } = require( '@playwright/test' );

// Outside `results/`, which Playwright empties at the start of a run.
const LOGIN = path.join( __dirname, '.auth', 'login.json' );

const globalSetup = async ( config ) => {
	const browser = await chromium.launch();
	const page = await browser.newPage( {
		baseURL: config.projects[ 0 ].use.baseURL,
	} );
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', 'admin' );
	await page.fill( '#user_pass', 'password' );
	await page.click( '#wp-submit' );
	// The cookies come with the redirect; the dashboard loads feeds from
	// wordpress.org, which can take longer than the wait.
	await page.waitForURL( /\/wp-admin\/$/, { waitUntil: 'commit' } );
	await page.context().storageState( { path: LOGIN } );
	await browser.close();
};

module.exports = globalSetup;
module.exports.LOGIN = LOGIN;
