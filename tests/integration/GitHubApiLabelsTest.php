<?php
/**
 * Test GitHub label functionality.
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
final class GitHubApiLabelsTest extends TestCase {
	/**
	 * Const for label name.
	 *
	 * @var $LABEL_NAME
	 */
	private const LABEL_NAME = 'Label for testing';

	/**
	 * Options variable for git.
	 *
	 * @var $options_git
	 */
	private array $options_git = array(
		'github-repo-url' => null,
		'repo-name'       => null,
		'repo-owner'      => null,
	);

	/**
	 * Options variable for labels.
	 *
	 * @var $options_labels
	 */
	private array $options_labels = array(
		'labels-pr-to-modify' => null,
	);

	/**
	 * Options variable for secrets.
	 *
	 * @var $options_secrets
	 */
	private $options_secrets = array();

	/**
	 * Variable for options.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Set up function.
	 *
	 * @return void.
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';

		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'labels',
			$this->options_labels
		);

		$this->options_secrets['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file.
			);

		$this->options = array_merge(
			$this->options_secrets,
			$this->options_git,
			$this->options_labels
		);
	}

	/**
	 * Tear down function.
	 *
	 * @return void.
	 */
	protected function tearDown(): void {
		$this->options_git     = null;
		$this->options_secrets = null;
		$this->options_labels  = null;
		$this->options         = null;
	}

	/**
	 * Test adding a GitHub label.
	 *
	 * @covers ::vipgoci_github_label_add_to_pr
	 *
	 * @return void
	 */
	public function testGitHubAddLabel1() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$labels_before = $this->labels_get();

		vipgoci_github_label_add_to_pr(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			$this->options['labels-pr-to-modify'],
			$this::LABEL_NAME
		);

		vipgoci_unittests_output_unsuppress();

		$labels_after = $this->labels_get();

		$this->assertSame(
			-1,
			count( $labels_before ) - count( $labels_after )
		);

		$this->assertSame(
			'Label for testing',
			$labels_after[0]->name
		);
	}

	/**
	 * Test removing a GitHub label.
	 *
	 * @covers ::vipgoci_github_pr_label_remove
	 *
	 * @return void
	 */
	public function testGitHubRemoveLabel1() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$labels_before = $this->labels_get();

		vipgoci_github_pr_label_remove(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			$this->options['labels-pr-to-modify'],
			$this::LABEL_NAME
		);

		vipgoci_unittests_output_unsuppress();

		$labels_after = $this->labels_get();

		$this->assertSame(
			1,
			count( $labels_before ) - count( $labels_after )
		);
	}

	/**
	 * Get labels.
	 *
	 * @return mixed
	 */
	private function labels_get() :mixed {
		/*
		 * Sometimes it can take GitHub
		 * a while to update its cache.
		 * Avoid stale cache by waiting
		 * a short while.
		 */
		sleep( 10 );

		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $this->options['repo-owner'] ) . '/' .
			rawurlencode( $this->options['repo-name'] ) . '/' .
			'issues/' .
			rawurlencode( $this->options['labels-pr-to-modify'] ) . '/' .
			'labels';

		$data = vipgoci_http_api_fetch_url(
			$github_url,
			$this->options['github-token']
		);

		$data = json_decode( $data );

		return $data;
	}
}
