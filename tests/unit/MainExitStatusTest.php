<?php
/**
 * Test vipgoci_exit_status().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test if exit status is correctly determined.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainExitStatusTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../defines.php';
		require_once __DIR__ . './../../main.php';
	}

	/**
	 * Test if exit status is correctly determined.
	 *
	 * @covers ::vipgoci_exit_status
	 *
	 * @return void
	 */
	public function testExitStatus1() :void {
		$exit_status = vipgoci_exit_status(
			array(
				'stats' => array(
					'lint' => array(
						25 => array(
							'error' => 0,
						),
					),
				),
			),
		);

		$this->assertSame(
			0,
			$exit_status
		);
	}

	/**
	 * Test if exit status is correctly determined.
	 *
	 * @covers ::vipgoci_exit_status
	 *
	 * @return void
	 */
	public function testExitStatus2() :void {
		$exit_status = vipgoci_exit_status(
			array(
				'stats' => array(
					'lint' => array(
						25 => array(
							'error' => 30,
						),
					),
				),
			),
		);

		$this->assertSame(
			250,
			$exit_status
		);
	}

	/**
	 * Test if exit status is correctly determined.
	 *
	 * @covers ::vipgoci_exit_status
	 *
	 * @return void
	 */
	public function testExitStatusWillReturn250WhenSkippedFilesIsFound() :void {
		$exit_status = vipgoci_exit_status(
			array(
				'stats'         => array(),
				'skipped-files' => array(
					25 => array( 'total' => 1 ),
				),
			),
		);

		$this->assertSame(
			250,
			$exit_status
		);
	}
}
