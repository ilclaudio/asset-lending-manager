#!/usr/bin/env node
/**
 * Idempotent WP-CLI bootstrap for the E2E database reset lane.
 *
 * Downloads `wp-cli.phar` into `tests/e2e/.bin/` if it is not already present.
 * The file is intentionally not committed to the repository (see
 * `tests/e2e/README.md`, "Database reset" section, and `.gitignore`).
 *
 * Usage:
 *   node tests/e2e/bin/ensure-wp-cli.js
 *
 * Runs automatically before `npm run test:e2e` via the `pretest:e2e` script
 * in package.json. Safe to run manually or repeatedly: it does nothing if the
 * file already exists.
 */

'use strict';

const fs = require( 'fs' );
const https = require( 'https' );
const path = require( 'path' );

const WP_CLI_URL = 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar';
const TARGET_DIR = path.join( __dirname, '..', '.bin' );
const TARGET_FILE = path.join( TARGET_DIR, 'wp-cli.phar' );

/**
 * Download a URL to a destination path, following redirects.
 *
 * @param {string} url URL to download.
 * @param {string} destPath Destination file path.
 * @param {number} redirectsLeft Remaining redirect hops allowed.
 * @return {Promise<void>}
 */
function download( url, destPath, redirectsLeft ) {
	if ( undefined === redirectsLeft ) {
		redirectsLeft = 5;
	}

	return new Promise( ( resolve, reject ) => {
		const request = https.get( url, ( response ) => {
			const status = response.statusCode || 0;

			if ( status >= 300 && status < 400 && response.headers.location ) {
				response.resume();
				if ( redirectsLeft <= 0 ) {
					reject( new Error( 'Too many redirects while downloading wp-cli.phar.' ) );
					return;
				}
				download( response.headers.location, destPath, redirectsLeft - 1 ).then( resolve, reject );
				return;
			}

			if ( status !== 200 ) {
				response.resume();
				reject( new Error( 'Unexpected HTTP status ' + status + ' while downloading wp-cli.phar from ' + url ) );
				return;
			}

			const tmpPath = destPath + '.download';
			const fileStream = fs.createWriteStream( tmpPath );

			response.pipe( fileStream );

			fileStream.on( 'finish', () => {
				fileStream.close( ( closeErr ) => {
					if ( closeErr ) {
						reject( closeErr );
						return;
					}
					fs.rename( tmpPath, destPath, ( renameErr ) => {
						if ( renameErr ) {
							reject( renameErr );
							return;
						}
						resolve();
					} );
				} );
			} );

			fileStream.on( 'error', ( streamErr ) => {
				fs.unlink( tmpPath, () => reject( streamErr ) );
			} );
		} );

		request.on( 'error', reject );
	} );
}

async function main() {
	if ( fs.existsSync( TARGET_FILE ) ) {
		console.log( '[ensure-wp-cli] wp-cli.phar already present at ' + TARGET_FILE + ', nothing to do.' );
		return;
	}

	fs.mkdirSync( TARGET_DIR, { recursive: true } );

	console.log( '[ensure-wp-cli] Downloading wp-cli.phar from ' + WP_CLI_URL + ' ...' );
	await download( WP_CLI_URL, TARGET_FILE );

	const stats = fs.statSync( TARGET_FILE );
	if ( stats.size < 1024 * 1024 ) {
		fs.unlinkSync( TARGET_FILE );
		throw new Error( 'Downloaded wp-cli.phar looks too small (' + stats.size + ' bytes) — aborting, nothing left in place.' );
	}

	console.log( '[ensure-wp-cli] wp-cli.phar downloaded (' + stats.size + ' bytes) to ' + TARGET_FILE + '.' );
}

main().catch( ( err ) => {
	console.error( '[ensure-wp-cli] Failed: ' + err.message );
	console.error( '[ensure-wp-cli] You can also download it manually from https://wp-cli.org/ and place it at ' + TARGET_FILE + '.' );
	process.exitCode = 1;
} );
