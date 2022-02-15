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
	$php_cmd = sprintf(
		'( %s %s 2>&1 )',
		escapeshellcmd( $php_path ),
		escapeshellarg( '-v' )
	);

	$php_output = vipgoci_runtime_measure_shell_exec(
		$php_cmd,
		'php_cli'
	);

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

	return $php_output_arr[0];
}

