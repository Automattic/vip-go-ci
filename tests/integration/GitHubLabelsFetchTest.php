<?php
/**
 * Test function vipgoci_github_pr_labels_get().
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
final class GitHubLabelsFetchTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	var $options_git = array(
		'git-path'			=> null,
		'github-repo-url'		=> null,
		'repo-owner'			=> null,
		'repo-name'			=> null,
	);

	var $options_git_repo_tests = array(
		'pr-test-labels-fetch-test-1'	=> null,
	);

	protected function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';

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

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		if ( empty( $this->options['github-token'] ) ) {
			$this->options['github-token'] = '';
		}
	}

	protected function tearDown(): void {
		unset( $this->options );
		unset( $this->options_git );
		unset( $this->options_git_repo_tests );
	}

	/**
	 * @covers ::vipgoci_github_pr_labels_get
	 */
	public function testLabelsFetch1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$labels = vipgoci_github_pr_labels_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			$this->options['pr-test-labels-fetch-test-1'],
			null
		);

		vipgoci_unittests_output_unsuppress();

		if ( ! is_array( $labels ) ) {
			throw new Exception( 'Unexpected return value from vipgoci_github_pr_labels_get(), possibly incorrect GitHub credentials' );
		}

		$this->assertSame(
			'enhancement',
			$labels[0]->name
		);

		$this->assertSame(
			'a2eeef',
			$labels[0]->color
		);
	}
}

