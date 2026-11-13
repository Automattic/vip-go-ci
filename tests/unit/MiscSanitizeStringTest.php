<?php
/**
 * Test function vipgoci_sanitize_string().
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
#[CoversFunction( 'vipgoci_sanitize_string' )]
final class MiscSanitizeStringTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../misc.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @return void
	 */
	public function testSanitizeString1() :void {
		$this->assertSame(
			'foobar',
			vipgoci_sanitize_string(
				'FooBar'
			)
		);

		$this->assertSame(
			'foobar',
			vipgoci_sanitize_string(
				'   FooBar   '
			)
		);
	}
}
