<?php
/**
 * Test function vipgoci_run_init_options_skip_large_files().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

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
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Set up variable, include files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../main.php';
		require_once __DIR__ . '/../../options.php';
		require_once __DIR__ . '/../../misc.php';
		require_once __DIR__ . '/../../defines.php';

		$this->options = array();
	}

	/**
	 * Clear variable.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Check if default --skip-large-files* options are
	 * correctly provided.
	 *
	 * @covers ::vipgoci_run_init_options_skip_large_files
	 *
	 * @return void
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
	 *
	 * @return void
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
