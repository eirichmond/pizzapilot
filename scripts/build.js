/**
 * PizzaPilot Plugin Build Script
 *
 * Creates a production-ready zip file excluding development files.
 * Usage: npm run build
 *
 * @package Pizzapilot
 */

const fs = require( 'fs' );
const path = require( 'path' );
const archiver = require( 'archiver' );

const PLUGIN_SLUG = 'pizzapilot';
const ROOT = path.resolve( __dirname, '..' );
const OUTPUT = path.join( ROOT, `${ PLUGIN_SLUG }.zip` );

// Files and directories to exclude from the zip.
const EXCLUDE = [
	'.git',
	'.gitignore',
	'.claude',
	'.DS_Store',
	'node_modules',
	'vendor',
	'composer.json',
	'composer.lock',
	'phpcs.xml.dist',
	'package.json',
	'package-lock.json',
	'tasks',
	'scripts',
	'PRD.md',
	'CLAUDE.md',
	'TASKS.md',
	`${ PLUGIN_SLUG }.zip`,
];

// Remove existing zip if present.
if ( fs.existsSync( OUTPUT ) ) {
	fs.unlinkSync( OUTPUT );
}

const output = fs.createWriteStream( OUTPUT );
const archive = archiver( 'zip', { zlib: { level: 9 } } );

output.on( 'close', () => {
	const size = ( archive.pointer() / 1024 ).toFixed( 1 );
	console.log( `\n✓ ${ PLUGIN_SLUG }.zip created (${ size } KB)` );
} );

archive.on( 'error', ( err ) => {
	throw err;
} );

archive.pipe( output );

// Read directory and add files, skipping excluded paths.
function addDirectory( dir, archivePath ) {
	const entries = fs.readdirSync( dir, { withFileTypes: true } );

	for ( const entry of entries ) {
		if ( EXCLUDE.includes( entry.name ) ) {
			continue;
		}

		const fullPath = path.join( dir, entry.name );
		const zipPath = path.join( archivePath, entry.name );

		if ( entry.isDirectory() ) {
			addDirectory( fullPath, zipPath );
		} else {
			archive.file( fullPath, { name: zipPath } );
		}
	}
}

console.log( `Building ${ PLUGIN_SLUG }.zip...` );
addDirectory( ROOT, PLUGIN_SLUG );
archive.finalize();
