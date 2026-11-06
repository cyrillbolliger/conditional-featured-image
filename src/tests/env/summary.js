/**
 * The Playwright results of a compat run (report/results.json, the JSON
 * reporter's file) as Markdown for its PR or issue: the counts and the
 * failed tests. `node tests/env/summary.js <results.json>`
 */
const fs = require( 'fs' );

const { stats, suites } = JSON.parse( fs.readFileSync( process.argv[ 2 ] ) );

const failed = [];
const collect = ( suite, titles ) => {
	for ( const spec of suite.specs ?? [] ) {
		if ( spec.tests.some( ( test ) => test.status === 'unexpected' ) ) {
			failed.push( [ ...titles, spec.title ].join( ' › ' ) );
		}
	}
	for ( const child of suite.suites ?? [] ) {
		collect( child, [ ...titles, child.title ] );
	}
};
for ( const suite of suites ) {
	collect( suite, [ suite.title ] );
}

const lines = [
	`${ stats.expected } passed, ${ stats.unexpected } failed, ${ stats.flaky } flaky, ${ stats.skipped } skipped.`,
];
if ( failed.length ) {
	lines.push(
		'',
		'Failed:',
		'',
		...failed.map( ( title ) => `- ${ title }` )
	);
}
process.stdout.write( lines.join( '\n' ) + '\n' );
