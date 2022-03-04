#!/usr/bin/env php
<?php
/**
 * Emulate PHP linting. Will emulate different PHP versions depending on
 * how the script is invoked.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

global $argv;

if ( str_contains( $argv[0], 'fail' ) ) {
	$options = getopt( 'l:d:' );

	if ( empty( $options['l'] ) ) {
		$file_name = 'file.php';
	} else {
		$file_name = $options['l'];
	}

	if ( ( str_contains( $argv[0], '7.3' ) ) || ( str_contains( $argv[0], '7.4' ) ) ) {
		echo "PHP Parse error:  syntax error, unexpected ';' in " . $file_name . ' on line 3' . PHP_EOL;
	} elseif ( ( str_contains( $argv[0], '8.0' ) ) || ( str_contains( $argv[0], '8.1' ) ) ) {
		echo 'PHP Parse error:  syntax error, unexpected token ";" in ' . $file_name . ' on line 3' . PHP_EOL;
	} else {
		echo "PHP Parse error:  syntax error, unexpected ';' in " . $file_name . ' on line 3' . PHP_EOL;
	}

	echo 'Errors parsing ' . $file_name . PHP_EOL;

	exit( 255 );
} else {
	echo 'No syntax errors detected in ' . $file_name . PHP_EOL;

	exit( 0 );
}

