<?php

declare( strict_types=1 );

if ( ! defined( 'VIPGOCI_UNIT_TESTING' ) ) {
	define( 'VIPGOCI_UNIT_TESTING', true );
}

if ( ! defined( 'VIPGOCI_UNIT_TESTS_INI_DIR_PATH' ) ) {
	define( 'VIPGOCI_UNIT_TESTS_INI_DIR_PATH', dirname( __DIR__ ) );
}

define( 'VIPGOCI_UNIT_TESTS_TEST_ID_KEY', 'vipgoci_unittests_test_ids' );

require_once( __DIR__ . '/IncludesForTestsOutputControl.php' );
require_once( __DIR__ . '/IncludesForTestsConfig.php' );
require_once( __DIR__ . '/IncludesForTestsRepo.php' );
require_once( __DIR__ . '/IncludesForTestsMisc.php' );

require_once( __DIR__ . '/../../requires.php' );
