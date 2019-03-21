<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class PhpcsScanScanCommitTest extends TestCase {
	var $options_phpcs = array(
		'phpcs-path'				=> null,
		'phpcs-standard'			=> null,
		'phpcs-sniffs-exclude'			=> null,
		'phpcs-severity'			=> null,
		'phpcs-runtime-set'			=> null,
		'commit-test-phpcs-scan-commit-1'	=> null,
	);

	var $options_git_repo = array(
		'repo-owner'				=> null,
		'repo-name'				=> null,
		'git-path'				=> null,
		'github-repo-url'			=> null,
	);

	protected function setUp() {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git_repo
		);

		vipgoci_unittests_get_config_values(
			'phpcs-scan',
			$this->options_phpcs
		);

		$this->options = array_merge(
			$this->options_git_repo,
			$this->options_phpcs
		);

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['token'] =
			$this->options['github-token'];

		$this->options['branches-ignore'] = array();

		$this->options['svg-checks'] = false;

		$this->options['skip-folders'] = array();
	}

	protected function tearDown() {
		$this->options_phpcs = null;
		$this->options_git_repo = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_phpcs_scan_commit
	 */
	public function testDoScanTest1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'phpcs-runtime-set', 'phpcs-sniffs-exclude', 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-phpcs-scan-commit-1'];

		$issues_submit = array();
		$issues_stats = array();

		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore']
		);


		foreach( $prs_implicated as $pr_item ) {
			$issues_stats[
				$pr_item->number
			][
				'error'
			] = 0;
		}


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
		
		vipgoci_phpcs_scan_commit(
			$this->options,
			$issues_submit,
			$issues_stats
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			array(
				8 => array(
					array(
						'type'		=> 'phpcs',
						'file_name'	=> 'my-test-file-1.php',
						'file_line'	=> 3,
						'issue'	=> array(
							'message' => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source' => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable' => false,
							'type' => 'ERROR',
							'line' => 3,
							'column' => 20,
							'level' => 'ERROR',
						)
					),

					array(
						'type'		=> 'phpcs',
						'file_name'	=> 'my-test-file-1.php',
						'file_line'	=> 7,
						'issue'		=> array(
							'message' => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source' => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable' => false,
							'type' => 'ERROR',
							'line' => 7,
							'column' => 20,
							'level' => 'ERROR',
						)
					),

					array(
						'type'		=> 'phpcs',
						'file_name'	=> 'my-test-file-1.php',
						'file_line'	=> 11,
						'issue'	=> array(
							'message' => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source' => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable' => false,
							'type' => 'ERROR',
							'line' => 11,
							'column' => 20,
							'level' => 'ERROR'
						)
					)
				)
			),

			$issues_submit
		);

		$this->assertEquals(
			array(
				8 => array(
					'error' => 3,
				)
			),
			$issues_stats
		);
	}
}
