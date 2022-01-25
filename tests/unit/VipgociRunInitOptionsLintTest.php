<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . '/../../main.php' );
require_once( __DIR__ . '/../../options.php' );
require_once( __DIR__ . '/../../misc.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class VipgociRunInitOptionsLintTest extends TestCase {
	protected function setUp() :void {
		$this->options = array(
			'lint'                                   => null,
			'lint-skip-folders-in-repo-options-file' => null,
			'lint-skip-folders'                      => '/folder1/folder2/,folder3/folder4',
		);
	}

	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * @covers ::vipgoci_run_init_options_lint
	 */
	public function testRunInitOptionsLintDefault() {
		vipgoci_run_init_options_lint(
			$this->options
		);

		$this->assertSame(
			array(
				'lint'                                   => true,
				'lint-skip-folders-in-repo-options-file' => false,
				'lint-skip-folders'                      => array( 'folder1/folder2', 'folder3/folder4' ),
			),
			$this->options
		);
	}

	/**
	 * @covers ::vipgoci_run_init_options_lint
	 */
	public function testRunInitOptionsLintCustom() {
		$this->options['lint'] = 'false';
		$this->options['lint-skip-folders-in-repo-options-file'] = 'true';

		vipgoci_run_init_options_lint(
			$this->options
		);

		$this->assertSame(
			array(
				'lint'                                   => false,
				'lint-skip-folders-in-repo-options-file' => true,
				'lint-skip-folders'                      => array( 'folder1/folder2', 'folder3/folder4' ),
			),
			$this->options
		);
	}
}
