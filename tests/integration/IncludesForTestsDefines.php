<?php
/**
 * Defines for test suites.
 *
 * @package Automattic/vip-go-ci
 */

declare( strict_types=1 );

if ( ! defined( 'VIPGOCI_UNIT_TESTING' ) ) {
	define( 'VIPGOCI_UNIT_TESTING', true );
}

if ( ! defined( 'VIPGOCI_UNIT_TESTS_INI_DIR_PATH' ) ) {
	define( 'VIPGOCI_UNIT_TESTS_INI_DIR_PATH', dirname( __DIR__ ) );
}

