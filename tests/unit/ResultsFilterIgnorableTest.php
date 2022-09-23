<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test function that filters out ignorable messages.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ResultsFilterIgnorableTest extends TestCase {
	/**
	 * Require files.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../results.php';
		require_once __DIR__ . '/../../log.php';
		require_once __DIR__ . '/../integration/IncludesForTestsOutputControl.php';
	}

	/**
	 * Test the function in different ways.
	 *
	 * @covers ::vipgoci_results_filter_ignorable
	 */
	public function testFilterIgnorable1() {
		$options = array(
 			'review-comments-ignore' => array(
				'json_encode() is discouraged. Use wp_json_encode() instead.',
				'json_encode() is discouraged. ',
				'Test 200',
				'Test 300'
			)
		);
		
		$options['review-comments-ignore'] = array_map(
			'vipgoci_results_standardize_ignorable_message',
			$options['review-comments-ignore']
		);

		$results_expected = '{"issues":{"32":[{"type":"phpcs","file_name":"bla-8.php","file_line":3,"issue":{"message":"All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found \'mysql_query\'.","source":"WordPress.Security.EscapeOutput.OutputNotEscaped","severity":5,"fixable":false,"line":3,"column":6,"level":"ERROR"}},{"type":"phpcs","file_name":"bla-8.php","file_line":3,"issue":{"message":"Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead","source":"PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved","severity":5,"fixable":false,"line":3,"column":6,"level":"ERROR"}}]},"stats":{"phpcs":{"32":{"error":2,"warning":0,"info":0}},"lint":{"32":{"error":0,"warning":0,"info":0}},"test-api":{"32":{"error":0,"warning":0,"info":0}}}}';

		$results_altered = json_decode(
			'{"issues":{"32":[{"type":"phpcs","file_name":"bla-10.php","file_line":7,"issue":{"message":"json_encode() is discouraged. Use wp_json_encode() instead.","source":"WordPress.WP.AlternativeFunctions.json_encode_json_encode","severity":5,"fixable":false,"line":7,"column":6,"level":"WARNING"}},{"type":"phpcs","file_name":"bla-8.php","file_line":3,"issue":{"message":"All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found \'mysql_query\'.","source":"WordPress.Security.EscapeOutput.OutputNotEscaped","severity":5,"fixable":false,"line":3,"column":6,"level":"ERROR"}},{"type":"phpcs","file_name":"bla-8.php","file_line":3,"issue":{"message":"Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead","source":"PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved","severity":5,"fixable":false,"line":3,"column":6,"level":"ERROR"}}]},"stats":{"phpcs":{"32":{"error":2,"warning":1,"info":0}},"lint":{"32":{"error":0,"warning":0,"info":0}},"test-api":{"32":{"error":0,"warning":0,"info":0}}}}',
			true
		);

		vipgoci_unittests_output_suppress();

		vipgoci_results_filter_ignorable(
			$options,
			$results_altered
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$results_expected,
			json_encode(
				$results_altered
			)
		);
	}
}
