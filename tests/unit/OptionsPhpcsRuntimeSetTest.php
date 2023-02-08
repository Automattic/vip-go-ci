<?php
/**
 * Test function vipgoci_option_phpcs_runtime_set().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class OptionsPhpcsRuntimeSetTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../options.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_option_phpcs_runtime_set
	 *
	 * @return void
	 */
	public function testOptionsPhpcsRuntimeSet1() :void {
		$this->options = array(
			'myphpcsruntimeoption' => 'testVersion 7.4-,allowUnusedVariablesBeforeRequire true,allowUndefinedVariablesInFileScope false',
			'other-option1'        => '123 456',
			'other-option2'        => array(
				'1',
				'2',
			),
		);

		vipgoci_option_phpcs_runtime_set(
			$this->options,
			'myphpcsruntimeoption',
		);

		$this->assertSame(
			array(
				'myphpcsruntimeoption' => array(
					array(
						'testVersion',
						'7.4-',
					),
					array(
						'allowUnusedVariablesBeforeRequire',
						'true',
					),
					array(
						'allowUndefinedVariablesInFileScope',
						'false',
					),
				),
				'other-option1'        => '123 456',
				'other-option2'        => array(
					'1',
					'2',
				),
			),
			$this->options
		);

		unset( $this->options );
	}
}
