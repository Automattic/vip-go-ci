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

function vipgoci_unittests_get_config_values( $section, &$config_arr ) {
	foreach (
		array_keys( $config_arr ) as $config_key
	) {
		$config_arr[ $config_key ] =
			vipgoci_unittests_get_config_value(
				$section,
				$config_key
			);

		if ( empty( $config_arr[ $config_key ] ) ) {
			$config_arr[ $config_key ] = null;
		}
	}
}

require_once( __DIR__ . '/../vip-go-ci.php' );


