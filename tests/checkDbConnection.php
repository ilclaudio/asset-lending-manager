<?php
$link = mysqli_connect( 'localhost', 'admin', 'admin', 'wp_alm_tests' );

if ( ! $link ) {
	die( 'Errore connessione: ' . mysqli_connect_error() );
}

echo "Connessione riuscita al database wordpress_test!\n";
mysqli_close( $link );
