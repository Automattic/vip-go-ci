<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/../../main.php';
require_once __DIR__ . '/../../options.php';
require_once __DIR__ . '/../../misc.php';

use PHPUnit\Framework\TestCase;

/**
 * Check if options relating to linting
 * are correctly parsed and set.
*
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsLintTest extends TestCase {
	/**
	 * Set up variable.
	 */
	protected function setUp() :void {
		$this->options = array(
			'lint'                                   => null,
			'lint-modified-files-only'               => null,
			'lint-skip-folders-in-repo-options-file' => null,
			'lint-skip-folders'                      => '/folder1/folder2/,folder3/folder4',
			'lint-php-path'                          => null,
		);
	}

	/**
	 * Clear variable.
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Check if options relating to linting
	 * are correctly parsed and set.
	 *
	 * @covers ::vipgoci_run_init_options_lint
	 */
	public function testRunInitOptionsLintDefault() :void {
		vipgoci_run_init_options_lint(
			$this->options
		);

		$this->assertSame(
			array(
				'lint'                                   => true,
				'lint-modified-files-only'               => true,
				'lint-skip-folders-in-repo-options-file' => false,
				'lint-skip-folders'                      => array( 'folder1/folder2', 'folder3/folder4' ),
				'lint-php-path'                          => 'php',
			),
			$this->options
		);
	}

	/**
	 * Check if options relating to linting
	 * are correctly parsed and set.
	 *
	 * @covers ::vipgoci_run_init_options_lint
	 */
	public function testRunInitOptionsLintCustom() :void {
		$this->options['lint']                                   = 'false';
		$this->options['lint-modified-files-only']               = 'false';
		$this->options['lint-skip-folders-in-repo-options-file'] = 'true';
		$this->options['lint-php-path']                          = null;

		vipgoci_run_init_options_lint(
			$this->options
		);

		$this->assertSame(
			array(
				'lint'                                   => false,
				'lint-modified-files-only'               => false,
				'lint-skip-folders-in-repo-options-file' => true,
				'lint-skip-folders'                      => array( 'folder1/folder2', 'folder3/folder4' ),
				'lint-php-path'                          => null,
			),
			$this->options
		);
	}
}
