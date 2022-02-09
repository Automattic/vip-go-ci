<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . './../../defines.php';
require_once __DIR__ . './../../main.php';

use PHPUnit\Framework\TestCase;

/**
 * Test if exit status is correctly determined.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainExitStatusTest extends TestCase {
	/**
	 * Test if exit status is correctly determined.
	 *
	 * @covers ::vipgoci_exit_status
	 */
	public function testExitStatus1() {
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
	 */
	public function testExitStatus2() {
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
	 */
	public function testExitStatusWillReturn250WhenSkippedFilesIsFound() {
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
