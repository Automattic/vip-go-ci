<?php
/**
 * Test vipgoci_util_php_interpreter_get_version() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

require_once __DIR__ . '/IncludesForTests.php';

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class OtherUtilitiesPhpInterpreterGetVersionTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options_lint
	 */
	private array $options_lint = array(
		'lint-php1-path' => null,
	);

	/**
	 * Fetch config values.
	 */
	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'lint-scan',
			$this->options_lint
		);
	}

	/**
	 * Unset option variable.
	 */
	protected function tearDown(): void {
		unset( $this->options_lint );
	}

	/**
	 * Test if fetched PHP interpreter version looks good.
	 * Uses internal function to do this.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_util_php_interpreter_get_version
	 */
	public function testPhpInterpreterGetVersion() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options_lint,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$php_version = vipgoci_util_php_interpreter_get_version(
			$this->options_lint['lint-php1-path'],
		);

		/*
		 * Let version_compare() take care of validating the returned
		 * version number; the function does various sanity checks for
		 * us so we do not have to do it manually.
		 */
		$this->assertTrue(
			version_compare( $php_version, '1.0.0', '>=' )
		);
	}
}
