<?php
/**
 * Test vipgoci_phpcs_get_version() function.
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
final class PhpcsScanGetVersionTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options_phpcs
	 */
	private array $options_phpcs = array(
		'phpcs-php-path' => null,
		'phpcs-path'     => null,
	);

	/**
	 * Fetch config values.
	 */
	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'phpcs-scan',
			$this->options_phpcs
		);
	}

	/**
	 * Unset option variable.
	 */
	protected function tearDown(): void {
		unset( $this->options_phpcs );
	}

	/**
	 * Test if fetched PHP interpreter version looks good.
	 * Uses internal function to do this.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_phpcs_get_version
	 */
	public function testPhpcsPhpInterpreterGetVersion() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options_phpcs,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$phpcs_version = vipgoci_phpcs_get_version(
			$this->options_phpcs['phpcs-path'],
			$this->options_phpcs['phpcs-php-path'],
		);

		/*
		 * Let version_compare() take care of validating the returned
		 * version number; the function does various sanity checks for
		 * us so we do not have to do it manually.
		 */
		$this->assertTrue(
			version_compare( $phpcs_version, '1.0.0', '>=' )
		);
	}
}
