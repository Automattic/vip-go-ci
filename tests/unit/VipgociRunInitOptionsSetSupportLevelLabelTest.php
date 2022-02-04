<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . '/../../main.php' );
require_once( __DIR__ . '/../../options.php' );
require_once( __DIR__ . '/../../misc.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class VipgociRunInitOptionsSetSupportLevelLabelTest extends TestCase {
	protected function setUp() :void {
		$this->options = array(
		);
	}

	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * @covers ::vipgoci_run_init_options_set_support_level_label
	 */
	public function testRunInitOptionsSetSupportLevelLabelDefault() :void {
		$this->options = array(
			'set-support-level-label'         => null,
			'set-support-level-label-prefix'  => '',
			'set-support-level-field'         => ''
		);

		vipgoci_run_init_options_set_support_level_label(
			$this->options
		);

		$this->assertSame(
			array(
				'set-support-level-label'        => false,
				'set-support-level-label-prefix' => null,
				'set-support-level-field'        => null
			),
			$this->options
		);
	}

	/**
	 * @covers ::vipgoci_run_init_options_set_support_level_label
	 */
	public function testRunInitOptionsSetSupportLevelLabelCustom() :void {
		$this->options = array(
			'set-support-level-label'         => 'true',
			'set-support-level-label-prefix'  => '  testing123  ',
			'set-support-level-field'         => '  testingABC  '
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
