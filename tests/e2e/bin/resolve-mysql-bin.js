/**
 * Resolve the directory containing the `mysql`/`mysqldump` binaries used
 * under the hood by `wp db export`/`wp db import`.
 *
 * On Windows, LocalWP does not ship these as standalone system binaries: they
 * live inside Local's own per-engine runtime folder and are never added to
 * the system PATH (see `DEV/TODO/TODO_ResetPlan.md`, "Note ambientali").
 *
 * Resolution order:
 *   1. `ALMGR_E2E_MYSQL_BIN_DIR` env var, if set — used as-is, no validation
 *      beyond existence, so a developer can always force a specific path.
 *   2. Auto-detected Local "lightning-services" engine folder (Windows only).
 *   3. `null` — caller should assume the binaries are already on PATH and
 *      skip prepending anything.
 *
 * Usage as a module:
 *   const { resolveMysqlBinDir } = require( './resolve-mysql-bin' );
 *   const dir = resolveMysqlBinDir(); // string | null
 *
 * Usage standalone (troubleshooting):
 *   node tests/e2e/bin/resolve-mysql-bin.js
 */

'use strict';

const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const BIN_NAMES = process.platform === 'win32'
	? [ 'mysql.exe', 'mysqldump.exe' ]
	: [ 'mysql', 'mysqldump' ];

/**
 * Whether a directory contains both required binaries.
 *
 * @param {string} dir Candidate directory.
 * @return {boolean}
 */
function hasBinaries( dir ) {
	return BIN_NAMES.every( ( name ) => fs.existsSync( path.join( dir, name ) ) );
}

/**
 * Auto-detect Local's per-engine bin folder on Windows.
 *
 * Typical path:
 *   %APPDATA%/Local/lightning-services/mariadb-<version>/bin/win32/bin
 *
 * Local can have several engine versions installed side by side (one per
 * site's chosen PHP/DB stack); when more than one candidate is found, the
 * most recently modified one is preferred as a heuristic for "most recently
 * used by Local", but this is only a fallback — `ALMGR_E2E_MYSQL_BIN_DIR`
 * always wins when set, precisely because this heuristic can guess wrong.
 *
 * @return {string|null}
 */
function autoDetectWindows() {
	const appData = process.env.APPDATA;
	if ( ! appData ) {
		return null;
	}

	const servicesDir = path.join( appData, 'Local', 'lightning-services' );
	if ( ! fs.existsSync( servicesDir ) ) {
		return null;
	}

	let entries;
	try {
		entries = fs.readdirSync( servicesDir, { withFileTypes: true } );
	} catch ( err ) {
		return null;
	}

	const candidates = [];
	for ( const entry of entries ) {
		if ( ! entry.isDirectory() ) {
			continue;
		}
		if ( ! /^(mariadb|mysql)-/.test( entry.name ) ) {
			continue;
		}
		const candidateDir = path.join( servicesDir, entry.name, 'bin', 'win32', 'bin' );
		if ( hasBinaries( candidateDir ) ) {
			candidates.push( candidateDir );
		}
	}

	if ( 0 === candidates.length ) {
		return null;
	}

	candidates.sort( ( a, b ) => fs.statSync( b ).mtimeMs - fs.statSync( a ).mtimeMs );

	return candidates[ 0 ];
}

/**
 * Resolve the mysql/mysqldump bin directory, or null if the caller should
 * rely on PATH as-is.
 *
 * @return {string|null}
 */
function resolveMysqlBinDir() {
	if ( process.env.ALMGR_E2E_MYSQL_BIN_DIR ) {
		const overrideDir = process.env.ALMGR_E2E_MYSQL_BIN_DIR;
		if ( ! fs.existsSync( overrideDir ) ) {
			throw new Error( 'ALMGR_E2E_MYSQL_BIN_DIR is set to "' + overrideDir + '" but that directory does not exist.' );
		}
		return overrideDir;
	}

	if ( 'win32' === process.platform ) {
		return autoDetectWindows();
	}

	return null;
}

/**
 * Build a `PATH` value with the resolved directory prepended, for spawning
 * `wp db export`/`import` with mysql/mysqldump reachable.
 *
 * @param {string|null} mysqlBinDir Directory to prepend, or null.
 * @return {string}
 */
function pathWithMysqlBin( mysqlBinDir ) {
	const currentPath = process.env.PATH || process.env.Path || '';
	if ( ! mysqlBinDir ) {
		return currentPath;
	}
	return mysqlBinDir + path.delimiter + currentPath;
}

module.exports = { resolveMysqlBinDir, pathWithMysqlBin };

if ( require.main === module ) {
	try {
		const dir = resolveMysqlBinDir();
		if ( dir ) {
			console.log( '[resolve-mysql-bin] Resolved mysql/mysqldump directory: ' + dir );
		} else {
			console.log( '[resolve-mysql-bin] No directory resolved; assuming mysql/mysqldump are already on PATH.' );
			console.log( '[resolve-mysql-bin] Platform: ' + process.platform + ', OS: ' + os.type() );
		}
	} catch ( err ) {
		console.error( '[resolve-mysql-bin] ' + err.message );
		process.exitCode = 1;
	}
}
