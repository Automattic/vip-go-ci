<?php

namespace Vipgoci\tests;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class VipgociRunInitOptionsSkipLargeFilesTest extends TestCase {
	protected function setUp() :void {
		$this->options = array(
		);
	}

	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * @covers ::vipgoci_run_init_options_skip_large_files
	 */
	public function testRunInitOptionsSkipLargeFilesDefault() {
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
	 * @covers ::vipgoci_run_init_options_skip_large_files
	 */
	public function testRunInitOptionsSkipLargeFilesCustom() {
		$this->options['skip-large-files'] = 'false';
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
