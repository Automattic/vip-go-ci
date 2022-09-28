<?php
/**
 * Test vipgoci_validate_slug() function().
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
final class MiscValidateSlugTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../misc.php';
	}

	/**
	 * Test if function validates slugs correctly.
	 *
	 * @covers ::vipgoci_validate_slug
	 *
	 * @return void
	 */
	public function testValidateSlug(): void {
		/*
		 * Valid slugs.
		 */
		$this->assertTrue(
			vipgoci_validate_slug( 'abc' )
		);

		$this->assertTrue(
			vipgoci_validate_slug( '123' )
		);

		$this->assertTrue(
			vipgoci_validate_slug( 'abc-123' )
		);

		$this->assertTrue(
			vipgoci_validate_slug( 'abc-123-def' )
		);

		$this->assertTrue(
			vipgoci_validate_slug( 'abc-123-def-456' )
		);

		/*
		 * Invalid slugs.
		 */
		$this->assertFalse(
			vipgoci_validate_slug( '-abc-123' )
		);

		$this->assertFalse(
			vipgoci_validate_slug( 'abc-123,' )
		);

		$this->assertFalse(
			vipgoci_validate_slug( 'abc-123 ' )
		);

		$this->assertFalse(
			vipgoci_validate_slug( 'abc-123#' )
		);
	}
}
