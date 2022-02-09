<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/../../main.php';

use PHPUnit\Framework\TestCase;

/**
 * Check if expected options are defined.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class VipgociOptionsRecognizedTest extends TestCase {
	/**
	 * Check if expected options are returned by the
	 * function that defines them.
	 *
	 * @covers ::vipgoci_options_recognized
	 */
	public function testOptionsRecognized() :void {
		$options_recognized_arr = vipgoci_options_recognized();

		$this->assertNotCount(
			0,
			$options_recognized_arr,
			'No options returned by vipgoci_options_recognized()'
		);

		$this->assertTrue(
			in_array(
				'help',
				$options_recognized_arr,
				true
			),
			'"help" not in array returned by vipgoci_options_recognized()'
		);
	}
}
