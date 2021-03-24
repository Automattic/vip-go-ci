<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscPatchChangedLinesTest extends TestCase {
	var $options_git = array(
		'git-path'		=> null,
	);

	var $options_github = array(
		'repo-owner'	=> null,
		'repo-name'	=> null,
		'pr-base-sha'	=> null,
		'commit-id'	=> null,
		'github-repo-url'	=> null, // FIXME: Should use vip-go-ci-tests repo
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'patch-changed-lines',
			$this->options_github
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_github
		);

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['commit'] = 'master';
	}

	/**
	 * @covers ::vipgoci_patch_changed_lines
	 */
	public function testPatchChangedLines1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( empty( $this->options ) ) {
			$this->markTestSkipped(
				'Must set up vipgoci_patch_changed_lines() test'
			);

			return;
		}

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

		$patch_arr = vipgoci_patch_changed_lines(
			$this->options['local-git-repo'],
			$this->options['pr-base-sha'],
			$this->options['commit-id'],
			'ap-hashes-api.php'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			json_decode(
				'[null,"194",195,196,null,197,198,199,200,201,202]',
				true
			),
			$patch_arr
		);
	}
}
