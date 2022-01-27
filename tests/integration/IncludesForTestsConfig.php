<?php

declare( strict_types=1 );

function vipgoci_unittests_get_config_value(
	$section,
	$key,
	$secret_file = false
) {
	if ( false === $secret_file ) {
		$ini_array = parse_ini_file(
			VIPGOCI_UNIT_TESTS_INI_DIR_PATH . '/../unittests.ini',
			true
		);
	}

	else {
		$ini_array = parse_ini_file(
			VIPGOCI_UNIT_TESTS_INI_DIR_PATH . '/../unittests-secrets.ini',
			true
		);
	}


	if ( false === $ini_array ) {
		return null;
	}	

	if ( isset(
		$ini_array
			[ $section ]
			[ $key ]
	) ) {
		if ( empty(
			$ini_array
				[ $section ]
				[ $key ]
		) ) {
			return null;
		}
			
		return $ini_array
			[ $section ]
			[ $key ];
	}

	return null;
}

function vipgoci_unittests_get_config_values( $section, &$config_arr, $secret_file = false ) {
	foreach (
		array_keys( $config_arr ) as $config_key
	) {
		$config_arr[ $config_key ] =
			vipgoci_unittests_get_config_value(
				$section,
				$config_key,
				$secret_file
			);

		if ( empty( $config_arr[ $config_key ] ) ) {
			$config_arr[ $config_key ] = null;
		}
	}
}

function vipgoci_unittests_options_test(
	$options,
	$options_not_required,
	&$test_instance
) {
	$missing_options_str = '';

	$options_keys = array_keys(
		$options
	);

	foreach(
		$options_keys as $option_key
	) {
		if ( in_array(
			$option_key,
			$options_not_required
		) ) {
			continue;
		}

		if (
			( '' === $options[ $option_key ] ) ||
			( null === $options[ $option_key ] )
		) {
			if ( '' !== $missing_options_str ) {
				$missing_options_str .= ', ';
			}

			$missing_options_str .= $option_key;
		}
	}

	if ( '' !== $missing_options_str ) {
		$test_instance->markTestSkipped(
			'Skipping test, not configured correctly, as some options are missing (' . $missing_options_str . ')'
		);

		return -1;
	}

	return 0;
}

