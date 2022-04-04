<?php
/**
 * Test vipgoci_irc_api_filter_ignorable_strings() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class OtherWebServicesIrcApiFilterIgnorableStringsTest extends TestCase {
	/**
	 * Setup function. Require files, set up indication.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../other-web-services.php';
		require_once __DIR__ . '/../../log.php';

		require_once __DIR__ . '/helper/IndicateTestId.php';

		vipgoci_unittests_indicate_test_id( 'OtherWebServicesIrcApiFilterIgnorableStringsTest' );
	}

	/**
	 * Tear down function. Remove indication.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		vipgoci_unittests_remove_indication_for_test_id( 'OtherWebServicesIrcApiFilterIgnorableStringsTest' );
	}

	/**
	 * Test simple usage of the function.
	 *
	 * @covers ::vipgoci_irc_api_filter_ignorable_strings
	 *
	 * @return void
	 */
	public function testFilterIgnorableStringsSimpleUsage(): void {
		$this->assertSame(
			'abcghi',
			vipgoci_irc_api_filter_ignorable_strings(
				'abc' . VIPGOCI_IRC_IGNORE_STRING_START . 'def' . VIPGOCI_IRC_IGNORE_STRING_END . 'ghi'
			)
		);
	}

	/**
	 * Test usage with JSON encoded string.
	 *
	 * @covers ::vipgoci_irc_api_filter_ignorable_strings
	 *
	 * @return void
	 */
	public function testFilterIgnorableStringsJsonUsage(): void {
		$this->assertSame(
			'"abcghi"',
			vipgoci_irc_api_filter_ignorable_strings(
				json_encode( 'abc' . VIPGOCI_IRC_IGNORE_STRING_START . 'def' . VIPGOCI_IRC_IGNORE_STRING_END . 'ghi' )
			)
		);
	}

	/**
	 * Test more complex usage of the function.
	 *
	 * @covers ::vipgoci_irc_api_filter_ignorable_strings
	 *
	 * @return void
	 */
	public function testFilterIgnorableStringsComplexUsage(): void {
		$this->assertSame(
			'abcghi123789',
			vipgoci_irc_api_filter_ignorable_strings(
				'abc' . VIPGOCI_IRC_IGNORE_STRING_START . 'def' . VIPGOCI_IRC_IGNORE_STRING_END . 'ghi' .
				'123' . VIPGOCI_IRC_IGNORE_STRING_START . '456' . VIPGOCI_IRC_IGNORE_STRING_END . '789'
			)
		);
	}

	/**
	 * Test with no strings to remove.
	 *
	 * @covers ::vipgoci_irc_api_filter_ignorable_strings
	 *
	 * @return void
	 */
	public function testFilterIgnorableStringsNotFound(): void {
		$this->assertSame(
			'abcdef',
			vipgoci_irc_api_filter_ignorable_strings(
				'abcdef'
			)
		);
	}

	/**
	 * Test invalid usage of the function.
	 *
	 * @covers ::vipgoci_irc_api_filter_ignorable_strings
	 *
	 * @return void
	 */
	public function testFilterIgnorableStringsInvalidUsage1(): void {
		ob_start();

		vipgoci_irc_api_filter_ignorable_strings(
			'ab' . VIPGOCI_IRC_IGNORE_STRING_END . 'cd' . VIPGOCI_IRC_IGNORE_STRING_START . 'ef'
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		$this->assertTrue(
			str_contains(
				$printed_data,
				'Incorrect usage of VIPGOCI_IRC_IGNORE_STRING_START and VIPGOCI_IRC_IGNORE_STRING_END; former should be placed before the latter'
			),
			'Should have printed message about invalid usage of IRC ignore constants'
		);
	}

	/**
	 * Test invalid usage of the function.
	 *
	 * @covers ::vipgoci_irc_api_filter_ignorable_strings
	 *
	 * @return void
	 */
	public function testFilterIgnorableStringsInvalidUsage2(): void {
		ob_start();

		vipgoci_irc_api_filter_ignorable_strings(
			'ab' . VIPGOCI_IRC_IGNORE_STRING_START . 'cd' . VIPGOCI_IRC_IGNORE_STRING_START .
			'ef' . VIPGOCI_IRC_IGNORE_STRING_END . 'hi' . VIPGOCI_IRC_IGNORE_STRING_END .
			'jk'
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		$this->assertTrue(
			str_contains(
				$printed_data,
				'Incorrect usage of VIPGOCI_IRC_IGNORE_STRING_START and VIPGOCI_IRC_IGNORE_STRING_END; embedding one ignore string within another is not allowed'
			),
			'Should have printed message about invalid usage of IRC ignore constants'
		);
	}
}
