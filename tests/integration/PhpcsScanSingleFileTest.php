<?php
/**
 * Test function vipgoci_phpcs_scan_single_file().
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
final class PhpcsScanSingleFileTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	var $options_phpcs = array(
		'phpcs-path'                      => null,
		'phpcs-php-path'                  => null,
		'phpcs-standard'                  => null,
		'phpcs-severity'                  => null,
		'phpcs-runtime-set'               => null,
		'commit-test-phpcs-scan-commit-1' => null,
	);

	var $options_git_repo = array(
		'repo-owner'      => null,
		'repo-name'       => null,
		'git-path'        => null,
		'github-repo-url' => null,
	);

	protected function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';

		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git_repo
		);

		vipgoci_unittests_get_config_values(
			'phpcs-scan',
			$this->options_phpcs
		);

		$this->options_phpcs['phpcs-sniffs-exclude'] = array();

		$this->options = array_merge(
			$this->options_git_repo,
			$this->options_phpcs
		);

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		if ( empty( $this->options['github-token'] ) ) {
			$this->options['github-token'] = '';
		}

		$this->options['token'] =
			$this->options['github-token'];

		$this->options['branches-ignore'] = array();

		$this->options['svg-checks'] = false;

		$this->options['lint-skip-folders'] = array();

		$this->options['phpcs-skip-folders'] = array();

		$this->options['phpcs-severity'] = (int) $this->options['phpcs-severity'];

		vipgoci_option_phpcs_runtime_set(
			$this->options,
			'phpcs-runtime-set'
		);

		$this->options['skip-large-files'] = true;

		$this->options['skip-large-files-limit'] = 15000;

		$this->options['lint-modified-files-only'] = false;
	}

	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		$this->options_phpcs    = null;
		$this->options_git_repo = null;
		unset( $this->options );
	}

	/**
	 * @covers ::vipgoci_phpcs_scan_single_file
	 */
	public function testDoScanTest1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'phpcs-runtime-set', 'github-token', 'token' ),
			$this
		);

		if ( - 1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-phpcs-scan-commit-1'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		$scan_results = vipgoci_phpcs_scan_single_file(
			$this->options,
			'my-test-file-1.php'
		);

		vipgoci_unittests_output_unsuppress();

		$expected_results = array(
			'file_issues_arr_master' => array(
				'totals' => array(
					'errors'   => 3,
					'warnings' => 0,
					'fixable'  => 0,
				),

				'files' => array(
					$scan_results['temp_file_name'] => array(
						'errors'   => 3,
						'warnings' => 0,
						'messages' => array(
							array(
								'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
								'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
								'severity' => 5,
								'fixable'  => false,
								'type'     => 'ERROR',
								'line'     => 3,
								'column'   => 20,
							),

							array(
								'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
								'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
								'severity' => 5,
								'fixable'  => false,
								'type'     => 'ERROR',
								'line'     => 7,
								'column'   => 20,
							),

							array(
								'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
								'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
								'severity' => 5,
								'fixable'  => false,
								'type'     => 'ERROR',
								'line'     => 11,
								'column'   => 20,
							),

						)

					)
				)
			),

			'file_issues_str' => '',
			'temp_file_name'  => $scan_results['temp_file_name'],
			'validation'      => [ 'total' => 0 ]
		);

		$expected_results['file_issues_str'] = json_encode(
			$expected_results['file_issues_arr_master']
		);

		$this->assertSame(
			$expected_results,
			$scan_results
		);
	}
}

