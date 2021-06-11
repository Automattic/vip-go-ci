<?php
declare(strict_types=1);

/**
 * Validation applied to verify wether the bot should scan or skip the file specified
 */

/**
 * @param $temp_file_name
 * @param $file_name
 * @param string $commit_id
 *
 * Validates if the file is valid to be scanned by vip-go-ci
 *
 * @return array
 *	[
 *		'issues' =>
 *		[
 *			'max-lines' => [$file_name],
 *		],
 *		'total' => 1
 *	]
 * In a oop we could simply inject a service for caching
 * Vars and set values in the constructor
 */
function vipgoci_validate( string $temp_file_name, string $file_name, string $commit_id ): array
{
	$validation_result = array( 'total' => 0 );

	if ( false === vipgoci_is_number_of_lines_valid( $temp_file_name, $file_name, $commit_id ) ) {
		$validation_result[ 'issues' ][ VIPGOCI_VALIDATION_MAXIMUM_LINES ] = [ $file_name ];
		$validation_result[ 'total' ] = count( $validation_result[ 'issues' ] );
	}

	return $validation_result;
}

/**
 * @param string $temp_file_name
 * @param string $file_name
 * @param string $commit_id
 *
 * @return bool
 */
function vipgoci_is_number_of_lines_valid( string $temp_file_name, string $file_name, string $commit_id ): bool
{
	/**
	 * Verifies if number of lines validation are in the cache
	 * If so, returns the value
	 */
	$cache_key = vipgoci_cache_get_is_number_of_lines_valid_key( $commit_id );
	$current_cached_value = vipgoci_cache_get_is_number_of_lines_valid( $cache_key );
	$is_number_of_lines_valid = vipgoci_cache_get_is_number_of_lines_valid_by_file_name( $current_cached_value, $file_name );
	if ( ! is_null( $is_number_of_lines_valid ) ) {
		return $is_number_of_lines_valid;
	}

	/**
	 * Calculates the file number of lines
	 * if it is > than the default limit (defined at defined)
	 * the bot won't scan it
	 */
	$cmd = sprintf( 'wc -l %s | awk \'{print $1;}\' 2>&1', escapeshellcmd( $temp_file_name ) );
	vipgoci_log( 'Validating file number of lines', array( 'file_name' => $file_name ) );

	$output = vipgoci_sanitize_string(
		vipgoci_runtime_measure_shell_exec( $cmd, 'file_validation' )
	);

	vipgoci_log(
		sprintf( 'Validation number of lines ', $file_name ),
		array( 'file_name' => $file_name, 'cmd' => $cmd, 'output' => $output ), 0
	);

	$is_number_of_lines_valid = vipgoci_verify_number_of_lines_output( $output );
	vipgoci_cache_set_is_number_of_lines_valid_by_file_name( $cache_key, $current_cached_value, $file_name, $is_number_of_lines_valid );

	return $is_number_of_lines_valid;
}

/**
 * @param string $output
 *
 * @return bool
 */
function vipgoci_verify_number_of_lines_output( string $output ): bool
{
	return is_numeric( $output )
		? $output < VIPGOCI_VALIDATION_MAXIMUM_LINES_LIMIT
		: false;
}

/**
 * @param string $cache_key
 * @param array $cache_value
 * @param string $file_name
 * @param bool $is_number_of_lines_valid
 * Performs caching logic
 * TODO Perhaps this should be in a different file and be "injected" as dependency
 */

function vipgoci_cache_set_is_number_of_lines_valid_by_file_name(
	string $cache_key,
	array $cache_value,
	string $file_name,
	bool $is_number_of_lines_valid
): void {
	// Prepares object
	$cache_value[ $file_name ] = $is_number_of_lines_valid;
	// Saves
	vipgoci_cache_set_is_number_of_lines_valid( $cache_key, $cache_value );
}

/**
 * @param string $cache_key
 * @param array $cache_value
 * Caches result of number of validation
 *
 */
function vipgoci_cache_set_is_number_of_lines_valid(
	string $cache_key,
	array $cache_value
): void {
	vipgoci_cache( $cache_key, $cache_value );
}

/**
 * @param string $cache_key
 * Gets entire number of lines object from the cache
 *
 * @return array
 */
function vipgoci_cache_get_is_number_of_lines_valid( string $cache_key ): array
{
	$current_cached_value = vipgoci_cache( $cache_key, null );

	return is_array($current_cached_value)? $current_cached_value : array();
}

/**
 * @param string $$current_cached_value
 * @param string $file_name
 * Verifies if there's validation available for the specific file in the
 * number of lines cache object
 *
 * @return bool|null
 */
function vipgoci_cache_get_is_number_of_lines_valid_by_file_name( array $current_cached_value, string $file_name ): ?bool
{
	return $current_cached_value[ $file_name ] ?? null;
}

/**
 * @param string $commit_id
 * Built the cache key for the number of lines validation
 *
 * @return string
 */
function vipgoci_cache_get_is_number_of_lines_valid_key( string $commit_id ): string
{
	return 'number-of-lines-' . $commit_id ;
}
