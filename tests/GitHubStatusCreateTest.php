<?php

namespace Vipgoci\tests;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class GitHubStatusCreateTest extends TestCase {
	var $options_git_repo_tests = array(
		'commit-test-repo-pr-diffs-4-a'	=> null,
	);

	var $options_git = array(
		'repo-owner'		=> null,
		'repo-name'		=> null,
	);

	public function setUp(): void {
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

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['token'] =
			$this->options['github-token'];

		$this->options['github-commit'] =
			$this->options['commit-test-repo-pr-diffs-4-a'];

		$this->options['build-context'] = 'vip-go-ci-temp';
	}

	public function tearDown(): void {
		$this->options = null;
		$this->options_git = null;
		$this->options_git_repo_tests = null;
	}

	private function _setBuildStatus(): void {
		sleep(2); // GitHub API requirement

		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $this->options['repo-owner'] ) . '/' .
			rawurlencode( $this->options['repo-name'] ) . '/' .
			'statuses/' .
			rawurlencode( $this->options['github-commit'] );

		$github_postfields = array(
			'state'		=> $this->options['build-state'],
			'description'	=> $this->options['build-description'],
			'context'	=> $this->options['build-context'],
			'target_url'	=> $this->options['build-target-url'],
		);

		vipgoci_github_post_url(
			$github_url,
			$github_postfields,
			$this->options['github-token']
		);
	}

	private function _getCurrentBuildStatus(): ?array {
		sleep(2);

		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $this->options['repo-owner'] ) . '/' .
			rawurlencode( $this->options['repo-name'] ) . '/' .
			'commits/' .
			rawurlencode( $this->options['github-commit'] ) . '/' .
			'status';

		$data = vipgoci_github_fetch_url(
			$github_url,
			$this->options['github-token']
		);

		$data = json_decode(
			$data,
			true
		);

		foreach( $data['statuses'] as $tmp_status ) {
			if (
				$this->options['build-context'] ===
				$tmp_status['context']
			) {
				return array(
					'state'		=> $tmp_status['state'],
					'description'	=> $tmp_status['description'],
					'context'	=> $tmp_status['context'],
					'target_url'	=> $tmp_status['target_url'],
				);
			}
		}

		return null;
	}

	/**
	 * @covers ::vipgoci_github_status_create
	 */
	public function testGitHubStatusCreate1(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		/*
		 * Set build status and
		 * then verify that it is failed.
		 */
		$new_build_description  = 'Build failure: ' . time();

		$this->options['build-state'] = 'failure';
		$this->options['build-description'] = $new_build_description;
		$this->options['build-target-url'] = null;

		$this->_setBuildStatus();

		$this->assertSame(
			array(
				'state'		=> 'failure',
				'description'	=> $new_build_description,
				'context'	=> $this->options['build-context'],
				'target_url'	=> null,
			),
			$this->_getCurrentBuildStatus()
		);

		/*
		 * Use the API function to set the
		 * status and then verify it changed.
		 */
		$new_build_description = 'Build success: ' . time();

		vipgoci_unittests_output_suppress();

		vipgoci_github_status_create(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			$this->options['github-commit'],
			'success',
			'https://automattic.com/test1',
			$new_build_description,
			$this->options['build-context']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'state'		=> 'success',
				'description'	=> $new_build_description,
				'context'	=> $this->options['build-context'],
				'target_url'	=> 'https://automattic.com/test1',
			),
			$this->_getCurrentBuildStatus()
		);
	}
}
