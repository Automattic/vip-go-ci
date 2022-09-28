<?php
/**
 * Mock functions required to execute tests/unit/WpCoreMiscAssignAddonFieldsTest.php
 *
 * @package Automattic/vip-go-ci
 */

declare( strict_types=1 );

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis

/**
 * Mock log.php @ vipgoci_log
 *
 * @param string $str         Log message.
 * @param array  $debug_data  Debug data accompanying the log message.
 * @param int    $debug_level Debug level of the message.
 * @param bool   $irc         If to log to IRC.
 *
 * @return void
 */
function vipgoci_log(
	string $str,
	array $debug_data = array(),
	int $debug_level = 0,
	bool $irc = false
) :void {
}

// phpcs:enable VariableAnalysis.CodeAnalysis.VariableAnalysis

