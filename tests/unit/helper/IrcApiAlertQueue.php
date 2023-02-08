<?php
/**
 * Helper file.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Provide vipgoci_irc_api_alert_queue(), but does nothing.
 *
 * @param string|null $message Message string.
 * @param bool        $dump    Has no effect.
 *
 * @return void
 */
function vipgoci_irc_api_alert_queue(
	?string $message = null, // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	bool $dump = false // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
) :void { // phpcs:ignore WordPress.WhiteSpace.ControlStructureSpacing.NoSpaceBeforeCloseParenthesis
}

