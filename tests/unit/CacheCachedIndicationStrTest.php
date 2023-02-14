<?php
/**
 * Test vipgoci_cached_indication_str().
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
final class CacheCachedIndicationStrTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../cache.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_cached_indication_str
	 *
	 * @return void
	 */
	public function testCachedIndicationStr1() :void {
		$this->assertSame(
			' (cached)',
			vipgoci_cached_indication_str(
				true
			)
		);

		$this->assertSame(
			' (cached)',
			vipgoci_cached_indication_str(
				array( 1, 2, 3 ),
			)
		);

		$this->assertSame(
			'',
			vipgoci_cached_indication_str(
				false,
			)
		);
	}
}
