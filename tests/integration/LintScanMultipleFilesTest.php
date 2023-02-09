<?php
/**
 * Test vipgoci_lint_scan_multiple_files() function
 * using emulating PHP linters. The aim is to ensure that
 * multiple PHP linter functionality works and that
 * error messages are merged correctly.
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
final class LintScanMultipleFilesTest extends TestCase {
	/**
	 * Variable for options.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Specify PHP versions to emulate.
	 *
	 * @var $emulating_php_versions
	 */
	private array $emulating_php_versions = array(
		'7.3',
		'7.4',
		'8.0',
		'8.1',
	);

	/**
	 * Function to set up emulating PHP interpreters.
	 *
	 * @return void
	 */
	private function prepare_emulating_php_interpreters() :void {
		foreach (
			$this->emulating_php_versions as $php_ver
		) {
			if ( '8.0' === $php_ver ) {
				$ok_or_fail = 'ok'; // Linting PHP 8.0 should be successful.
			} else {
				$ok_or_fail = 'fail'; // Other versions should give errors.
			}

			$tmp_php_path = tempnam(
				sys_get_temp_dir(),
				'php-' . $php_ver . '-' . $ok_or_fail . '.php'
			);

			if ( false === $tmp_php_path ) {
				die( 'Unable to create temporary file.' );
			}

			if ( false === copy(
				__DIR__ . '/helper-scripts/php-linter-replacement.php',
				$tmp_php_path
			) ) {
				die( 'Unable to copy file' );
			}

			if ( false === chmod(
				$tmp_php_path,
				0700
			) ) {
				die( 'Unable to chmod file' );
			}

			$this->options['lint-php-versions'][] = $php_ver;

			$this->options['lint-php-version-paths'][ $php_ver ] =
				$tmp_php_path;
		}
	}

	/**
	 * Function to clean up emulating PHP interpreters.
	 *
	 * @return void
	 */
	private function cleanup_emulating_php_interpreters() :void {
		foreach (
			$this->emulating_php_versions as $php_ver
		) {
			unlink(
				$this->options['lint-php-version-paths'][ $php_ver ]
			);
		}
	}

	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/../../lint-scan.php';
		require_once __DIR__ . '/../../misc.php';
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../log.php';
		require_once __DIR__ . '/../../statistics.php';
		require_once __DIR__ . '/../../output-security.php';
		require_once __DIR__ . '/helper/LintScanMultipleFiles.php';
		require_once __DIR__ . '/IncludesForTestsOutputControl.php';
		require_once __DIR__ . '/IncludesForTestsMisc.php';

		$this->options = array(
			'repo-owner'               => 'test-owner',
			'repo-name'                => 'test-name',
			'commit'                   => 'commit',
			'token'                    => 'token',
			'lint'                     => true,
			'lint-skip-folders'        => array(),
			'phpcs-skip-folders'       => array(),
			'branches-ignore'          => array(),
			'skip-draft-prs'           => false,
			'skip-large-files'         => false,
			'skip-large-files-limit'   => 3,
			'lint-modified-files-only' => false,
			'local-git-repo'           => '',
		);

		$this->prepare_emulating_php_interpreters();

		global $vipgoci_debug_level;
		$vipgoci_debug_level = 2;
	}

	/**
	 * Tear down function. Remove variables.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$this->cleanup_emulating_php_interpreters();

		unset( $this->options );
	}

	/**
	 * PHP lint file with syntax errors.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_lint_scan_multiple_files
	 */
	public function testLintDoScan1() :void {

		vipgoci_unittests_output_suppress();

		$issues_submit  = array();
		$issues_skipped = array();

		$files_to_php_lint = array(
			'lint-scan-commit-test-2.php',
		);

		$prs_implicated = array();

		$issues_submit = vipgoci_lint_scan_multiple_files(
			$this->options,
			$prs_implicated,
			$issues_skipped,
			$files_to_php_lint
		);

		vipgoci_unittests_output_unsuppress();

		/*
		 * Some versions of PHP reverse the ',' and ';'
		 * in PHP linting error messages. Ensure consistency.
		 */
		for ( $i = 0; $i < 2; $i++ ) {
			$issues_submit[ $files_to_php_lint[0] ][3][ $i ]['message'] =
				vipgoci_unittests_php_syntax_error_compat(
					$issues_submit[ $files_to_php_lint[0] ][3][ $i ]['message'],
					true
				);
		}

		$this->assertSame(
			array(
				$files_to_php_lint[0] => array(
					3 => array(
						array(
							'message'  => "Linting with PHP 7.3, 7.4 turned up: <code>syntax error, unexpected ';'</code>",
							'level'    => 'ERROR',
							'severity' => 5,
						),
						array(
							'message'  => 'Linting with PHP 8.1 turned up: <code>syntax error, unexpected token ";"</code>',
							'level'    => 'ERROR',
							'severity' => 5,
						),
						// Linting with PHP 8.0 should not return error.
					),
				),
			),
			$issues_submit
		);

		unset( $this->options['commit'] );
	}
}
