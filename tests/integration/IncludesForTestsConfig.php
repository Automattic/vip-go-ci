<?php

declare( strict_types=1 );

/**
 * Get configuration value from an INI config file, 
 *
 * @param string $section     Section of the configuration file selected.
 * @param string $key         Fetch value for this key.
 * @param bool   $secret_file Use secret file rather than the public one.
 *
 * @return null|string
 */
function vipgoci_unittests_get_config_value(
	string $section,
	string $key,
	bool $secret_file = false
) :?string {
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

/**
 * Get configuration values from an INI config file,
 *
 * @param string $section     Section of the configuration file selected.
 * @param array  $config_arr  Fetch value for these keys.
 * @param bool   $secret_file Use secret file rather than the public one.
 *
 * @return void 
 */
function vipgoci_unittests_get_config_values(
	string $section,
	array &$config_arr,
	bool $secret_file = false
) :void {
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

/**
 * Test if all options required for test are in place.
 *
 * @param array  $options              Array of options.
 * @param array  $options_not_required Array of options not required.
 * @param object $test_instance        Instance of test class.
 *
 * @return int
 */
function vipgoci_unittests_options_test(
	array $options,
	array $options_not_required,
	object &$test_instance
) :int {
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

