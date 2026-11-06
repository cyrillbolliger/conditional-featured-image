/**
 * The pinned versions the test site runs, in .wp-env.json: core and the
 * Classic Editor. WooCommerce, CPT UI and the themes are not pinned: wp-env
 * installs their latest stable version.
 *
 * `node tests/env/pins.js detect` prints the updates to test, as the matrix
 * of update-compat.yml: one `{ component, from, to, channel }` per update,
 * newer than its pin and without an open PR or issue. COMPONENT and VERSION
 * (workflow_dispatch) name one update instead.
 *
 * `node tests/env/pins.js override <component> <version>` writes the update
 * to .wp-env.override.json, for wp-env to start on it.
 *
 * `node tests/env/pins.js move <component> <version>` moves the pin; for core
 * also `Tested up to` in README.txt.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );
const { isNewer } = require( './versions' );

const root = path.resolve( __dirname, '../..' );
const file = ( name ) => path.join( root, name );
const read = ( name ) => fs.readFileSync( file( name ), 'utf8' );
const readJson = ( name ) => JSON.parse( read( name ) );
const writeJson = ( name, data ) =>
	fs.writeFileSync( file( name ), JSON.stringify( data, null, '\t' ) + '\n' );

const PLUGINS = [ 'classic-editor' ];

const coreZip = ( version ) =>
	`https://downloads.wordpress.org/release/wordpress-${ version }.zip`;
const pluginZip = ( slug, version ) =>
	`https://downloads.wordpress.org/plugin/${ slug }.${ version }.zip`;

const pin = ( component ) => {
	const env = readJson( '.wp-env.json' );
	if ( component === 'core' ) {
		return env.core.match( /wordpress-(.+)\.zip$/ )[ 1 ];
	}
	const prefix = `https://downloads.wordpress.org/plugin/${ component }.`;
	return env.plugins
		.find( ( plugin ) => plugin.startsWith( prefix ) )
		.slice( prefix.length, -'.zip'.length );
};

// The keys of .wp-env.json that change for the update.
const updatedEnv = ( component, version ) => {
	const env = readJson( '.wp-env.json' );
	if ( component === 'core' ) {
		return { core: coreZip( version ) };
	}
	const old = pluginZip( component, pin( component ) );
	return {
		plugins: env.plugins.map( ( plugin ) =>
			plugin === old ? pluginZip( component, version ) : plugin
		),
	};
};

// What versions.js compares: a release, or a core beta or RC.
const VERSION = /^[\d.]+(-(beta|RC)\d*)?$/;

const channel = ( version ) =>
	/^[\d.]+$/.test( version ) ? 'stable' : 'pre';

const getJson = async ( url ) => {
	const response = await fetch( url );
	if ( ! response.ok ) {
		throw new Error( `${ url }: ${ response.status }` );
	}
	return response.json();
};

// In CI the repo's token (GH_TOKEN) reads its PRs and issues; locally gh's
// login does.
const gh = ( args ) => execFileSync( 'gh', args, { encoding: 'utf8' } ).trim();

// The newest stable core and, if newer still, the newest pre-release.
const coreCandidates = async () => {
	const { offers } = await getJson(
		'https://api.wordpress.org/core/version-check/1.7/?channel=beta'
	);
	const versions = offers
		.map( ( offer ) => offer.version )
		.filter( ( version ) => VERSION.test( version ) );
	const newest = ( list ) =>
		list.reduce( ( a, b ) => ( isNewer( b, a ) ? b : a ) );
	const stable = newest(
		versions.filter( ( version ) => channel( version ) === 'stable' )
	);
	const pre = versions.filter(
		( version ) =>
			channel( version ) === 'pre' && isNewer( version, stable )
	);
	return pre.length ? [ stable, newest( pre ) ] : [ stable ];
};

const pluginCandidate = async ( slug ) =>
	(
		await getJson(
			`https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=${ slug }`
		)
	).version;

const issueTitle = ( component, version ) =>
	`Compat: ${ component } ${ version } fails`;
const branchName = ( component, version ) =>
	`compat/${ component }-${ version }`;

// An open PR or issue for the update: tested before, not again.
const isReported = ( component, version ) => {
	const prs = gh( [
		'pr',
		'list',
		'--state',
		'open',
		'--head',
		branchName( component, version ),
		'--json',
		'number',
		'-q',
		'length',
	] );
	const issues = JSON.parse(
		gh( [
			'issue',
			'list',
			'--state',
			'open',
			'--label',
			'compat',
			'--json',
			'title',
		] )
	);
	return (
		prs !== '0' ||
		issues.some(
			( { title } ) => title === issueTitle( component, version )
		)
	);
};

const detect = async () => {
	const { COMPONENT: component, VERSION: version } = process.env;
	if ( component ) {
		if ( ! [ 'core', ...PLUGINS ].includes( component ) ) {
			throw new Error( `Unknown component: ${ component }` );
		}
		if ( ! VERSION.test( version ) ) {
			throw new Error( `Not a version: ${ version }` );
		}
		return [
			{
				component,
				from: pin( component ),
				to: version,
				channel: channel( version ),
			},
		];
	}
	const candidates = [
		...( await coreCandidates() ).map( ( to ) => [ 'core', to ] ),
		...( await Promise.all(
			PLUGINS.map( async ( slug ) => [
				slug,
				await pluginCandidate( slug ),
			] )
		) ),
	];
	// Only a plugin's releases: its version is whatever its author tagged.
	// Core's are filtered above.
	return candidates
		.filter( ( [ name, to ] ) => name === 'core' || /^[\d.]+$/.test( to ) )
		.filter( ( [ name, to ] ) => isNewer( to, pin( name ) ) )
		.filter( ( [ name, to ] ) => ! isReported( name, to ) )
		.map( ( [ name, to ] ) => ( {
			component: name,
			from: pin( name ),
			to,
			channel: name === 'core' ? channel( to ) : 'stable',
		} ) );
};

const override = ( component, version ) =>
	writeJson( '.wp-env.override.json', updatedEnv( component, version ) );

// Only forward: a manual run of an older version must not move the pin back.
const move = ( component, version ) => {
	if ( ! isNewer( version, pin( component ) ) ) {
		console.log(
			`::notice::${ component } ${ version } passes; the pin ${ pin(
				component
			) } is not older and stays.`
		);
		return;
	}
	writeJson( '.wp-env.json', {
		...readJson( '.wp-env.json' ),
		...updatedEnv( component, version ),
	} );
	if ( component === 'core' ) {
		const testedUpTo = version.split( '.' ).slice( 0, 2 ).join( '.' );
		fs.writeFileSync(
			file( 'README.txt' ),
			read( 'README.txt' ).replace(
				/^Tested up to: .*$/m,
				`Tested up to: ${ testedUpTo }`
			)
		);
	}
};

const [ command, ...args ] = process.argv.slice( 2 );
if ( command === 'detect' ) {
	detect().then( ( matrix ) =>
		process.stdout.write( JSON.stringify( matrix ) + '\n' )
	);
} else if ( command === 'override' ) {
	override( ...args );
} else if ( command === 'move' ) {
	move( ...args );
} else {
	throw new Error( `Unknown command: ${ command }` );
}
