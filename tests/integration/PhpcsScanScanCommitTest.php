<?php

require_once __DIR__ . '/IncludesForTests.php';

use PHPUnit\Framework\TestCase;

final class PhpcsScanScanCommitTest extends TestCase {
	var $options_phpcs = array(
		'phpcs-path'                      => null,
		'phpcs-standard'                  => null,
		'phpcs-severity'                  => null,
		'phpcs-runtime-set'               => null,
		'commit-test-phpcs-scan-commit-1' => null,
		'commit-test-phpcs-scan-commit-2' => null,
		'commit-test-phpcs-scan-commit-4' => null,
		'commit-test-phpcs-scan-commit-5' => null,
		'commit-test-phpcs-scan-commit-6' => null,
	);

	var $options_git_repo = array(
		'repo-owner'      => null,
		'repo-name'       => null,
		'git-path'        => null,
		'github-repo-url' => null,
	);

	protected function setUp(): void {
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

		$this->options['skip-draft-prs'] = false;

		$this->options['skip-large-files'] = false;

		$this->options['skip-large-files-limit'] = 15;
	}

	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		$this->options_phpcs    = null;
		$this->options_git_repo = null;
		$this->options          = null;
	}

	/**
	 * @covers ::vipgoci_phpcs_scan_commit
	 */
	public function testDoScanTest1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'phpcs-runtime-set', 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit']                                 = $this->options['commit-test-phpcs-scan-commit-1'];
		$this->options['phpcs-skip-scanning-via-labels-allowed'] = false;

		$issues_submit  = array();
		$issues_stats   = array();
		$issues_skipped = array();

		vipgoci_unittests_output_suppress();

		$prs_implicated = $this->getPRsImplicated();

		foreach ( $prs_implicated as $pr_item ) {
			$issues_stats[ $pr_item->number ]['error'] = 0;

			$issues_skipped[ $pr_item->number ]['issues']['max-lines'] = array();
			$issues_skipped[ $pr_item->number ]['issues']['total']     = 0;
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
			$issues_stats,
			$issues_skipped
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				8 => array(
					array(
						'type'      => 'phpcs',
						'file_name' => 'my-test-file-1.php',
						'file_line' => 3,
						'issue'     => array(
							'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 3,
							'column'   => 20,
							'level'    => 'ERROR',
						),
					),

					array(
						'type'      => 'phpcs',
						'file_name' => 'my-test-file-1.php',
						'file_line' => 7,
						'issue'     => array(
							'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 7,
							'column'   => 20,
							'level'    => 'ERROR',
						),
					),

					array(
						'type'      => 'phpcs',
						'file_name' => 'my-test-file-1.php',
						'file_line' => 11,
						'issue'     => array(
							'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 11,
							'column'   => 20,
							'level'    => 'ERROR',
						),
					),
				),
			),
			$issues_submit
		);

		$this->assertSame(
			array(
				8 => array(
					'error' => 3,
				),
			),
			$issues_stats
		);
	}

	/**
	 * @covers ::vipgoci_phpcs_scan_commit
	 */
	public function testDoScanTest2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'phpcs-runtime-set', 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-phpcs-scan-commit-2'];

		/*
		 * Skipping PHPCS scanning via PR labels is allowed,
		 * one PR should be set up to ask to skip but another
		 * should not.
		 */
		$this->options['phpcs-skip-scanning-via-labels-allowed'] =
			true;

		$issues_submit  = array();
		$issues_stats   = array();
		$issues_skipped = array();

		vipgoci_unittests_output_suppress();

		$prs_implicated = $this->getPRsImplicated();

		foreach ( $prs_implicated as $pr_item ) {
			$issues_stats[ $pr_item->number ]['error'] = 0;

			$issues_skipped[ $pr_item->number ]['issues']['max-lines'] = array();
			$issues_skipped[ $pr_item->number ]['issues']['total']     = 0;
		}

		vipgoci_unittests_output_unsuppress();

		/*
		 * We should have found two PRs, and
		 * we should have initialised statistics
		 * for both. Make sure it is so.
		 */
		$this->assertSame(
			array(
				22 => array(
					'error' => 0,
				),

				21 => array(
					'error' => 0,
				),
			),
			$issues_stats
		);

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

		vipgoci_phpcs_scan_commit(
			$this->options,
			$issues_submit,
			$issues_stats,
			$issues_skipped
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				21 => array(
					array(
						'type'      => 'phpcs',
						'file_name' => 'test.php',
						'file_line' => 3,
						'issue'     => array(
							'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 3,
							'column'   => 20,
							'level'    => 'ERROR',
						),
					),

					array(
						'type'      => 'phpcs',
						'file_name' => 'test.php',
						'file_line' => 7,
						'issue'     => array(
							'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 7,
							'column'   => 20,
							'level'    => 'ERROR',
						),
					),
				),

				/*
				 * No errors for PR #22 because
				 * label is set to skip PHPCS scanning.
				 */
			),
			$issues_submit
		);

		$this->assertSame(
			array(
				21 => array(
					'error' => 2,
				),

				/*
				 * Statistics for this scan-type gets
				 * removed when PHPCS scanning is skipped,
				 * so don't expect anything for PR #22.
				 */
			),
			$issues_stats
		);
	}

	/**
	 * @covers ::vipgoci_phpcs_scan_commit
	 */
	public function testDoScanTest3() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'phpcs-runtime-set', 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-phpcs-scan-commit-4'];

		$this->options['phpcs-skip-scanning-via-labels-allowed'] =
			false;

		$this->options['lint-skip-folders'] = array();

		$this->options['phpcs-skip-folders'] = array(
			'tests2',
			'tests3',
			'tests4',
		);

		$issues_submit  = array();
		$issues_stats   = array();
		$issues_skipped = array();

		vipgoci_unittests_output_suppress();

		$prs_implicated = $this->getPRsImplicated();

		foreach ( $prs_implicated as $pr_item ) {
			$issues_stats[ $pr_item->number ]['error'] = 0;

			$issues_stats[ $pr_item->number ]['warning'] = 0;

			$issues_skipped[ $pr_item->number ]['issues']['max-lines'] = array();
			$issues_skipped[ $pr_item->number ]['issues']['total']     = 0;
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
			$issues_stats,
			$issues_skipped
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				30 => array(
					array(
						'type'      => 'phpcs',
						'file_name' => 'test.php',
						'file_line' => 3,
						'issue'     => array(
							'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 3,
							'column'   => 20,
							'level'    => 'ERROR',
						),
					),

					array(
						'type'      => 'phpcs',
						'file_name' => 'test.php',
						'file_line' => 7,
						'issue'     => array(
							'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 7,
							'column'   => 20,
							'level'    => 'ERROR',
						),
					),

					array(
						'type'      => 'phpcs',
						'file_name' => 'test.php',
						'file_line' => 10,
						'issue'     => array(
							'message'  => "Scripts should be registered/enqueued via `wp_enqueue_script`. This can improve the site's performance due to script concatenation.",
							'source'   => 'WordPress.WP.EnqueuedResources.NonEnqueuedScript',
							'severity' => 3,
							'fixable'  => false,
							'type'     => 'WARNING',
							'line'     => 10,
							'column'   => 6,
							'level'    => 'WARNING',
						),
					),

					array(
						'type'      => 'phpcs',
						'file_name' => 'tests1/some_phpcs_issues.php',
						'file_line' => 3,
						'issue'     => array(
							'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 3,
							'column'   => 20,
							'level'    => 'ERROR',
						),
					),

					/*
					 * Note: tests2 folder is skipped from
					 * scanning, so no results for that
					 */
				),
			),
			$issues_submit
		);

		$this->assertSame(
			array(
				30 => array(
					'error'   => 3,
					'warning' => 1,
				),
			),
			$issues_stats
		);
	}


	/**
	 * @covers ::vipgoci_phpcs_scan_commit
	 *
	 * Test if --phpcs-sniffs-exclude is used
	 * while doing PHPCS scanning.
	 */
	public function testDoScanTest4() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'phpcs-runtime-set', 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-phpcs-scan-commit-4'];

		$this->options['phpcs-skip-scanning-via-labels-allowed'] =
			false;

		$this->options['lint-skip-folders'] = array();

		$this->options['phpcs-skip-folders'] = array();

		// Sniff to skip.
		$this->options['phpcs-sniffs-exclude'] = array(
			'WordPress.Security.EscapeOutput',
		);

		$issues_submit  = array();
		$issues_stats   = array();
		$issues_skipped = array();

		vipgoci_unittests_output_suppress();

		$prs_implicated = $this->getPRsImplicated();

		vipgoci_unittests_output_unsuppress();

		foreach ( $prs_implicated as $pr_item ) {
			$issues_stats[ $pr_item->number ]['warning'] = 0;

			$issues_stats[ $pr_item->number ]['error'] = 0;

			$issues_skipped[ $pr_item->number ]['issues']['max-lines'] = array();
			$issues_skipped[ $pr_item->number ]['issues']['total']     = 0;
		}

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		vipgoci_unittests_output_unsuppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		vipgoci_unittests_output_suppress();

		vipgoci_phpcs_scan_commit(
			$this->options,
			$issues_submit,
			$issues_stats,
			$issues_skipped
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				30 => array(
					/*
					 * Note: Escaping issues not listed, as
					 * they should have been excluded.
					 */
					array(
						'type'      => 'phpcs',
						'file_name' => 'test.php',
						'file_line' => 10,
						'issue'     => array(
							'message'  => "Scripts should be registered/enqueued via `wp_enqueue_script`. This can improve the site's performance due to script concatenation.",
							'source'   => 'WordPress.WP.EnqueuedResources.NonEnqueuedScript',
							'severity' => 3,
							'fixable'  => false,
							'type'     => 'WARNING',
							'line'     => 10,
							'column'   => 6,
							'level'    => 'WARNING',
						),
					),
				),
			),
			$issues_submit
		);

		$this->assertSame(
			array(
				30 => array(
					'warning' => 1,
					'error'   => 0,
				),
			),
			$issues_stats
		);
	}

	/**
	 * Tests when PHPCS uses basepath "." in its configuration.
	 *
	 * @covers ::vipgoci_phpcs_scan_commit
	 */
	public function testDoScanTest5() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'phpcs-runtime-set', 'github-token', 'token' ),
			$this
		);

		if ( - 1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-phpcs-scan-commit-4'];

		$this->options['phpcs-skip-scanning-via-labels-allowed'] =
			false;

		$this->options['lint-skip-folders'] = array();

		$this->options['phpcs-skip-folders'] = array();

		$issues_submit  = array();
		$issues_stats   = array();
		$issues_skipped = array();

		/*
		 * Write out a new PHPCS standard with
		 * basepath "."
		 */
		$tmp_standard = vipgoci_save_temp_file(
			'ruleset',
			'xml',
			sprintf(
				'<?xml version="1.0"?><ruleset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" name="Test-Standard-300" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/squizlabs/PHP_CodeSniffer/master/phpcs.xsd"><description>Test Coding Standards</description><arg name="basepath" value="."/><rule ref="%s"/></ruleset>',
				$this->options['phpcs-standard']
			)
		);

		/*
		 * Move new standard into a folder
		 * of its own, as otherwise PHPCS
		 * will remove the whole folder from
		 * the path returned.
		 */

		$rand_str = rand( 100000, 999999 );

		$tmp_standard_new = str_replace(
			'/ruleset',
			'/ruleset-dir' . $rand_str . '/ruleset',
			$tmp_standard
		);

		$tmp_standard_dir = pathinfo(
			$tmp_standard_new,
			PATHINFO_DIRNAME
		);

		mkdir(
			$tmp_standard_dir
		);

		rename(
			$tmp_standard,
			$tmp_standard_new
		);

		$tmp_standard = $tmp_standard_new;
		unset( $tmp_standard_new );

		/*
		 * Actually use the new standard.
		 */
		$this->options['phpcs-standard'] = $tmp_standard;

		vipgoci_unittests_output_suppress();

		$prs_implicated = $this->getPRsImplicated();

		foreach ( $prs_implicated as $pr_item ) {
			$issues_stats[ $pr_item->number ]['error']                 = 0;
			$issues_stats[ $pr_item->number ]['warning']               = 0;
			$issues_skipped[ $pr_item->number ]['issues']['max-lines'] = array();
			$issues_skipped[ $pr_item->number ]['issues']['total']     = 0;
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
			$issues_stats,
			$issues_skipped
		);

		vipgoci_unittests_output_unsuppress();

		/*
		 * Remove temporary files.
		 */
		unlink(
			$tmp_standard
		);

		rmdir(
			$tmp_standard_dir
		);

		$this->assertSame(
			array(
				30 => array(
					array(
						'type'      => 'phpcs',
						'file_name' => 'test.php',
						'file_line' => 3,
						'issue'     => array(
							'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 3,
							'column'   => 20,
							'level'    => 'ERROR',
						),
					),

					array(
						'type'      => 'phpcs',
						'file_name' => 'test.php',
						'file_line' => 7,
						'issue'     => array(
							'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 7,
							'column'   => 20,
							'level'    => 'ERROR',
						),
					),

					array(
						'type'      => 'phpcs',
						'file_name' => 'test.php',
						'file_line' => 10,
						'issue'     => array(
							'message'  => "Scripts should be registered/enqueued via `wp_enqueue_script`. This can improve the site's performance due to script concatenation.",
							'source'   => 'WordPress.WP.EnqueuedResources.NonEnqueuedScript',
							'severity' => 3,
							'fixable'  => false,
							'type'     => 'WARNING',
							'line'     => 10,
							'column'   => 6,
							'level'    => 'WARNING',
						),
					),

					array(
						'type'      => 'phpcs',
						'file_name' => 'tests1/some_phpcs_issues.php',
						'file_line' => 3,
						'issue'     => array(
							'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 3,
							'column'   => 20,
							'level'    => 'ERROR',
						),
					),

					array(
						'type'      => 'phpcs',
						'file_name' => 'tests2/some_phpcs_issues.php',
						'file_line' => 3,
						'issue'     => array(
							'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
							'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 3,
							'column'   => 20,
							'level'    => 'ERROR',
						),
					),
				),
			),
			$issues_submit
		);

		$this->assertSame(
			array(
				30 => array(
					'error'   => 4,
					'warning' => 1,
				),
			),
			$issues_stats
		);
	}

	/**
	 * @covers ::vipgoci_phpcs_scan_commit
	 * Should skip files with more than 15000 lines
	 * Expected to skip 1 file
	 * skip-large-files = true
	 * skip-large-files-limit = 15000
	 */
	public function testLintSkipLargeFilesWhenSkipLargeFilesOptionIsOnAndFileIsLargerThanLimit() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'phpcs-runtime-set', 'github-token', 'token' ),
			$this
		);

		if ( - 1 === $options_test ) {
			return;
		}

		$this->options['phpcs-skip-scanning-via-labels-allowed'] = false;
		$this->options['commit']                                 = $this->options['commit-test-phpcs-scan-commit-5'];
		vipgoci_unittests_output_suppress();

		$prs_implicated = $this->getPRsImplicated();

		$this->options['local-git-repo'] = vipgoci_unittests_setup_git_repo( $this->options );

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		/**
		 * Test actually starts here
		 * These options will define the test result
		 */
		$this->options['skip-large-files']       = true;
		$this->options['skip-large-files-limit'] = 15000;

		/**
		 * Prepare mock data
		 */
		$issues_submit  = array();
		$issues_stats   = array();
		$issues_skipped = array();

		foreach ( $prs_implicated as $pr_item ) {
			$issues_stats[ $pr_item->number ]['error'] = 0;
			$issues_skipped[ $pr_item->number ]        = $this->getDefaultSkippedFilesDueIssuesMock();
		}

		vipgoci_phpcs_scan_commit(
			$this->options,
			$issues_submit,
			$issues_stats,
			$issues_skipped
		);

		vipgoci_unittests_output_unsuppress();

		$expected_issues_skipped = array(
			39 => array(
				'issues' => array(
					'max-lines' => array( 'test1/myfile-1.php' ),
				),
				'total'  => 1,
			),
		);

		$this->assertSame(
			$expected_issues_skipped[39],
			$issues_skipped[39]
		);
	}

	/**
	 * @covers ::vipgoci_phpcs_scan_commit
	 * Should skip files with more than 15 and can otherwise
	 * Expected to scan 1 file normally and skip 1 file
	 * tests1/myfile1.php should be skipped
	 * tests1/myfile2.php should be scanned
	 * skip-large-files = true
	 * skip-large-files-limit = 15
	 */
	public function testShouldScanRegularFilesAndSkipLargeFilesWhenSkipLargeFilesOptionIsOnAndFileIsLargerThanLimit() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'phpcs-runtime-set', 'github-token', 'token' ),
			$this
		);

		if ( - 1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();
		$this->options['skip-large-files']                       = true;
		$this->options['skip-large-files-limit']                 = 15;
		$this->options['commit']                                 = $this->options['commit-test-phpcs-scan-commit-6'];
		$this->options['phpcs-skip-scanning-via-labels-allowed'] = false;
		$this->options['local-git-repo']                         = vipgoci_unittests_setup_git_repo( $this->options );

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped( 'Could not set up git repository: ' . vipgoci_unittests_output_get() );

			return;
		}

		$prs_implicated = $this->getPRsImplicated();
		$issues_submit  = array();
		$issues_stats   = array();
		$issues_skipped = array();

		foreach ( $prs_implicated as $pr_item ) {
			$issues_stats[ $pr_item->number ]   = $this->getStatsDefault();
			$issues_submit[ $pr_item->number ]  = array();
			$issues_skipped[ $pr_item->number ] = $this->getDefaultSkippedFilesDueIssuesMock();
		}

		vipgoci_phpcs_scan_commit(
			$this->options,
			$issues_submit,
			$issues_stats,
			$issues_skipped
		);

		vipgoci_unittests_output_unsuppress();

		$expected_issues_skipped = array(
			'issues' => array(
				'max-lines' => array( 'tests1/myfile1.php' ),
			),
			'total'  => 1,
		);

		$this->assertSame(
			$expected_issues_skipped,
			$issues_skipped[43]
		);

		$this->assertSame(
			array(
				'error'   => 3,
				'warning' => 1,
				'info'    => 0,
			),
			$issues_stats[43]
		);

		$this->assertSame(
			$this->getFailedIssuesSubmitMock(),
			$issues_submit[43]
		);
	}

	/**
	 * @covers ::vipgoci_phpcs_scan_commit
	 * Should scan all the files
	 * Expected to scan 2 files normally
	 * skip-large-files = false
	 * skip-large-files-limit = any
	 */
	public function testShouldScanAllTheFilesWhenSkipLargeFilesOptionIsOff() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'phpcs-runtime-set', 'github-token', 'token' ),
			$this
		);
		if ( - 1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$this->options['commit']                                 = $this->options['commit-test-phpcs-scan-commit-6'];
		$this->options['phpcs-skip-scanning-via-labels-allowed'] = false;
		$this->options['local-git-repo']                         = vipgoci_unittests_setup_git_repo( $this->options );

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped( 'Could not set up git repository: ' . vipgoci_unittests_output_get() );

			return;
		}

		$prs_implicated = $this->getPRsImplicated();

		$this->options['skip-large-files']       = false; // if we set to false, it will fail
		$this->options['skip-large-files-limit'] = 15; // if we decrease to 4, it will include 2 files in the result

		$issues_submit  = array();
		$issues_stats   = array();
		$issues_skipped = array();

		foreach ( $prs_implicated as $pr_item ) {
			$issues_stats[ $pr_item->number ]   = $this->getStatsDefault();
			$issues_submit[ $pr_item->number ]  = array();
			$issues_skipped[ $pr_item->number ] = $this->getDefaultSkippedFilesDueIssuesMock();
		}

		vipgoci_phpcs_scan_commit(
			$this->options,
			$issues_submit,
			$issues_stats,
			$issues_skipped
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'error'   => 7,
				'warning' => 2,
				'info'    => 0,
			),
			$issues_stats[43]
		);

		$this->assertSame(
			$issues_skipped[43],
			$this->getDefaultSkippedFilesDueIssuesMock()
		);

		$failedMultipleFilesSubmitMock = $this->getFailedMultipleFilesSubmitMock();
		$this->assertSame(
			$issues_submit[43][0],
			$failedMultipleFilesSubmitMock[0]
		);

		$this->assertSame(
			$issues_submit[43][5],
			$failedMultipleFilesSubmitMock[1]
		);
	}

	/**
	 * @return array
	 */
	private function getDefaultSkippedFilesDueIssuesMock() {
		return array(
			'issues' => array(),
			'total'  => 0,
		);
	}

	/**
	 * @return array[]
	 */
	private function getFailedIssuesSubmitMock(): array {
		return array(
			0 =>
				array(
					'type'      => 'phpcs',
					'file_name' => 'tests1/myfile2.php',
					'file_line' => 3,
					'issue'     =>
						array(
							'message'  => 'Detected usage of a non-sanitized input variable: $_POST[\'phpcs should catch this problem\']',
							'source'   => 'WordPress.Security.ValidatedSanitizedInput.InputNotSanitized',
							'severity' => 10,
							'fixable'  => false,
							'type'     => 'WARNING',
							'line'     => 3,
							'column'   => 6,
							'level'    => 'WARNING',
						),
				),
			1 =>
				array(
					'type'      => 'phpcs',
					'file_name' => 'tests1/myfile2.php',
					'file_line' => 3,
					'issue'     =>
						array(
							'message'  => 'All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found \'$_POST[\'phpcs should catch this problem\']\'.',
							'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 3,
							'column'   => 6,
							'level'    => 'ERROR',
						),
				),
			2 =>
				array(
					'type'      => 'phpcs',
					'file_name' => 'tests1/myfile2.php',
					'file_line' => 3,
					'issue'     =>
						array(
							'message'  => 'Processing form data without nonce verification.',
							'source'   => 'WordPress.Security.NonceVerification.Missing',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 3,
							'column'   => 6,
							'level'    => 'ERROR',
						),
				),
			3 =>
				array(
					'type'      => 'phpcs',
					'file_name' => 'tests1/myfile2.php',
					'file_line' => 3,
					'issue'     =>
						array(
							'message'  => 'Detected usage of a possibly undefined superglobal array index: $_POST[\'phpcs should catch this problem\']. Use isset() or empty() to check the index exists before using it',
							'source'   => 'WordPress.Security.ValidatedSanitizedInput.InputNotValidated',
							'severity' => 5,
							'fixable'  => false,
							'type'     => 'ERROR',
							'line'     => 3,
							'column'   => 6,
							'level'    => 'ERROR',
						),
				),
		);
	}

	/**
	 * @return string
	 */
	private function getFailedMultipleFilesSubmitMock(): array {
		return array(
			array(
				'type'      => 'phpcs',
				'file_name' => 'tests1/myfile1.php',
				'file_line' => 3,
				'issue'     =>
					array(
						'message'  => 'Detected usage of a non-sanitized input variable: $_POST[\'phpcs should catch this problem\']',
						'source'   => 'WordPress.Security.ValidatedSanitizedInput.InputNotSanitized',
						'severity' => 10,
						'fixable'  => false,
						'type'     => 'WARNING',
						'line'     => 3,
						'column'   => 6,
						'level'    => 'WARNING',
					),
			),
			array(
				'type'      => 'phpcs',
				'file_name' => 'tests1/myfile2.php',
				'file_line' => 3,
				'issue'     =>
					array(
						'message'  => 'Detected usage of a non-sanitized input variable: $_POST[\'phpcs should catch this problem\']',
						'source'   => 'WordPress.Security.ValidatedSanitizedInput.InputNotSanitized',
						'severity' => 10,
						'fixable'  => false,
						'type'     => 'WARNING',
						'line'     => 3,
						'column'   => 6,
						'level'    => 'WARNING',
					),
			),
		);
	}

	/**
	 * @return array|bool|mixed|null
	 */
	public function getPRsImplicated() {
		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore']
		);

		return $prs_implicated;
	}

	/**
	 * @return int[]
	 */
	public function getStatsDefault(): array {
		return array(
			'error'   => 0,
			'warning' => 0,
			'info'    => 0,
		);
	}
}
