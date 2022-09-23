<?php
/**
 * Test vipgoci_results_approved_files_comments_remove().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ResultsApprovedFilesCommentsRemoveTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';
	}

	/**
	 * Test function and compare result with expected results.
	 * Expects results for one approved file to be removed.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_results_approved_files_comments_remove
	 */
	public function testRemoveCommentFromResults() :void {
		$results_altered = json_decode(
			'{"issues":{"32":[{"type":"phpcs","file_name":"bla-10.php","file_line":7,"issue":{"message":"json_encode() is discouraged. Use wp_json_encode() instead.","source":"WordPress.WP.AlternativeFunctions.json_encode_json_encode","severity":5,"fixable":false,"line":7,"column":6,"level":"WARNING"}},{"type":"phpcs","file_name":"bla-8.php","file_line":3,"issue":{"message":"All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found \'mysql_query\'.","source":"WordPress.Security.EscapeOutput.OutputNotEscaped","severity":5,"fixable":false,"line":3,"column":6,"level":"ERROR"}},{"type":"phpcs","file_name":"bla-8.php","file_line":3,"issue":{"message":"Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead","source":"PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved","severity":5,"fixable":false,"line":3,"column":6,"level":"ERROR"}}]},"stats":{"phpcs":{"32":{"error":2,"warning":1,"info":0}},"lint":{"32":{"error":0,"warning":0,"info":0}}}}',
			true
		);

		$results_expected = json_decode(
			'{"issues":{"32":[{"type":"phpcs","file_name":"bla-8.php","file_line":3,"issue":{"message":"All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found \'mysql_query\'.","source":"WordPress.Security.EscapeOutput.OutputNotEscaped","severity":5,"fixable":false,"line":3,"column":6,"level":"ERROR"}},{"type":"phpcs","file_name":"bla-8.php","file_line":3,"issue":{"message":"Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead","source":"PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved","severity":5,"fixable":false,"line":3,"column":6,"level":"ERROR"}}]},"stats":{"phpcs":{"32":{"error":2,"warning":0,"info":0}},"lint":{"32":{"error":0,"warning":0,"info":0}}}}',
			true
		);

		$auto_approved_files_arr = array(
			'bla-10.php' => 'autoapprove-approved-php-file', // Not a value used generally, only for testing.
		);

		vipgoci_unittests_output_suppress();

		vipgoci_results_approved_files_comments_remove(
			array(),
			$results_altered,
			$auto_approved_files_arr
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$results_expected,
			$results_altered
		);
	}
}
