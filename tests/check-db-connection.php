<?php
/**
 * Diagnostic script to verify direct DB connectivity for integration tests.
 *
 * Not a PHPUnit test — run manually to check the test DB connection.
 *
 * @package AssetLendingManager
 */

$link = mysqli_connect( 'localhost', 'admin', 'admin', 'wp_almgr_tests' );

if ( ! $link ) {
	die( 'Errore connessione: ' . mysqli_connect_error() );
}

echo "Connessione riuscita al database wordpress_test!\n";
mysqli_close( $link );
