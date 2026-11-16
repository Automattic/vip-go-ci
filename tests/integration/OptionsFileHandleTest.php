<?php
/**
 * Test vipgoci_option_file_handle().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_option_file_handle' )]
final class OptionsFileHandleTest extends TestCase {
	/**
	 * Set up function. Require files, etc.
	 *
	 * @return void
	 */
	public function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @return void
	 */
	public function testOptionsFileHandle1() :void {
		$options = array(
		);

		$temp_file_name = vipgoci_save_temp_file(
			'my-test-file',
			'txt',
			'content'
		);

		vipgoci_option_file_handle(
			$options,
			'mytestoption',
			$temp_file_name
		);

		$this->assertSame(
			$options['mytestoption'],
			$temp_file_name
		);

		unlink( $temp_file_name );
	}
}
