<?php
/**
 * CLI utility that fails predictably.
 *
 * Uses first argument to determine when and when not to fail.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/*
 * Do not display errors. If set to on, PHPUnit
 * tests will fail when this script fails.
 */
ini_set( 'display_errors', 'off' ); // phpcs:ignore WordPress.PHP.IniSet.display_errors_Blacklisted

if ( empty( $argv[1] ) ) {
	echo 'Incorrect usage' . PHP_EOL;
	exit( 10 );
} elseif ( false === is_dir( $argv[1] ) ) {
	echo 'First parameter is not a directory' . PHP_EOL;
	exit( 10 );
}

for ( $i = 0; $i < 2; $i++ ) {
	// Construct path to file.
	$file_name = $argv[1] . DIRECTORY_SEPARATOR . (int) $i;

	$file_contents = @file_get_contents( $file_name ); // phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged

	// If file does not exist, create it.
	if ( false === $file_contents ) {
		file_put_contents( $file_name, (int) $i );
		non_existant_function_500();
	}
}

/*
 * On third run with same $argv[1], print string
 * and exit with status 0.
 */
echo 'Success' . PHP_EOL;

exit( 0 );

