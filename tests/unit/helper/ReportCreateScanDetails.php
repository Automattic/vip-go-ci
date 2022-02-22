<?php
/**
 * Helper function implementation for
 * ReportCreateScanDetails* tests.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Helper function that does nothing.
 *
 * @param string $php_path Path to PHP.
 *
 * @return string Version number (fixed).
 */
function vipgoci_util_php_interpreter_get_version(
	string $php_path
) :string {
	return '7.4.3';
}

/**
 * Helper function that does nothing.
 *
 * @param string $phpcs_path Path to PHPCS.
 * @param string $php_path   Path to PHP.
 *
 * @return string Version number (fixed).
 */
function vipgoci_phpcs_get_version(
	string $phpcs_path,
	string $php_path
) :string {
	return '3.5.5';
}

/**
 * Helper function that does nothing.
 *
 * @param array $options Options needed.
 *
 * @return array
 */
function vipgoci_options_sensitive_clean(
	array $options
) :array {
	return $options;
}

