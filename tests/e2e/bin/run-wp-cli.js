/**
 * Shared helper to invoke `wp-cli.phar` against the `alm-e2e` site from
 * Node.js (global-setup/teardown, baseline seeding, ad hoc troubleshooting).
 *
 * Centralizes the three environment-specific pieces documented in
 * `DEV/TODO/TODO_ResetPlan.md` ("Note ambientali") and `tests/e2e/README.md`:
 *   - which PHP CLI binary runs the phar (`ALMGR_E2E_PHP_BIN`, default `php`);
 *   - where the `alm-e2e` site lives on disk (`ALMGR_E2E_SITE_PATH`, required);
 *   - where `mysql`/`mysqldump` live, since Local does not put them on PATH
 *     (auto-detected by `resolve-mysql-bin.js`, overridable via
 *     `ALMGR_E2E_MYSQL_BIN_DIR`).
 */

'use strict';

const path = require( 'path' );
const { spawnSync } = require( 'child_process' );
const { resolveMysqlBinDir, pathWithMysqlBin } = require( './resolve-mysql-bin' );

const WP_CLI_PHAR = path.join( __dirname, '..', '.bin', 'wp-cli.phar' );

/**
 * Normalize a filesystem path to forward slashes.
 *
 * `wp db import`/`export` shell out to the `mysql` client's `source` command
 * on Windows, which does its own backslash escape processing — a path like
 * `C:\Users\...` breaks it (`\U` is parsed as an unknown client command).
 * Forward slashes work fine on Windows for this purpose, so every path handed
 * to `wp-cli.phar` here is normalized before use.
 *
 * @param {string} filePath Path to normalize.
 * @return {string}
 */
function toWpPath( filePath ) {
	return filePath.split( path.sep ).join( '/' );
}

/**
 * Read the `alm-e2e` site path from the environment, or throw a clear error.
 *
 * @return {string}
 */
function getSitePath() {
	const sitePath = process.env.ALMGR_E2E_SITE_PATH;
	if ( ! sitePath ) {
		throw new Error(
			'ALMGR_E2E_SITE_PATH is not set. Point it at the filesystem root of the ' +
			'`alm-e2e` WordPress site (the folder containing wp-config.php) — see tests/e2e/README.md.'
		);
	}
	return toWpPath( sitePath );
}

/**
 * Run a `wp` command against the `alm-e2e` site.
 *
 * @param {string[]} args WP-CLI arguments, e.g. `[ 'db', 'export', 'file.sql' ]`.
 * @param {object}   [options] Optional overrides.
 * @param {string}   [options.sitePath] Override for `ALMGR_E2E_SITE_PATH`.
 * @return {import('child_process').SpawnSyncReturns<Buffer>}
 */
function runWpCli( args, options ) {
	options = options || {};
	const sitePath = options.sitePath ? toWpPath( options.sitePath ) : getSitePath();
	const phpBin = process.env.ALMGR_E2E_PHP_BIN || 'php';
	const mysqlBinDir = resolveMysqlBinDir();

	// Normalize any path-shaped argument (e.g. the baseline.sql file passed to
	// `db import`/`export`) the same way as the site path — see toWpPath().
	const normalizedArgs = args.map( ( arg ) => (
		'string' === typeof arg && arg.indexOf( path.sep ) !== -1 ? toWpPath( arg ) : arg
	) );

	const fullArgs = [ WP_CLI_PHAR, '--path=' + sitePath ].concat( normalizedArgs );

	const result = spawnSync( phpBin, fullArgs, {
		stdio: 'inherit',
		env: Object.assign( {}, process.env, {
			PATH: pathWithMysqlBin( mysqlBinDir ),
			Path: pathWithMysqlBin( mysqlBinDir ), // Windows env vars are case-insensitive but Node keeps both keys distinct.
		} ),
	} );

	if ( result.error ) {
		throw result.error;
	}

	return result;
}

module.exports = { runWpCli, getSitePath, toWpPath, WP_CLI_PHAR };
