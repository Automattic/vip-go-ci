<?php
/**
 * Test vipgoci_run_init_options_autoapprove() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

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
	 * Require files, set up variable.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../main.php';
		require_once __DIR__ . '/../../options.php';
		require_once __DIR__ . '/../../misc.php';
		require_once __DIR__ . '/helper/MainRunInitOptionsAutoapprove.php';

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
	 * Check if default auto-approve options are
	 * correctly provided.
	 *
	 * @covers ::vipgoci_run_init_options_autoapprove
	 *
	 * @return void
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
	 *
	 * @return void
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

	/**
	 * Check if errors are correctly handled.
	 *
	 * @covers ::vipgoci_run_init_options_autoapprove
	 *
	 * @return void
	 */
	public function testRunInitOptionsAutoapproveErrors1() :void {
		$this->options = array(
			'autoapprove'                           => 'true',
			'autoapprove-php-nonfunctional-changes' => 'true',
			'autoapprove-php-nonfunctional-changes-file-extensions' => 'php,inc',
			'autoapprove-filetypes'                 => 'txt,gif,png,pdf,php', // 'php' is not allowed.
			'autoapprove-label'                     => 'MyText2',
			'lint-file-extensions'                  => array( 'php' ),
			'phpcs-file-extensions'                 => array( 'php', 'js', 'twig' ),
			'svg-file-extensions'                   => array( 'svg' ),
		);

		try {
			vipgoci_run_init_options_autoapprove(
				$this->options
			);
		} catch ( \ErrorException $error ) {
			$error_msg = $error->getMessage();
		}

		$this->assertSame(
			'vipgoci_sysexit() was called; message=Parameter --autoapprove-filetypes can not contain \'"php,js,twig,svg,inc"\' as one of the values',
			$error_msg
		);
	}

	/**
	 * Check if errors are correctly handled.
	 *
	 * @covers ::vipgoci_run_init_options_autoapprove
	 *
	 * @return void
	 */
	public function testRunInitOptionsAutoapproveErrors2() :void {
		$this->options = array(
			'autoapprove'                           => 'true',
			'autoapprove-php-nonfunctional-changes' => 'true',
			'autoapprove-php-nonfunctional-changes-file-extensions' => 'php,inc',
			'autoapprove-filetypes'                 => '', // Should not be empty.
			'autoapprove-label'                     => false, // Should not be false.
			'lint-file-extensions'                  => array( 'php' ),
			'phpcs-file-extensions'                 => array( 'php', 'js', 'twig' ),
			'svg-file-extensions'                   => array( 'svg' ),
		);

		try {
			vipgoci_run_init_options_autoapprove(
				$this->options
			);
		} catch ( \ErrorException $error ) {
			$error_msg = $error->getMessage();
		}

		$this->assertSame(
			'vipgoci_sysexit() was called; message=To be able to auto-approve, file-types to approve must be specified, as well as a label; see --help for information',
			$error_msg
		);
	}
}
