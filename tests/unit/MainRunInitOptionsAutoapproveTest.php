<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/../../defines.php';
require_once __DIR__ . '/../../main.php';
require_once __DIR__ . '/../../options.php';
require_once __DIR__ . '/../../misc.php';

use PHPUnit\Framework\TestCase;

/**
 * Check if auto-approve options are
 * correctly handled.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsAutoapproveTest extends TestCase {
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
	 * Check if default auto-approve options are
	 * correctly provided.
	 *
	 * @covers ::vipgoci_run_init_options_autoapprove
	 */
	public function testRunInitOptionsAutoapproveDefault() :void {
		$this->options = array(
			'autoapprove'                           => null,
			'autoapprove-php-nonfunctional-changes' => null,
			'autoapprove-php-nonfunctional-changes-file-extensions' => null,
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
				'autoapprove-php-nonfunctional-changes-file-extensions' => array( 'php' ),
				'autoapprove-filetypes'                 => array(),
				'autoapprove-label'                     => 'MyText1',
			),
			$this->options
		);
	}

	/**
	 * Check if custom auto-approve options are
	 * correctly parsed.
	 *
	 * @covers ::vipgoci_run_init_options_autoapprove
	 */
	public function testRunInitOptionsAutoapproveCustom() :void {
		$this->options = array(
			'autoapprove'                           => 'true',
			'autoapprove-php-nonfunctional-changes' => 'true',
			'autoapprove-php-nonfunctional-changes-file-extensions' => 'php,inc',
			'autoapprove-filetypes'                 => 'txt,gif,png,pdf',
			'autoapprove-label'                     => 'MyText2',
			'lint-file-extensions'                  => array( 'php' ),
			'phpcs-file-extensions'                 => array( 'php', 'js', 'twig' ),
			'svg-file-extensions'                   => array( 'svg' ),
		);

		vipgoci_run_init_options_autoapprove(
			$this->options
		);

		$this->assertSame(
			array(
				'autoapprove'                           => true,
				'autoapprove-php-nonfunctional-changes' => true,
				'autoapprove-php-nonfunctional-changes-file-extensions' => array( 'php', 'inc' ),
				'autoapprove-filetypes'                 => array( 'txt', 'gif', 'png', 'pdf' ),
				'autoapprove-label'                     => 'MyText2',
				'lint-file-extensions'                  => array( 'php' ),
				'phpcs-file-extensions'                 => array( 'php', 'js', 'twig' ),
				'svg-file-extensions'                   => array( 'svg' ),
			),
			$this->options
		);
	}
}
