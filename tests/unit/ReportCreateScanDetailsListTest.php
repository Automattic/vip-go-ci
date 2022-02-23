<?php
/**
 * Test vipgoci_report_create_scan_details_list(), which
 * generates specialized HTML lists.
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
final class ReportCreateScanDetailsListTest extends TestCase {
	/**
	 * Setup function.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../reports.php';
		require_once __DIR__ . '/../../output-security.php';
	}

	/**
	 * Test number being provided for formatting.
	 *
	 * @covers ::vipgoci_report_create_scan_details_list
	 */
	public function testDetailsListNumber(): void {
		$actual_output = vipgoci_report_create_scan_details_list(
			'left-',
			'-right',
			500,
			''
		);

		$this->assertSame(
			'left-500-right',
			$actual_output
		);
	}

	/**
	 * Test boolean being provided for formatting.
	 *
	 * @covers ::vipgoci_report_create_scan_details_list
	 */
	public function testDetailsListBool(): void {
		$actual_output = vipgoci_report_create_scan_details_list(
			'left-',
			'-right',
			true,
			''
		);

		$this->assertSame(
			'left-true-right',
			$actual_output
		);
	}

	/**
	 * Test empty array being provided for formatting.
	 *
	 * @covers ::vipgoci_report_create_scan_details_list
	 */
	public function testDetailsListArray1(): void {
		$actual_output = vipgoci_report_create_scan_details_list(
			'left-',
			'-right',
			array(),
			'fallback_results'
		);

		$this->assertSame(
			'fallback_results',
			$actual_output
		);
	}

	/**
	 * Test array with data being provided for formatting.
	 *
	 * @covers ::vipgoci_report_create_scan_details_list
	 */
	public function testDetailsListArray2(): void {
		$actual_output = vipgoci_report_create_scan_details_list(
			'<li>',
			'</li>',
			array(
				'item1' => array(
					'1',
					'2',
					'3',
					'4',
				),

				'item2' => 'testing123',
			),
			'fallback_results',
			'-separator-'
		);

		$this->assertSame(
			'<li>item1-separator-1, 2, 3, 4</li><li>item2-separator-testing123</li>',
			$actual_output
		);
	}


	/**
	 * Test array with data being provided for formatting.
	 *
	 * @covers ::vipgoci_report_create_scan_details_list
	 */
	public function testDetailsListArray3(): void {
		$actual_output = vipgoci_report_create_scan_details_list(
			'<li>',
			'</li>',
			array(
				1 => 'abc',
				2 => 'def',
				3 => '123',
			),
			'fallback_results',
			'-separator-'
		);

		$this->assertSame(
			'<li>abc</li><li>def</li><li>123</li>',
			$actual_output
		);
	}
}
