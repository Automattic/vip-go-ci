<?php
/**
 * Test function vipgoci_phpcs_get_all_standards().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class PhpcsScanGetAllStandardsTest extends TestCase {
	var $options_phpcs = array(
		'phpcs-path'		=> null,
		'phpcs-php-path'	=> null,
		'phpcs-standard'	=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'phpcs-scan',
			$this->options_phpcs
		);
	}

	protected function tearDown(): void {
		$this->options_phpcs = null;
	}

	/**
	 * @covers ::vipgoci_phpcs_get_all_standards
	 */
	public function testGetAllStandardsTest1() {
		vipgoci_unittests_output_suppress();

		$all_standards = vipgoci_phpcs_get_all_standards(
			$this->options_phpcs['phpcs-path'],
			$this->options_phpcs['phpcs-php-path']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertNotEmpty(
			$all_standards
		);

		$this->assertNotFalse(
			array_search(
				$this->options_phpcs['phpcs-standard'],
				$all_standards
			)
		);
	}
}
