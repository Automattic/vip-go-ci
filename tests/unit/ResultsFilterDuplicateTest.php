<?php
/**
 * Test vipgoci_results_filter_duplicate().
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
final class ResultsFilterDuplicateTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../results.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_results_filter_duplicate
	 *
	 * @return void
	 */
	public function testFilterDuplicate1() :void {
		$issues_filtered = vipgoci_results_filter_duplicate(
			array(
				array(
					'message'  => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source'   => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable'  => false,
					'type'     => 'WARNING',
					'line'     => 7,
					'column'   => 6,
					'level'    => 'WARNING',
				),
				array(
					'message'  => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source'   => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable'  => false,
					'type'     => 'WARNING',
					'line'     => 7,
					'column'   => 6,
					'level'    => 'WARNING',
				),
				array(
					'message'  => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source'   => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable'  => false,
					'type'     => 'WARNING',
					'line'     => 7,
					'column'   => 6,
					'level'    => 'WARNING',
				),
			)
		);

		$this->assertSame(
			array(
				array(
					'message'  => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source'   => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable'  => false,
					'type'     => 'WARNING',
					'line'     => 7,
					'column'   => 6,
					'level'    => 'WARNING',
				),
			),
			$issues_filtered
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_results_filter_duplicate
	 *
	 * @return void
	 */
	public function testFilterDuplicate2() :void {
		$issues_filtered = vipgoci_results_filter_duplicate(
			array(
				array(
					'message'  => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source'   => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable'  => false,
					'type'     => 'WARNING',
					'line'     => 7,
					'column'   => 6,
					'level'    => 'WARNING',
				),
				array(
					'message'  => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source'   => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable'  => false,
					'type'     => 'WARNING',
					'line'     => 7,
					'column'   => 6,
					'level'    => 'WARNING',
				),
				array(
					'message'  => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source'   => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable'  => false,
					'type'     => 'WARNING',
					'line'     => 7,
					'column'   => 6,
					'level'    => 'WARNING',
				),
				array(
					'message'  => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source'   => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable'  => false,
					'type'     => 'WARNING',
					'line'     => 80,
					'column'   => 6,
					'level'    => 'WARNING',
				),
			),
		);

		$this->assertSame(
			array(
				array(
					'message'  => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source'   => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable'  => false,
					'type'     => 'WARNING',
					'line'     => 7,
					'column'   => 6,
					'level'    => 'WARNING',
				),

				array(
					'message'  => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source'   => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable'  => false,
					'type'     => 'WARNING',
					'line'     => 80,
					'column'   => 6,
					'level'    => 'WARNING',
				),
			),
			$issues_filtered
		);
	}
}
