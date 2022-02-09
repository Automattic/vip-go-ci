<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/../../main.php';
require_once __DIR__ . '/../../options.php';
require_once __DIR__ . '/../../misc.php';

use PHPUnit\Framework\TestCase;

/**
 * Check if options for support level label
 * are correctly parsed.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsSetSupportLevelLabelTest extends TestCase {
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
	 * Check if default options for support level label
	 * are correctly provided.
	 *
	 * @covers ::vipgoci_run_init_options_set_support_level_label
	 */
	public function testRunInitOptionsSetSupportLevelLabelDefault() :void {
		$this->options = array(
			'set-support-level-label'        => null,
			'set-support-level-label-prefix' => '',
			'set-support-level-field'        => '',
		);

		vipgoci_run_init_options_set_support_level_label(
			$this->options
		);

		$this->assertSame(
			array(
				'set-support-level-label'        => false,
				'set-support-level-label-prefix' => null,
				'set-support-level-field'        => null,
			),
			$this->options
		);
	}

	/**
	 * Check if custom options for support level label
	 * are correctly provided.
	 *
	 * @covers ::vipgoci_run_init_options_set_support_level_label
	 */
	public function testRunInitOptionsSetSupportLevelLabelCustom() :void {
		$this->options = array(
			'set-support-level-label'        => 'true',
			'set-support-level-label-prefix' => '  testing123  ',
			'set-support-level-field'        => '  testingABC  ',
		);

		vipgoci_run_init_options_set_support_level_label(
			$this->options
		);

		$this->assertSame(
			array(
				'set-support-level-label'        => true,
				'set-support-level-label-prefix' => 'testing123',
				'set-support-level-field'        => 'testingABC',
			),
			$this->options
		);
	}
}
