<?php
/**
 * Logic for certain utilities that do not
 * belong elsewhere.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Get version of PHP interpreter specified.
 *
 * @param string $php_path Path to PHP binary to get version for.
 *
 * @return string|null PHP version number, null on failure.
 */
function vipgoci_util_php_interpreter_get_version(
	string $php_path
) :string|null {

	$cache_id = array( __FUNCTION__, $php_path );

	$cached_data = vipgoci_cache( $cache_id );

	if ( false !== $cached_data ) {
		return $cached_data;
	}

	$php_cmd = sprintf(
		'%s %s',
		escapeshellcmd( $php_path ),
		escapeshellarg( '-v' )
	);

	$php_output_2    = '';
	$php_result_code = -255;

	$php_output = vipgoci_runtime_measure_exec_with_retry(
		$php_cmd,
		array( 0 ),
		$php_output_2,
		$php_result_code,
		'php_cli',
		false
	);

	if ( null === $php_output ) {
		vipgoci_sysexit(
			'Unable to get PHP version due to error',
			array(
				'cmd'    => $php_cmd,
				'output' => $php_output,
			),
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	}

	$php_output = str_replace(
		'PHP ',
		'',
		$php_output
	);

	$php_output_arr = explode(
		' ',
		$php_output
	);

	// If something went wrong, return null.
	if ( empty( $php_output_arr[0] ) ) {
		return null;
	}

	$php_version_str = trim( $php_output_arr[0] );

	vipgoci_log(
		'Determined PHP version',
		array(
			'php-path'    => $php_path,
			'php-version' => $php_version_str,
		),
		2
	);

	vipgoci_cache( $cache_id, $php_version_str );

	return $php_version_str;
}

