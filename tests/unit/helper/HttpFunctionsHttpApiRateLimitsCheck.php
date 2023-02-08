<?php
/**
 * Helper function implementation for
 * HttpFunctionsHttpApiRateLimitsCheckTest test.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

/**
 * Print string $str when called.
 *
 * @param string $str         Message.
 * @param array  $debug_data  Debug data (not used).
 * @param int    $exit_status Exit status (not used).
 * @param bool   $irc         (not used).
 *
 * @return void
 */
function vipgoci_sysexit(
	string $str,
	array $debug_data = array(),
	int $exit_status = VIPGOCI_EXIT_USAGE_ERROR,
	bool $irc = false
) :void {
	echo json_encode( $str ) . PHP_EOL;
}

// phpcs:enable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

