<?php
/**
 * Test () ...
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

// Note: require_once should be in setUp().

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * See README.md for details.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class Skeleton extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * All files should be required here. See README.md.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../FILE-TO-BE-TESTED';
	}

	/**
	 * Short description of the function.
	 *
	 * @covers ::
	 */
	public function testName(): void {
	}
}
