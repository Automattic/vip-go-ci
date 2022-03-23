<?php
/**
 * Test () ...
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

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
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';
	}

	/**
	 * Short description of the function.
	 *
	 * @covers ::
	 *
	 * @return void
	 */
	public function testName(): void {
	}
}
