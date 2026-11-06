/**
 * The plugin's end-to-end check, on the wp-env site with the fixture content
 * (`npm run env:start`, `npm run env:fixtures`). Run locally and in CI
 * (.github/workflows/): `npm run test:e2e`.
 *
 * One worker: the specs switch the site's theme and the Classic Editor, which
 * is global state.
 */
const { defineConfig } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: '.',
	outputDir: 'results',
	globalSetup: require.resolve( './global-setup' ),
	workers: 1,
	// In CI a flaky test passes on its retry: a compat run must not file a
	// failure for it.
	retries: process.env.CI ? 1 : 0,
	// In CI also the report to upload, and the results that
	// tests/env/summary.js lists in the PR or issue. Not in report/: the html
	// reporter empties it at the end of the run.
	reporter: process.env.CI
		? [
				[ 'list' ],
				[ 'html', { open: 'never', outputFolder: 'report' } ],
				[ 'json', { outputFile: 'results/results.json' } ],
			]
		: 'list',
	use: {
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8890',
		browserName: 'chromium',
		// A selector an update broke fails in 5 s, not after the test's 30.
		actionTimeout: 5000,
	},
} );
