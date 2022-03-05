<?php
/**
 * Test vipgoci_lint_do_scan_file() function.
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
final class LintDoScanFileTest extends TestCase {
	/**
	 * Options variable for the tests.
	 *
	 * @var $options_php
	 */
	private array $options_php = array(
		'lint-php1-path' => null,
	);

	/**
	 * Setup function. Require files, set options variable.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';

		vipgoci_unittests_get_config_values(
			'lint-scan',
			$this->options_php
		);
	}

	/**
	 * Teardown function. Unset variables.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $this->options_php );
	}

	/**
	 * Try to PHP lint, but there are errors with running the linter.
	 *
	 * @covers ::vipgoci_lint_do_scan_file
	 */
	public function testLintDoScan1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options_php,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$php_file_path = vipgoci_save_temp_file(
			'vipgoci-lint-do-scan-test-1',
			'php',
			'<?php ' . PHP_EOL . 'echo "foo"' . PHP_EOL
		);

		vipgoci_unittests_output_suppress();

		$ret = vipgoci_lint_do_scan_file(
			'/non-existing-path/not-a-directory/does-not-exist/not-php-abc-537890133',
			$php_file_path
		);

		vipgoci_unittests_output_unsuppress();

		unlink( $php_file_path );

		$this->assertNull( $ret );
	}

	/**
	 * Test PHP linting when there are no issues.
	 *
	 * @covers ::vipgoci_lint_do_scan_file
	 */
	public function testLintDoScan2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options_php,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$php_file_path = vipgoci_save_temp_file(
			'vipgoci-lint-do-scan-test-2',
			'php',
			'<?php ' . PHP_EOL . 'echo "foo";' . PHP_EOL
		);

		vipgoci_unittests_output_suppress();

		$ret = vipgoci_lint_do_scan_file(
			$this->options_php['lint-php1-path'],
			$php_file_path
		);

		vipgoci_unittests_output_unsuppress();

		unlink( $php_file_path );

		$this->assertSame(
			array(
				'No syntax errors detected in ' . $php_file_path,
			),
			$ret
		);
	}

	/**
	 * Test PHP linting when there are issues.
	 *
	 * @covers ::vipgoci_lint_do_scan_file
	 */
	public function testLintDoScan3() {
		$options_test = vipgoci_unittests_options_test(
			$this->options_php,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$php_file_path = vipgoci_save_temp_file(
			'test-lint-do-scan-test-3',
			'php',
			'<?php ' . PHP_EOL . 'echo "foo"' . PHP_EOL
		);

		vipgoci_unittests_output_suppress();

		$ret = vipgoci_lint_do_scan_file(
			$this->options_php['lint-php1-path'],
			$php_file_path
		);

		unlink( $php_file_path );

		vipgoci_unittests_output_unsuppress();

		$ret[0] = vipgoci_unittests_php_syntax_error_compat(
			$ret[0]
		);

		$this->assertSame(
			array(
				"PHP Parse error:  syntax error, unexpected end of file, expecting ',' or ';' in " . $php_file_path . ' on line 3',
				'Errors parsing ' . $php_file_path,
			),
			$ret
		);
	}
}
