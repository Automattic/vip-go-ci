<?php
/**
 * Helper function implementation.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Helper function that does nothing.
 *
 * @param array|null $action Start or stop measuring, or dump measurements (has no effect).
 * @param array|null $type   Measurement category (has no effect).
 *
 * @return void
 */
function vipgoci_runtime_measure(
	$action = null, // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	$type = null // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
) :bool {
	// Do nothing.
	return true;
}
