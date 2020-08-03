<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class ApHashesCalculateSha1SumForFileTest extends TestCase {
	var $options_git = array(
		'repo-owner'			=> null,
		'repo-name'			=> null,
		'github-repo-url'		=> null,
		'git-path'			=> null,
	);

	var $options_git_repo_tests = array(
		'commit-test-sha1sum-calculate'	=> null,
	);

	protected function setUp() {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'git-repo-tests',
			$this->options_git_repo_tests
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_git_repo_tests
		);

		$this->options['branches-ignore'] = array();
	
		$this->options['commit'] =
			$this->options['commit-test-sha1sum-calculate'];

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);
	}

	protected function tearDown() {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		$this->options = null;
		$this->options_git = null;
		$this->options_git_repo_tests = null;
	}

	/**
	 * @covers ::vipgoci_ap_hashes_calculate_sha1sum_for_file
	 */
	public function testApHashesApiHashesCalculateSha1SumForFile1() {	
		vipgoci_unittests_output_suppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		vipgoci_unittests_output_unsuppress();

		// Token is not really used, but the field needs to be defined.
		$this->options['token'] = '';

	
		vipgoci_unittests_output_suppress();

		$file_sha1 = vipgoci_ap_hashes_calculate_sha1sum_for_file(
			$this->options,
			'not-approved-1.php'
		);

		vipgoci_unittests_output_unsuppress();


		$this->assertEquals(
			'b8a0680f1173a5c7fbe95ace21dbee2a78f5bc36',
			$file_sha1
		);
	}
}
