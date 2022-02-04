<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

// Note: require_once() calls should be in setUp()

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

/**
 * See README.md for details.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class Skeleton extends TestCase {
	/**
	 * Setup function.
	 *
	 * All files should be required here. See README.md.
	 */
	protected function setUp() :void {
		require_once( __DIR__ . './../../FILE-TO-BE-TESTED' );
	}

	/**
	 * @covers ::
	 */
	public function test (): void {
	}
}
