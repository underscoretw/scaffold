<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_underscoretw_autoloader = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $wpcli_underscoretw_autoloader ) ) {
	require_once $wpcli_underscoretw_autoloader;
}

WP_CLI::add_command( 'scaffold _tw', 'UnderscoreTW_Command' );
WP_CLI::add_command( 'scaffold underscoretw', 'UnderscoreTW_Command' );
