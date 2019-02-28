<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class LintLintDoScanTest extends TestCase {
	var $options_php = array(
		'php-path'	=> null,
	);

	protected function setUp() {
		vipgoci_unittests_get_config_values(
			'lint-scan',
			$this->options_php
		);
	}

	protected function tearDown() {
		$this->options_php = null;
	}

	/**
	 * @covers ::
	 */
	public function testLintDoScan1() {
		if ( null === $this->options_php['php-path'] ) {
			$this->markTestSkipped(
				'Skipping test, not configured correctly'
			);

			return;
		}

		$php_file_path = vipgoci_save_temp_file(
			'test-lint-do-scan-1',
			'php',
			'<?php ' . PHP_EOL . 'echo "foo";' . PHP_EOL
		);

		$php_file_name = pathinfo(
			$php_file_path,
			PATHINFO_FILENAME
		);

		$ret = vipgoci_lint_do_scan(
			$this->options_php['php-path'],
			$php_file_path
		);

		$this->assertEquals(
			$ret,
			array(
				'No syntax errors detected in ' . $php_file_path
			)
		);
	}
}
