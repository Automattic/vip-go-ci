<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

require_once __DIR__ . '/IncludesForTests.php';

require_once __DIR__ . '/../unit/helper/IndicateTestId.php';

use PHPUnit\Framework\TestCase;

/**
 * Test the vipgoci_run_cleanup_irc() function.
 *
 * Should not call a HTTP endpoint as no data
 * should be in queue.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunCleanupIrcTest extends TestCase {
	/**
	 * Variable for options.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Set up all variables.
	 */
	protected function setUp(): void {
		$this->options = array(
			'irc-api-token' => '1234',
			'irc-api-bot'   => 'irc-bot',
			'irc-api-room'  => '#chatroom',
		);
	}

	/**
	 * Clear variables.
	 */
	protected function tearDown(): void {
		unset( $this->options );
	}

	/**
	 * Check if correct message is printed,
	 * indicating IRC queue clearing was attempted.
	 *
	 * @covers ::vipgoci_run_cleanup_irc
	 */
	public function testRunCleanupIrcSuccess() :void {
		$this->options['irc-api-url'] = 'https://127.0.0.1:1234';

		ob_start();

		vipgoci_run_cleanup_irc(
			$this->options
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		if ( true === vipgoci_unittests_debug_mode_on() ) {
			echo $printed_data;
		}

		/*
		 * Check if expected string was printed.
		 */
		$printed_data_found = strpos(
			$printed_data,
			'Sending messages to IRC API'
		);

		$this->assertNotFalse(
			$printed_data_found
		);

		/*
		 * Check if non-expected string was not printed.
		 */
		$printed_data_found = strpos(
			$printed_data,
			'Did not send alerts to IRC'
		);

		$this->assertFalse(
			$printed_data_found
		);
	}

	/**
	 * Check if error message is printed, indicating
	 * IRC queue clearance was not attempted.
	 *
	 * Option irc-api-url option is missing from $this->options which
	 * should lead to failure.
	 *
	 * @covers ::vipgoci_run_cleanup_irc
	 */
	public function testRunCleanupIrcFailure() :void {
		ob_start();

		vipgoci_run_cleanup_irc(
			$this->options
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		if ( true === vipgoci_unittests_debug_mode_on() ) {
			echo $printed_data;
		}

		/*
		 * Check if non-expected string was not printed.
		 */
		$printed_data_found = strpos(
			$printed_data,
			'Sending messages to IRC API'
		);

		$this->assertFalse(
			$printed_data_found
		);

		/*
		 * Check if expected string was printed.
		 */
		$printed_data_found = strpos(
			$printed_data,
			'Did not send alerts to IRC'
		);

		$this->assertNotFalse(
			$printed_data_found
		);
	}
}
