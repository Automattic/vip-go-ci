<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class PhpcsScanIssuesFilterDuplicate extends TestCase {
	/**
	 * @covers ::vipgoci_issues_filter_duplicate
	 */
	public function testFilterDuplicate1() {
		$issues_filtered = vipgoci_issues_filter_duplicate(
			array(
				array(
            				'message' => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source' => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable' => false,
					'type' => 'WARNING',
					'line' => 7,
					'column' => 6,
					'level' => 'WARNING',
				),

				array(
            				'message' => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source' => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable' => false,
					'type' => 'WARNING',
					'line' => 7,
					'column' => 6,
					'level' => 'WARNING',
				),

				array(
            				'message' => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source' => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable' => false,
					'type' => 'WARNING',
					'line' => 7,
					'column' => 6,
					'level' => 'WARNING',
				),
			)
		);

		$this->assertEquals(
			$issues_filtered,
			array(
				array(
            				'message' => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source' => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable' => false,
					'type' => 'WARNING',
					'line' => 7,
					'column' => 6,
					'level' => 'WARNING',
				)
			)
		);
	}
}
