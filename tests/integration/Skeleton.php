<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Comment to describe the test.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class Skeleton extends TestCase {
	/**
	 * Short description of the function.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';
	}

	/**
	 * Short description of the function.
	 *
	 * @covers ::
	 */
	public function testName(): void {
	}
}
