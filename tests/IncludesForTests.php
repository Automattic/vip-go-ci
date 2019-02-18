<?php

declare(strict_types=1);

if ( ! defined( 'VIPGOCI_UNIT_TESTING' ) ) {
	define( 'VIPGOCI_UNIT_TESTING', true );
}

function vipgoci_unittests_get_config_value(
	$section,
	$key
) {
	$ini_array = parse_ini_file(
		dirname( __FILE__ ) . '/../unittests.ini',
		true
	);

	if ( false === $ini_array ) {
		return null;
	}	

	if ( isset(
		$ini_array
			[ $section ]
			[ $key ]
	) ) {
		return $ini_array
			[ $section ]
			[ $key ];
	}

	return null;
}

require_once( __DIR__ . '/../vip-go-ci.php' );


