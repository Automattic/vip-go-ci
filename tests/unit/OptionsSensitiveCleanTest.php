<?php
/**
 * Test function vipgoci_options_sensitive_clean().
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
final class OptionsSensitiveCleanTest extends TestCase {
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
	 * @covers ::vipgoci_options_sensitive_clean
	 *
	 * @return void
	 */
	public function testSensitiveClean1() :void {
		$options = array(
			'a1' => 'secret',
			'b1' => 'notsecret',
			'c1' => 'secret',
			'd1' => 'secret',
			'e1' => 'notsecret',
			'f1' => 'notsecret',
		);

		$options_clean = vipgoci_options_sensitive_clean(
			$options
		);

		/*
		 * No options have been registered for
		 * cleaning, should remain unchanged.
		 */
		$this->assertSame(
			$options,
			$options_clean
		);

		/*
		 * Register two options for cleaning,
		 * those should be cleaned, but one 'secret'
		 * options should remain unchanged.
		 */
		vipgoci_options_sensitive_clean(
			null,
			array(
				'a1',
				'c1',
			)
		);

		$options_clean = vipgoci_options_sensitive_clean(
			$options
		);

		$this->assertSame(
			array(
				'a1' => '***',
				'b1' => 'notsecret',
				'c1' => '***',
				'd1' => 'secret',
				'e1' => 'notsecret',
				'f1' => 'notsecret',
			),
			$options_clean
		);

		/*
		 * Add one more yet, so all
		 * 'secret' options should be cleaned now.
		 */

		vipgoci_options_sensitive_clean(
			null,
			array(
				'd1',
			)
		);

		$options_clean = vipgoci_options_sensitive_clean(
			$options
		);

		$this->assertSame(
			array(
				'a1' => '***',
				'b1' => 'notsecret',
				'c1' => '***',
				'd1' => '***',
				'e1' => 'notsecret',
				'f1' => 'notsecret',
			),
			$options_clean
		);
	}
}
