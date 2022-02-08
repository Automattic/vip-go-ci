<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test function intended to standardize message
 * used in ignoring results.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ResultsStandardizeIgnorableMessageTest extends TestCase {
	/**
	 * Require file needed.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../results.php';
	}

	/**
	 * Test standardizing function in different ways.
	 *
	 * @covers ::vipgci_results_standardize_ignorable_message
	 */
	public function testStandardizeMessage1() :void {
		$review_comments_input = array(
			'json_encode() is discouraged. Use wp_json_encode() instead.',
			'json_encode() is discouraged.       .',
			'      TEST 200',
			' Test 300 '
		);

		$review_comments_expected = array(
			'json_encode() is discouraged. use wp_json_encode() instead',
			'json_encode() is discouraged',
			'test 200',
			'test 300'
		);

		$review_comments_observed = array_map(
			'vipgci_results_standardize_ignorable_message',
			$review_comments_input
		);

		$this->assertSame(
			$review_comments_expected,
			$review_comments_observed
		);
	}
}
