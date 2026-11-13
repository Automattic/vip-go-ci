<?php
/**
 * Test vipgoci_output_sanitize_url() function.
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
#[CoversFunction( 'vipgoci_output_sanitize_url' )]
final class OutputSecuritySanitizeUrlTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../output-security.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @return void
	 */
	public function testSanitizeUrl(): void {
		$this->assertSame(
			'https://test.local/api/v3?test=foo1\--#',
			vipgoci_output_sanitize_url(
				'https://test.local/api/v3?test=foo1 \\ -- #' . PHP_EOL
			)
		);
	}
}
