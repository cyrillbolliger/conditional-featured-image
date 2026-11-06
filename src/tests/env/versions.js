// A pre-release (7.2-beta1, 7.2-RC2) is a stable version's predecessor. Four
// parts, as WordPress's security releases have.
const versionKey = ( version ) => {
	const [ , base, stage = 'stable', number = '0' ] = version.match(
		/^([\d.]+)(?:-(beta|RC)(\d*))?$/
	);
	const parts = base.split( '.' ).map( Number );
	while ( parts.length < 4 ) {
		parts.push( 0 );
	}
	return [ ...parts, [ 'beta', 'RC', 'stable' ].indexOf( stage ), +number ];
};

const isNewer = ( version, than ) => {
	const a = versionKey( version );
	const b = versionKey( than );
	const i = a.findIndex( ( part, k ) => part !== b[ k ] );
	return i !== -1 && a[ i ] > b[ i ];
};

module.exports = { isNewer };
