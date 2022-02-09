<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/../../main.php';
require_once __DIR__ . '/../../options.php';
require_once __DIR__ . '/../../misc.php';
require_once __DIR__ . '/../../defines.php';

use PHPUnit\Framework\TestCase;

/**
 * Check if skip-large files options
 * are correctly handled.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsSkipLargeFilesTest extends TestCase {
	/**
	 * Set up variable.
	 */
	protected function setUp() :void {
		$this->options = array();
	}

	/**
	 * Clear variable.
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Check if default --skip-large-files* options are
	 * correctly provided.
	 *
	 * @covers ::vipgoci_run_init_options_skip_large_files
	 */
	public function testRunInitOptionsSkipLargeFilesDefault() :void {
		vipgoci_run_init_options_skip_large_files(
			$this->options
		);

		$this->assertSame(
			array(
				'skip-large-files'       => true,
				'skip-large-files-limit' => VIPGOCI_VALIDATION_MAXIMUM_LINES_LIMIT,
			),
			$this->options
		);
	}

	/**
	 * Check if custom --skip-large-files* options are
	 * correctly parsed.
	 *
	 * @covers ::vipgoci_run_init_options_skip_large_files
	 */
	public function testRunInitOptionsSkipLargeFilesCustom() :void {
		$this->options['skip-large-files']       = 'false';
		$this->options['skip-large-files-limit'] = '30000';

		vipgoci_run_init_options_skip_large_files(
			$this->options
		);

		$this->assertSame(
			array(
				'skip-large-files'       => false,
				'skip-large-files-limit' => 30000,
			),
			$this->options
		);
	}
}
