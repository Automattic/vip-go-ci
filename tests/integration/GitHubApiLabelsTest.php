<?php
/**
 * Test GitHub label functionality.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_github_label_add_to_pr' )]
#[CoversFunction( 'vipgoci_github_pr_label_remove' )]
final class GitHubApiLabelsTest extends TestCase {
	private ?LivePrState $pr_state = null;
	private int $output_level = 0;
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
		$this->output_level = ob_get_level();
		require_once __DIR__ . '/IncludesForTests.php';
		require_once __DIR__ . '/LivePrState.php';

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
		try {
			$this->close_test_output_buffers();
			$this->pr_state?->restore();
		} finally {
			$this->close_test_output_buffers();
			unset( $this->options_git, $this->options_secrets, $this->options_labels, $this->options );
		}
	}

	private function close_test_output_buffers(): void {
		while ( ob_get_level() > $this->output_level ) {
			ob_end_clean();
		}
	}

	private function capture_pr_state(): void {
		$this->pr_state = new LivePrState(
			$this->options['repo-owner'], $this->options['repo-name'],
			$this->options['github-token'], (int) $this->options['labels-pr-to-modify'],
			array( self::LABEL_NAME )
		);
	}

	/**
	 * Test adding a GitHub label.
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

		if ( vipgoci_unittests_skip_github_write_tests( $this ) ) {
			return;
		}
		$this->capture_pr_state();

		vipgoci_unittests_output_suppress();

		$labels_before = $this->labels_get();

		vipgoci_github_label_add_to_pr(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			(int) $this->options['labels-pr-to-modify'],
			$this::LABEL_NAME
		);

		vipgoci_unittests_output_unsuppress();

		$labels_after = $this->labels_get();

		$this->assertContains( self::LABEL_NAME, array_column( $labels_after, 'name' ) );
		$this->assertSame( $this->pr_state->had_label( self::LABEL_NAME ) ? count( $labels_before ) : count( $labels_before ) + 1, count( $labels_after ) );
	}

	/**
	 * Test removing a GitHub label.
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

		if ( vipgoci_unittests_skip_github_write_tests( $this ) ) {
			return;
		}
		$this->capture_pr_state();

		vipgoci_unittests_output_suppress();

		vipgoci_github_label_add_to_pr( $this->options['repo-owner'], $this->options['repo-name'], $this->options['github-token'], (int) $this->options['labels-pr-to-modify'], self::LABEL_NAME );
		$labels_before = $this->labels_get();

		vipgoci_github_pr_label_remove(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			(int) $this->options['labels-pr-to-modify'],
			$this::LABEL_NAME
		);

		vipgoci_unittests_output_unsuppress();

		$labels_after = $this->labels_get();

		$this->assertSame(
			1,
			count( $labels_before ) - count( $labels_after )
		);
		$this->assertNotContains( self::LABEL_NAME, array_column( $labels_after, 'name' ) );
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
		if ( 'true' === getenv( 'VIPGOCI_TESTING_LIVE_API' ) ) {
			sleep( 10 );
		}

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
