<?php
/**
 * Point an isolated integration run at an owned, empty config directory.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

$config_dir = getenv( 'VIPGOCI_MISSING_CONFIG_DIR' );

if ( false === $config_dir || ! is_dir( $config_dir ) ) {
	throw new RuntimeException( 'Missing integration config fixture directory.' );
}

define( 'VIPGOCI_UNIT_TESTS_INI_DIR_PATH', $config_dir );

require_once dirname( __DIR__, 3 ) . '/test.bootstrap.php';
