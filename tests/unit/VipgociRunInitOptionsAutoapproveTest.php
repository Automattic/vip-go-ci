<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . '/../../main.php' );
require_once( __DIR__ . '/../../options.php' );
require_once( __DIR__ . '/../../misc.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class VipgociRunInitOptionsAutoapproveTest extends TestCase {
	protected function setUp() :void {
		$this->options = array();
	}

	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * @covers ::vipgoci_run_init_options_autoapprove
	 */
	public function testRunInitOptionsAutoapproveDefault() :void {
		$this->options = array(
			'autoapprove'                           => null,
			'autoapprove-php-nonfunctional-changes' => null,
			'autoapprove-filetypes'                 => 'txt,gif,png',
			'autoapprove-label'                     => 'MyText1',
		);

		vipgoci_run_init_options_autoapprove(
			$this->options
		);

		$this->assertSame(
			array(
				'autoapprove'                           => false,
				'autoapprove-php-nonfunctional-changes' => false,
				'autoapprove-filetypes'                 => array( 'txt', 'gif', 'png' ),
				'autoapprove-label'                     => 'MyText1',
			),
			$this->options
		);
	}

	/**
	 * @covers ::vipgoci_run_init_options_autoapprove
	 */
	public function testRunInitOptionsAutoapproveCustom() :void {
		$this->options = array(
			'autoapprove'                           => 'true',
			'autoapprove-php-nonfunctional-changes' => 'true',
			'autoapprove-filetypes'                 => 'txt,gif,png,pdf',
			'autoapprove-label'                     => 'MyText2',
		);

		vipgoci_run_init_options_autoapprove(
			$this->options
		);

		$this->assertSame(
			array(
				'autoapprove'                           => true,
				'autoapprove-php-nonfunctional-changes' => true,
				'autoapprove-filetypes'                 => array( 'txt', 'gif', 'png', 'pdf' ),
				'autoapprove-label'                     => 'MyText2',
			),
			$this->options
		);
	}
}
