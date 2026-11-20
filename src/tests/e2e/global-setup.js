/**
 * Logs in as the Admin once per run (in CI with one retry) and saves the
 * session to `LOGIN`, for the editor specs. The rest run logged out.
 */
const path = require( 'path' );
const { chromium } = require( '@playwright/test' );

// Outside `results/`, which Playwright empties at the start of a run.
const LOGIN = path.join( __dirname, '.auth', 'login.json' );

const logIn = async ( baseURL ) => {
	const browser = await chromium.launch();
	try {
		const page = await browser.newPage( { baseURL } );
		await page.goto( '/wp-login.php' );
		await page.fill( '#user_login', 'admin' );
		await page.fill( '#user_pass', 'password' );
		await page.click( '#wp-submit' );
		// The cookies come with the redirect; the dashboard loads feeds from
		// wordpress.org, which can take longer than the wait. Any admin page: a
		// plugin's welcome screen (WooCommerce's setup wizard) can redirect the
		// dashboard away, and then `/wp-admin/` itself never commits.
		await page
			.waitForURL( /\/wp-admin\//, { waitUntil: 'commit' } )
			.catch( ( error ) => {
				error.message += `\nThe login ended on ${ page.url() }`;
				throw error;
			} );
		await page.context().storageState( { path: LOGIN } );
	} finally {
		await browser.close();
	}
};

const globalSetup = async ( config ) => {
	const baseURL = config.projects[ 0 ].use.baseURL;
	// In CI a flaky login is tried again, as `retries` does for a flaky test:
	// a failed global setup fails the whole run, and a compat run then files
	// an issue for the update. Unlike a flaky test, a retried login shows only
	// in the job's log, not in the results.
	if ( ! process.env.CI ) {
		return logIn( baseURL );
	}
	try {
		await logIn( baseURL );
	} catch ( firstError ) {
		// eslint-disable-next-line no-console -- the run's log shows the retry.
		console.warn(
			`The login failed, trying again:\n${ firstError.message }`
		);
		await logIn( baseURL ).catch( ( error ) => {
			error.message += `\nThe first attempt failed too:\n${ firstError.message }`;
			throw error;
		} );
	}
};

module.exports = globalSetup;
module.exports.LOGIN = LOGIN;
