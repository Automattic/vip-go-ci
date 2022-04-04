#!/usr/bin/env php
<?php
/**
 * Helper script. Print to standard output and standard error, exit
 * with code 125.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

fprintf( STDOUT, 'test123' );
fprintf( STDERR, 'test456' );

exit( 125 );

