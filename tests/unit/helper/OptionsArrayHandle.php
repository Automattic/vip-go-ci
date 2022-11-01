<?php
/**
 * Helper function implementation for
 * OptionsArrayHandleTest test.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
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
		'vipgoci_sysexit() was called; message=' . $str,
	);
}
// phpcs:enable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
