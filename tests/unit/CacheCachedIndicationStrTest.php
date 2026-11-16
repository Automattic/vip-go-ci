<?php
/**
 * Test vipgoci_cached_indication_str().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_cached_indication_str' )]
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
