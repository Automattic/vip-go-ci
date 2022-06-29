<?php
/**
 * Test vipgoci_wpscan_filter_fixed_vulnerabilities() function.
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
final class WpscanFilterFixedVulnerabilitiesTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../wpscan-api.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_wpscan_filter_fixed_vulnerabilities
	 *
	 * @return void
	 */
	public function testFilterFixedVulnerabilities(): void {
		// Unfiltered results.
		$results_unfiltered = array(
			'my-plugin' => array(
				'vulnerabilities' => array(
					array(
						'id'             => 'test-124',
						'title'          => 'My plugin <= 0.9.8',
						'created_at'     => '2018-01-01T01:01:01.000Z',
						'updated_at'     => '2018-01-01T01:01:01.000Z',
						'published_date' => '2018-01-01T01:01:01.000Z',
						'description'    => 'Description string',
						'poc'            => '',
						'vuln_type'      => 'XSS',
						'references'     => array(),
						'cvss'           => '',
						'fixed_in'       => '0.9.9',
						'introduced_in'  => '',
					),
					array(
						'id'             => 'test-125',
						'title'          => 'My plugin <= 0.9.9',
						'created_at'     => '2018-02-01T01:01:01.000Z',
						'updated_at'     => '2018-02-01T01:01:01.000Z',
						'published_date' => '2018-02-01T01:01:01.000Z',
						'description'    => 'Description string',
						'poc'            => '',
						'vuln_type'      => 'XSS',
						'references'     => array(),
						'cvss'           => '',
						'fixed_in'       => '1.0.0',
						'introduced_in'  => '',
					),
					array(
						'id'             => 'test-200',
						'title'          => 'My plugin <= 1.1.0',
						'created_at'     => '2019-01-01T01:01:01.000Z',
						'updated_at'     => '2019-01-01T01:01:01.000Z',
						'published_date' => '2019-01-01T01:01:01.000Z',
						'description'    => 'Description string 2',
						'poc'            => '',
						'vuln_type'      => 'XSS',
						'references'     => array(),
						'cvss'           => '',
						'fixed_in'       => '1.1.1',
						'introduced_in'  => '',
					),
				),
			),
		);

		// Expected results after filtering.
		$results_expected = array(
			'my-plugin' => array(
				'vulnerabilities' => array(
					array(
						'id'             => 'test-200',
						'title'          => 'My plugin <= 1.1.0',
						'created_at'     => '2019-01-01T01:01:01.000Z',
						'updated_at'     => '2019-01-01T01:01:01.000Z',
						'published_date' => '2019-01-01T01:01:01.000Z',
						'description'    => 'Description string 2',
						'poc'            => '',
						'vuln_type'      => 'XSS',
						'references'     => array(),
						'cvss'           => '',
						'fixed_in'       => '1.1.1',
						'introduced_in'  => '',
					),
				),
			),
		);

		// Filter results.
		$results_filtered = vipgoci_wpscan_filter_fixed_vulnerabilities(
			'my-plugin',
			'1.0.0',
			$results_unfiltered
		);

		// Assert if the results are same as the expected ones.
		$this->assertSame(
			$results_expected,
			$results_filtered
		);
	}
}
