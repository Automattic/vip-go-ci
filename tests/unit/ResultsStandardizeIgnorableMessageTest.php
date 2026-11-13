<?php
/**
 * Test vipgoci_results_standardize_ignorable_message().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Test function intended to standardize message
 * used in ignoring results.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_results_standardize_ignorable_message' )]
final class ResultsStandardizeIgnorableMessageTest extends TestCase {
	/**
	 * Require file needed.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../results.php';
	}

	/**
	 * Test standardizing function in different ways.
	 *
	 * @return void
	 */
	public function testStandardizeMessage1() :void {
		$review_comments_input = array(
			'json_encode() is discouraged. Use wp_json_encode() instead.',
			'json_encode() is discouraged.       .',
			'      TEST 200',
			' Test 300 ',
		);

		$review_comments_expected = array(
			'json_encode() is discouraged. use wp_json_encode() instead',
			'json_encode() is discouraged',
			'test 200',
			'test 300',
		);

		$review_comments_observed = array_map(
			'vipgoci_results_standardize_ignorable_message',
			$review_comments_input
		);

		$this->assertSame(
			$review_comments_expected,
			$review_comments_observed
		);
	}
}
