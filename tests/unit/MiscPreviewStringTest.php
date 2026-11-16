<?php
/**
 * Test vipgoci_preview_string() function.
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
#[CoversFunction( 'vipgoci_preview_string' )]
final class MiscPreviewStringTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../misc.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @return void
	 */
	public function testPreviewString(): void {
		$this->assertSame(
			'12345',
			vipgoci_preview_string(
				'123456789',
				5
			)
		);

		$this->assertSame(
			'123',
			vipgoci_preview_string(
				'123',
				10
			)
		);

		$this->assertSame(
			array( '123' ),
			vipgoci_preview_string(
				array( '123' ),
				10
			)
		);
	}
}
