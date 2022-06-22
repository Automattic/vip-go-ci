<?php
/**
 * Validation to determine wether vip-go-ci should scan a
 * particular file or not.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Determines if the file specified can be scanned by vip-go-ci.
 *
 * @param string $temp_file_name Temporary file name.
 * @param string $file_name      File name.
 * @param string $commit_id      Commit-ID of current commit.
 * @param int    $max_lines      Maximum number of lines allowed.
 *
 * @return array Array entry with issues.
 *    [
 *        'issues' =>
 *        [
 *            'max-lines' => [ $file_name ],
 *        ],
 *        'total' => 1
 *    ]
 */
function vipgoci_validate(
	string $temp_file_name,
	string $file_name,
	string $commit_id,
	int $max_lines
): array {
	$validation_result = array( 'total' => 0 );

	if ( false === vipgoci_is_number_of_lines_valid(
		$temp_file_name,
		$file_name,
		$commit_id,
		$max_lines
	) ) {
		$validation_result['issues'][ VIPGOCI_VALIDATION_MAXIMUM_LINES ] = array( $file_name );

		$validation_result['total'] = count( $validation_result['issues'] );
	}

	return $validation_result;
}

/**
 * Verify that file does not exceed limit set. Caches results.
 *
 * @param string $temp_file_name Temporary file name.
 * @param string $file_name      File name.
 * @param string $commit_id      Commit-ID of current commit.
 * @param int    $max_lines      Maximum number of lines.
 *
 * @return bool True if within limit, else false.
 */
function vipgoci_is_number_of_lines_valid(
	string $temp_file_name,
	string $file_name,
	string $commit_id,
	int $max_lines
): bool {
	/*
	 * Check if results are cached.
	 */
	$cache_key = array(
		__FUNCTION__,
		$file_name,
		$commit_id,
	);

	// Check for cached value.
	$is_number_of_lines_valid = vipgoci_cache(
		$cache_key
	);

	vipgoci_log(
		'Validating number of lines' .
			vipgoci_cached_indication_str( $is_number_of_lines_valid ),
		array(
			'file_name'     => $file_name,
			'cached_result' => ( false === $is_number_of_lines_valid ) ? null : $is_number_of_lines_valid,
		)
	);

	if ( false !== $is_number_of_lines_valid ) {
		/*
		 * Cached value found, should be string. Convert
		 * 'true' or 'false' string to boolean type.
		 */

		return vipgoci_convert_string_to_type(
			$is_number_of_lines_valid
		);
	}

	/*
	 * Get file length (number of lines).
	 */
	$cmd = sprintf(
		'wc -l %s | awk \'{print $1;}\'',
		escapeshellcmd( $temp_file_name )
	);

	$output_2        = '';
	$output_res_code = -255;

	$output = vipgoci_runtime_measure_exec_with_retry(
		$cmd,
		array( 0 ),
		$output_2,
		$output_res_code,
		'file_validation',
		true
	);

	$output_invalid = ( ( null === $output ) || ( ! is_numeric( $output ) ) );

	vipgoci_log(
		$output_invalid ?
			'Unable to validate number of lines, unable to execute utility or invalid output' :
			'Ran utility to validate number of lines',
		array(
			'file_name' => $file_name,
			'cmd'       => $cmd,
			'output'    => $output,
		),
		$output_invalid ? 0 : 2
	);

	if ( true === $output_invalid ) {
		/*
		 * Failed to execute or invalid output. Assume file
		 * can be scanned, do not cache results.
		 */

		return true;
	}

	// Sanitize string.
	$output = vipgoci_sanitize_string(
		$output
	);

	// Check string value.
	$is_number_of_lines_valid = ( $output < $max_lines );

	// Cache results.
	vipgoci_cache(
		$cache_key,
		( true === $is_number_of_lines_valid ) ? 'true' : 'false'
	);

	vipgoci_log(
		'Validated number of lines',
		array(
			'file_name' => $file_name,
			'output'    => $output,
		)
	);

	return $is_number_of_lines_valid;
}

