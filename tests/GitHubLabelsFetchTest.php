<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubLabelsFetchTest extends TestCase {
	var $options_git = array(
		'git-path'			=> null,
		'github-repo-url'		=> null,
		'repo-owner'			=> null,
		'repo-name'			=> null,
		'github-token'			=> null,
	);

	var $options_git_repo_tests = array(
		'pr-test-labels-fetch-test-1'	=> null,
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
	}

	protected function tearDown() {
		$this->options = null;
		$this->options_git = null;
		$this->options_git_repo_tests = null;
	}

	/**
	 * @covers ::vipgoci_github_labels_get
	 */
	public function testLabelsFetch1() {
		ob_start();

		$labels = vipgoci_github_labels_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			$this->options['pr-test-labels-fetch-test-1'],
			null
		);

		ob_end_clean();

		$this->assertEquals(
			'enhancement',
			$labels[0]->name
		);

		$this->assertEquals(
			'a2eeef',
			$labels[0]->color
		);
	}
}

