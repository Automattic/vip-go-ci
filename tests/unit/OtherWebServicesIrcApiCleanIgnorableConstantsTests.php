<?php
/**
 * Test vipgoci_irc_api_clean_ignorable_constants() function.
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
final class OtherWebServicesIrcApiCleanIgnorableConstantsTests extends TestCase {
	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../other-web-services.php';
	}

	/**
	 * Test usage of the function.
	 *
	 * @covers ::vipgoci_irc_api_clean_ignorable_constants
	 *
	 * @return void
	 */
	public function testIrcApiCleanIgnorableStrings(): void {
		$this->assertSame(
			'abcdefghi',
			vipgoci_irc_api_clean_ignorable_constants(
				'abc' . VIPGOCI_IRC_IGNORE_STRING_START . 'def' . VIPGOCI_IRC_IGNORE_STRING_END . 'ghi'
			)
		);
	}
}
