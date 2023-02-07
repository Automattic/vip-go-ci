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
		return '8.0.3';
	} elseif ( str_contains( $php_path, 'php8.1' ) ) {
		return '8.1.4';
	}

	return '7.4.20';
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
	string $phpcs_path, // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	string $php_path // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
) :string {// phpcs:ignore WordPress.WhiteSpace.ControlStructureSpacing.NoSpaceBeforeCloseParenthesis
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

