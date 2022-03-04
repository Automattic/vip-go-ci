<?php
/**
 * Helper function implementation for
 * ReportCreateScanDetails* tests.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Helper function that does not call PHP interpreter
 * but returns version number. Will return different
 * version depending on which interpreter path was
 * used.
 *
 * @param string $php_path Path to PHP.
 *
 * @return string Version number (fixed).
 */
function vipgoci_util_php_interpreter_get_version(
	string $php_path
) :string {
	if ( str_contains( $php_path, 'php7.3' ) ) {
		return '7.3.1';
	} elseif ( str_contains( $php_path, 'php7.4' ) ) {
		return '7.4.2';
	} elseif ( str_contains( $php_path, 'php8.0' ) ) {
		return '7.4.3';
	} elseif ( str_contains( $php_path, 'php8.0' ) ) {
		return '8.0.4';
	} elseif ( str_contains( $php_path, 'php8.1' ) ) {
		return '8.1.5';
	}

	return '7.4.20';
}

/**
 * Throws an exception indicating error when called.
 *
 * @param string $str         Message (not used).
 * @param array  $debug_data  Debug data (not used).
 * @param int    $exit_status Exit status (not used).
 * @param bool   $irc         (not used).
 *
 * @throws ErrorException Throws exception when called.
 *
 * @return void|int Throws exception.
 */
function vipgoci_sysexit(
	string $str,
	array $debug_data = array(),
	int $exit_status = VIPGOCI_EXIT_USAGE_ERROR,
	bool $irc = false
) {
	throw new ErrorException(
		'vipgoci_sysexit() was called. This means that some options are not defined or incorrectly used.'
	);
}

