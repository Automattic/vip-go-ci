<?php
/**
 * Test vipgoci_results_filter_comments_to_max().
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
final class ResultsFilterCommentsToMaxTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Results array.
	 *
	 * @var $results
	 */
	private array $results = array();

	/**
	 * Original results array.
	 *
	 * @var $results_orig
	 */
	private array $results_orig = array();

	/**
	 * Options array for git options.
	 *
	 * @var $options_git
	 */
	private array $options_git = array(
		'repo-owner' => null,
		'repo-name'  => null,
	);

	/**
	 * Options array for git repo tests.
	 *
	 * @var $options_git_repo_tests
	 */
	private array $options_git_repo_tests = array(
		'pr-test-github-pr-results-max' => null,
	);

	/**
	 * Include files, set up variables, etc.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../results.php';
		require_once __DIR__ . '/../../log.php';

		require_once __DIR__ . '/helper/ResultsFilterCommentsToMaxTest.php';

		require_once __DIR__ . '/IncludesForTestsDefines.php';
		require_once __DIR__ . '/IncludesForTestsConfig.php';
		require_once __DIR__ . '/IncludesForTestsOutputControl.php';

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

		$this->options['token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file.
			);

		$this->results = array(
			'issues' => array(
				$this->options['pr-test-github-pr-results-max'] => array(
					array(
						'type'      => 'phpcs',
						'file_name' => 'bla-8.php',
						'file_line' => 9,
						'issue'     => array(
							'message'  => 'This comment is 36% valid code; is this commented out code?',
							'source'   => 'Squiz.PHP.CommentedOutCode.Found',
							'severity' => 1,
							'fixable'  => false,
							'line'     => 9,
							'column'   => 1,
							'level'    => 'WARNING',
						),
					),
					array(
						'type'      => 'phpcs',
						'file_name' => 'bla-9.php',
						'file_line' => 10,
						'issue'     => array(
							'message'  => 'This comment is 100% valid code; is this commented out code?',
							'source'   => 'Squiz.PHP.CommentedOutCode.Found',
							'severity' => 10,
							'fixable'  => false,
							'line'     => 10,
							'column'   => 1,
							'level'    => 'WARNING',
						),
					),
				),
			),
			'stats'  => array(
				'phpcs' => array(
					$this->options['pr-test-github-pr-results-max'] => array(
						'error'   => 0,
						'warning' => 2,
						'info'    => 0,
					),
				),
			),
		);

		$this->results_orig = $this->results;
	}

	/**
	 * Clean up.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		unset( $this->options );
		unset( $this->options_git );
		unset( $this->options_git_repo_tests );
		unset( $this->results );
		unset( $this->results_orig );
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_results_filter_comments_to_max
	 *
	 * @return void
	 */
	public function testResultsFilterCommentsToMax1() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		/*
		 * Test with max 1 comments allowed.
		 */
		$this->options['review-comments-total-max'] = 1;

		$prs_comments_maxed = array();

		vipgoci_unittests_output_suppress();

		vipgoci_results_filter_comments_to_max(
			$this->options,
			$this->results,
			$prs_comments_maxed
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				$this->options['pr-test-github-pr-results-max'] => true,
			),
			$prs_comments_maxed
		);

		$this->assertSame(
			array(
				'issues' => array(
					$this->options['pr-test-github-pr-results-max'] => array(),
				),
				'stats'  => array(
					'phpcs' => array(
						$this->options['pr-test-github-pr-results-max'] => array(
							'error'   => 0,
							'warning' => 0,
							'info'    => 0,
						),
					),
				),
			),
			$this->results
		);
	}
	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_results_filter_comments_to_max
	 *
	 * @return void
	 */
	public function testResultsFilterCommentsToMax2() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		/*
		 * Exactly one more comment allowed.
		 */
		$comments_count = count(
			vipgoci_github_pr_reviews_comments_get_by_pr(
				$this->options,
				(int) $this->options['pr-test-github-pr-results-max'],
				array(
					'login'           => 'myself',
					'comments_active' => true,
				)
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->options['review-comments-total-max'] = $comments_count + 1;

		$prs_comments_maxed = array();

		vipgoci_unittests_output_suppress();

		vipgoci_results_filter_comments_to_max(
			$this->options,
			$this->results,
			$prs_comments_maxed
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				$this->options['pr-test-github-pr-results-max'] => true,
			),
			$prs_comments_maxed
		);

		$this->assertSame(
			array(
				'issues' => array(
					$this->options['pr-test-github-pr-results-max'] => array(
						array(
							'type'      => 'phpcs',
							'file_name' => 'bla-9.php',
							'file_line' => 10,
							'issue'     => array(
								'message'  => 'This comment is 100% valid code; is this commented out code?',
								'source'   => 'Squiz.PHP.CommentedOutCode.Found',
								'severity' => 10,
								'fixable'  => false,
								'line'     => 10,
								'column'   => 1,
								'level'    => 'WARNING',
							),
						),
					),
				),
				'stats'  => array(
					'phpcs' => array(
						$this->options['pr-test-github-pr-results-max'] => array(
							'error'   => 0,
							'warning' => 1,
							'info'    => 0,
						),
					),
				),
			),
			$this->results
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_results_filter_comments_to_max
	 *
	 * @return void
	 */
	public function testResultsFilterCommentsToMax3() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Max 100 allowed.
		$this->options['review-comments-total-max'] = 100;

		$prs_comments_maxed = array();

		vipgoci_unittests_output_suppress();

		vipgoci_results_filter_comments_to_max(
			$this->options,
			$this->results,
			$prs_comments_maxed
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(),
			$prs_comments_maxed
		);

		$this->assertSame(
			$this->results_orig,
			$this->results
		);
	}
}
