<?php
declare( strict_types=1 );

/**
 * Validation applied to verify wether the bot should scan or skip the file specified
 */

/**
 * @param string $temp_file_name
 * @param string $file_name
 * @param string $commit_id
 * @param int $max_lines
 *
 * Validates if the file is valid to be scanned by vip-go-ci
 *
 * @return array
 *    [
 *        'issues' =>
 *        [
 *            'max-lines' => [$file_name],
 *        ],
 *        'total' => 1
 *    ]
 * In a oop we could simply inject a service for caching
 * Vars and set values in the constructor
 */
function vipgoci_validate( string $temp_file_name, string $file_name, string $commit_id, int $max_lines ): array {
	$validation_result = array( 'total' => 0 );

	if ( false === vipgoci_is_number_of_lines_valid( $temp_file_name, $file_name, $commit_id, $max_lines ) ) {
		$validation_result['issues'][ VIPGOCI_VALIDATION_MAXIMUM_LINES ] = [ $file_name ];
		$validation_result['total'] = count( $validation_result['issues'] );
	}

	return $validation_result;
}

/**
 * @param string $temp_file_name
 * @param string $file_name
 * @param string $commit_id
 * @param int $max_lines
 *
 * @return bool
 */
function vipgoci_is_number_of_lines_valid( string $temp_file_name, string $file_name, string $commit_id, int $max_lines ): bool {
	/**
	 * Verifies if number of lines validation are in the cache
	 * If so, returns the value
	 */

	$cache_key                = vipgoci_cache_get_is_number_of_lines_valid_key( $file_name, $commit_id );
	$is_number_of_lines_valid = vipgoci_cache_get_is_number_of_lines_valid( $cache_key );

	vipgoci_log(
		'Validating number of lines' .
			vipgoci_cached_indication_str( $is_number_of_lines_valid ),
		array(
			'file_name' => $file_name,
		)
	);

	if ( ! is_null( $is_number_of_lines_valid ) ) {
		return $is_number_of_lines_valid;
	}

	/**
	 * Calculates the file number of lines
	 * if it is > than the default limit (defined at defined)
	 * the bot won't scan it
	 */
	$cmd = sprintf( 'wc -l %s | awk \'{print $1;}\' 2>&1', escapeshellcmd( $temp_file_name ) );


	$output = vipgoci_runtime_measure_shell_exec_with_retry(
		$cmd,
		'file_validation'
	);

	vipgoci_log(
		( null === $output ) ?
			'Unable to validate number of lines, unable to execute utility' :
			'Ran utility to validate number of lines',
		array(
			'file_name' => $file_name,
			'cmd'       => $cmd,
			'output'    => $output,
		),
		( null === $output ) ? 0 : 2
	);

	if ( null === $output ) {
		return true;
	}

	$output = vipgoci_sanitize_string(
		$output
	);

	$is_number_of_lines_valid = vipgoci_verify_number_of_lines_output( $output, $max_lines );

	vipgoci_cache_set_is_number_of_lines_valid( $cache_key, $is_number_of_lines_valid );

	vipgoci_log(
		'Validated number of lines',
		array(
			'file_name' => $file_name,
			'output'    => $output,
		)
	);

	return $is_number_of_lines_valid;
}

/**
 * @param array $cache_key
 * @param bool $is_number_of_lines_valid
 *
 * Sets cache for converted is_number_of_lines_valid
 */
function vipgoci_cache_set_is_number_of_lines_valid( array $cache_key, bool $is_number_of_lines_valid ): void {
	vipgoci_cache(
		$cache_key,
		$is_number_of_lines_valid === true ? 'true' : 'false'
	);
}

/**
 * @param string $output
 * @param int $max_lines
 *
 * @return bool
 */
function vipgoci_verify_number_of_lines_output( string $output, int $max_lines ): bool {
	return is_numeric( $output ) && $output < $max_lines;
}

/**
 * @param array $cache_key
 *
 * Verifies if there's cached validation result available
 * returns it if it does, else returns null
 *
 * @return bool|null
 */
function vipgoci_cache_get_is_number_of_lines_valid( array $cache_key ): ?bool {
	$cached_value = vipgoci_cache( $cache_key );

	return false !== $cached_value ? 'true' === $cached_value : null;
}

/**
 * @param string $file_name
 * @param string $commit_id
 * Builds the cache key for the number of lines validation
 *
 * @return array
 */
function vipgoci_cache_get_is_number_of_lines_valid_key( string $file_name, string $commit_id ): array {
	return array( __FUNCTION__, $file_name, $commit_id );
}
